<?php
/**
 * 2014 Stigmi
 *
 * bpost Shipping Manager
 *
 * Allow your customers to choose their preferrred delivery method: delivery at home or the office, at a pick-up location or in a bpack 24/7 parcel
 * machine.
 *
 * @author    Stigmi <www.stigmi.eu>
 * @copyright 2014 Stigmi
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Autoloader
{
	/**
	 * File where classes index is stored
	 */
	const INDEX_FILE = 'cache/class_bpost_index.php';

	/**
	 * @var Autoloader
	 */
	protected static $instance;

	/**
	 *  @var array array('classname' => 'path/to/override', 'classnamecore' => 'path/to/class/core')
	 */
	public $index = array();

	protected function __construct()
	{
		$this->root_dir = _PS_ROOT_DIR_.'/';
		if (file_exists($this->root_dir.self::INDEX_FILE))
			$this->index = include($this->root_dir.self::INDEX_FILE);
		else
			$this->generateIndex();
	}

	/**
	 * Get instance of autoload (singleton)
	 *
	 * @return Autoload
	 */
	public static function getInstance()
	{
		if (!Autoloader::$instance)
			Autoloader::$instance = new Autoloader();

		return Autoloader::$instance;
	}

	/**
	 * @param string $class_name
	 */
	public function load($class_name)
	{
		// regenerate the class index if the requested file doesn't exists
		if (empty($this->index[$class_name]) || !is_file($this->index[$class_name]))
			$this->generateIndex();

		if (!empty($this->index[$class_name]))
			require_once($this->index[$class_name]);

	}

	public function generateIndex()
	{
		$classes = $this->getClassesFromDir(_PS_MODULE_DIR_.'bpostshm/classes/lib/');
		ksort($classes);
		$content = '<?php return '.var_export($classes, true).'; ?>';

		// Write classes index on disc to cache it
		$filename = $this->root_dir.self::INDEX_FILE;
		$filename_tmp = tempnam(dirname($filename), basename($filename.'.'));
		if ($filename_tmp !== false && file_put_contents($filename_tmp, $content, LOCK_EX) !== false)
		{
			if (!rename($filename_tmp, $filename))
				unlink($filename_tmp);
			else
				@chmod($filename, 0666);
		}
		// $filename_tmp couldn't be written. $filename should be there anyway (even if outdated), no need to die.
		else
			error_log('Cannot write temporary file '.$filename_tmp);

		$this->index = $classes;

	}

	public function getClassesFromDir($path = '', &$classes = array())
	{
		foreach (scandir($path) as $file)
		{
			if ($file[0] != '.')
			{
				if (is_dir($path.$file))
					$this->getClassesFromDir($path.$file.'/', $classes);
				else if (Tools::substr($file, -4) == '.php')
				{
					$content = Tools::file_get_contents($path.$file);
					$pattern = '#(class|interface)\s+(?P<classname>[a-zA-Z0-9]+)(\w|\s|\\\)+\{#ix';
					if (preg_match($pattern, $content, $m) && !empty($m['classname']))
						if (strpos($m['classname'], basename($file, '.php')))
							$classes[$m['classname']] = $path.$file;
				}
			}
		}

		return $classes;
	}
}