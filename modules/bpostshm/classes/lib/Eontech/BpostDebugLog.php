<?php
/**
* Manage singular debug log
*  
* @author    Serge <serge@stigmi.eu>
* @version   0.5.0
* @copyright Copyright (c), Eontech.net. All rights reserved.
* @license   BSD License
*/

if (!defined('_PS_VERSION_'))
	exit;

class EontechBpostDebugLog
{
	const LOG_FILE_PFX = 'bpost_debug_';
	const XML_FILE_PFX = 'bd_';

	protected static $instance;
	protected static $_contents;
	protected static $_logpath;

	public function __construct()
	{
		$this->loadContents();
	}

	public function __destruct()
	{
		$this->commitContents();
	}

	/**
	 * Get instance (singleton)
	 *
	 * @return EontechBpostDebugLog
	 */
	public static function getInstance()
	{
		if (null === static::$instance)
			static::$instance = new static();

		return static::$instance;
	}

	public static function getMillitime()
	{
		return (int)round(1000 * (float)microtime(true));
	}

	public static function getSize()
	{
		$size = 0;
		$filepath = static::getLogPath();
		if (file_exists($filepath))
			$size = (int)filesize($filepath);

		return $size;
	}

	public static function hasLog()
	{
		return (bool)(static::getSize() > 5);
	}

	public static function removeLog()
	{
		$return = true;
		$filepath = static::getLogPath();
		if (file_exists($filepath))
			$return = unlink($filepath);

		return $return;
	}

	public function reset()
	{
		$this->dirty = !empty(static::$_contents);
		static::$_contents = array();
	}

	public function isValid()
	{
		return isset(static::$_contents);
	}

	public function logRequest($url, $body = null, $headers = array(), $method = 'GET', $name = '')
	{
		$this->log_entry = array(
			'qmt' => (int)static::getMillitime(),
			'name' => (string)$name,
			'type' => (string)$method,
			'url' => (string)$url,
			'header' => empty($headers) ? '' : (string)$headers[0],
			'qxml' => (string)$body
		);
	}

	public function logResponse($response, $ecode = null)
	{
		if (empty($this->log_entry))
			return;
		
		$this->log_entry['rms'] = (int)static::getMillitime() - (int)$this->log_entry['qmt'];
		if (null === $ecode)
			$this->log_entry['rxml'] = (string)$response;
		else
		{
			$this->log_entry['rxml'] = '';
			$this->log_entry['ecode'] = $ecode;
			$this->log_entry['emsg'] = (string)$response;
		}
		static::$_contents[] = $this->log_entry;
		$this->log_entry = array();
		$this->dirty = true;
	}

	public function getLogs()
	{
		$logs = array();
		$contents = $this->getContents();
		foreach ($contents as $idx => $entry)
		{
			$logs[$idx] = array(
				'time' => (int)round($entry['qmt'] / 1000),
				'name' => $entry['name'],
			 	'type' => $entry['type'],
				'url' => $entry['url'],
				'header' => $entry['header'],
				'qx' => !empty($entry['qxml']),
				'rms' => $entry['rms'],
				'rx' => !empty($entry['rxml']),
			);

			if (isset($entry['ecode']))
			{
				$logs[$idx]['ecode'] = $entry['ecode'];
				$logs[$idx]['emsg'] = $entry['emsg'];
			}
		}

		return $logs;
	}

	public function getXmlLink($key, $for_request = false)
	{
		$link = false;
		$contents = $this->getContents();
		$qr = ((bool)$for_request ? 'q' : 'r').'xml';
		if (isset($contents[$key]) && 
			!empty($contents[$key][$qr]))
		{
			$xml = $contents[$key][$qr];
			// basedir/bd_random.xml
			$base_dir = static::getBaseDir();
			$xml_files = glob($base_dir.static::XML_FILE_PFX.'*.xml');
			if (isset($xml_files[0]))
				$xml_path = $xml_files[0];
			else
				$xml_path = $base_dir.static::XML_FILE_PFX.Tools::passwdGen(6, 'NO_NUMERIC').'.xml';

			$file_op = (bool)($fp = fopen($xml_path, 'w'));
			if ($file_op)
			{
				$file_op = $file_op && fwrite($fp, $xml);
				$file_op = $file_op && fclose($fp);
				$this->xml_path = $xml_path;
				$link = str_replace(_PS_ROOT_DIR_, __PS_BASE_URI__, $xml_path);
			}
		}

		return $link;
	}

	protected static function getBaseDir()
	{
		// module_dir/pdf/log/
		// $base_dir = BpostShm::getModuleFolder('pdf').'log'.DIRECTORY_SEPARATOR;
		$base_dir = BpostShm::getModuleFolder('log');
		if (! is_dir($base_dir))
		{
			mkdir($base_dir, 0755);
			$index_file = 'index.php';
			@copy(dirname(__FILE__).DIRECTORY_SEPARATOR.$index_file, $base_dir.$index_file);
		}

		return $base_dir;
	}

	protected static function getLogPath()
	{
		if (null === static::$_logpath)
		{
			$base_dir = static::getBaseDir();
			// log_file: bpost_debug_shop_random.log
			$log_file_prefix = static::LOG_FILE_PFX.basename(_PS_ROOT_DIR_).'_';
			$log_files = glob($base_dir.$log_file_prefix.'*.log');
			if (isset($log_files[0]))
				$logpath = $log_files[0];
			else
			{
				$logpath = $base_dir.$log_file_prefix.Tools::passwdGen(8, 'NO_NUMERIC').'.log';
				if (! touch($logpath))
					error_log('Cannot create bpost log file in '.dirname($logpath));

			}
			static::$_logpath = $logpath;
		}

		return static::$_logpath;
	}

	protected function getContents()
	{
		return static::$_contents;
	}

	protected function loadContents()
	{
		$dirty = true;
		$contents = array();
		$filepath = static::getLogPath();
		if (file_exists($filepath))
		{
			$file_contents = json_decode(file_get_contents($filepath), true);
			if (null !== $file_contents)
			{
				$day_start_mt = (int)(1000 * gmmktime(0,0,0));
				$num_entries = count($file_contents);
				foreach ($file_contents as $idx => $entry)
					if ((int)$entry['qmt'] >= $day_start_mt)
						$contents[] = $file_contents[$idx];

				$dirty = $num_entries && (count($contents) < $num_entries);
			}
		}
		/*
		else
		{
			$parts = explode('/', $filepath);
			$file = array_pop($parts);
			$dir = '';
			foreach($parts as $part)
				if(! is_dir($dir .= "/$part"))
					mkdir($dir, 0755);
			
			if (! touch($filepath))
				error_log('Cannot create bpost log file in '.dirname($filepath));

		}*/

		static::$_contents = $contents;
		$this->dirty = (bool)$dirty;
		$this->commitContents();
	}

	protected function commitContents()
	{
		if (!$this->isValid() || !$this->dirty)
			return;

		$filename = static::getLogPath();
		$filename_tmp = tempnam(dirname($filename), basename($filename.'~'));
		if ($filename_tmp !== false && file_put_contents($filename_tmp, json_encode(static::$_contents), LOCK_EX) !== false)
		{
			if (!rename($filename_tmp, $filename))
				unlink($filename_tmp);
			else
				@chmod($filename, 0644);
		}
		// $filename_tmp couldn't be written, but old version still there.
		else
			error_log('Cannot write temporary bpost log file '.$filename_tmp);
	}
}