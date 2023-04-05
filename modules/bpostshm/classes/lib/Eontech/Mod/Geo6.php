<?php
/**
 * Geo6 class (updated: 5 jun 2017)
 * updated 'taxipost' change: 25 jun 2019
 *
 * @author    Serge Jamasb <serge@stigmi.eu>
 * @version   3.3.9
 * @copyright Copyright (c), Eontech.net. All rights reserved.
 * @license   BSD License
 */

class EontechModGeo6
{
	/* URL for the api */
	/* const API_URL = 'http://taxipost.geo6.be/Locator'; */
	const API_URL = 'http://pudo.bpost.be/Locator';

	/* current version */
	const VERSION = '3';

	/**
	 * @var string
	 */
	private $app_id;

	/**
	 * @var string
	 */
	private $partner;

	/**
	 * The timeout
	 *
	 * @var int
	 */
	private $time_out = 10;

	/**
	 * The user agent
	 *
	 * @var string
	 */
	private $user_agent;

	/**
	 * Constructor
	 * @param string $partner Static parameter used for protection/statistics
	 * @param string $app_id   Static parameter used for protection/statistics
	 */
	public function __construct($partner, $app_id)
	{
		$this->setPartner((string)$partner);
		$this->setAppId((string)$app_id);
	}

	/**
	 * Build the url to be called
	 *
	 * @param  string	 $method
	 * @param  array|null $parameters
	 * @return string
	 */
	private function buildUrl($method, $parameters = null)
	{
		// add credentials
		$parameters['Function'] = $method;
		$parameters['Partner'] = $this->getPartner();
		$parameters['AppId'] = $this->getAppId();
		$parameters['Format'] = 'xml';

		return self::API_URL.'?'.http_build_query($parameters);
	}

	/**
	 * Make the real call
	 *
	 * @param  string		   $method
	 * @param  array|null	   $parameters
	 * @return SimpleXMLElement
	 * @throws EontechModException
	 */
	private function doCall($method, $parameters = null)
	{
		$options = array();
		$options[CURLOPT_URL] = $this->buildUrl($method, $parameters);
		$options[CURLOPT_USERAGENT] = $this->getUserAgent();
		$options[CURLOPT_FOLLOWLOCATION] = $this->getFollowLocation();
		$options[CURLOPT_SSL_VERIFYPEER] = false;
		$options[CURLOPT_SSL_VERIFYHOST] = false;
		$options[CURLOPT_RETURNTRANSFER] = true;
		$options[CURLOPT_TIMEOUT] = (int)$this->getTimeOut();

		$curl = curl_init();

		// set options
		curl_setopt_array($curl, $options);

		// execute
		$response = curl_exec($curl);
		$error_number = curl_errno($curl);
		$error_message = curl_error($curl);

		// error?
		if ($error_number != '')
			throw new EontechModException($error_message, $error_number);

		// we expect XML so decode it
		$xml = simplexml_load_string($response);

		// validate xml
		if ($xml === false || (isset($xml->head) && isset($xml->body)))
			throw new EontechModException('Invalid XML-response.');

		// catch generic errors
		if (isset($xml['type']) && (string)$xml['type'] == 'TaxipostLocatorError')
			throw new EontechModException((string)$xml->txt);

		// return
		return $xml;
	}

	/**
	 * @param string $app_id
	 */
	public function setAppId($app_id)
	{
		$this->app_id = $app_id;
	}

	/**
	 * @return string
	 */
	public function getAppId()
	{
		return $this->app_id;
	}

	/**
	 * @param string $partner
	 */
	public function setPartner($partner)
	{
		$this->partner = $partner;
	}

	/**
	 * @return string
	 */
	public function getPartner()
	{
		return $this->partner;
	}

	/**
	 * Set the timeout
	 * After this time the request will stop. You should handle any errors triggered by this.
	 *
	 * @param int $seconds The timeout in seconds.
	 */
	public function setTimeOut($seconds)
	{
		$this->time_out = (int)$seconds;
	}

	/**
	 * Get the timeout that will be used
	 *
	 * @return int
	 */
	public function getTimeOut()
	{
		return (int)$this->time_out;
	}

	/**
	 * Get the useragent that will be used.
	 * Our version will be prepended to yours.
	 * It will look like: "PHP Bpost/<version> <your-user-agent>"
	 *
	 * @return string
	 */
	public function getUserAgent()
	{
		return (string)'PHP Bpost Geo6/'.self::VERSION.' '.$this->user_agent;
	}

	/**
	 * Set the user-agent for you application
	 * It will be appended to ours, the result will look like: "PHP Bpost/<version> <your-user-agent>"
	 *
	 * @param string $user_agent Your user-agent, it should look like <app-name>/<app-version>.
	 */
	public function setUserAgent($user_agent)
	{
		$this->user_agent = (string)$user_agent;
	}

	/**
	 * Get follow location. true / false
	 * based on server open basedir value
	 *
	 * @return bool
	 */
	public function getFollowLocation()
	{
		$open_basedir = ini_get('open_basedir');

		return (bool)empty($open_basedir);
	}

	/* webservice methods */
	/**
	 * The GetNearestServicePoints web service delivers the nearest bpost pick-up points to a location
	 *
	 * @param string $street   Street name
	 * @param string $number   Street number
	 * @param string $zone	 Postal code and/or city
	 * @param string $country 2-letter iso code, possible values are: BE, FR, getSupportedCountries()
	 * @param string $language Language, possible values are: nl, fr
	 * @param int	$type	 Requested point type, possible values are:
	 *							  1: Post Office
	 *							  2: Post Point
	 *							  3: (1+2, Post Office + Post Point)
	 *							  4: bpack 24/7
	 *							  7: (1+2+4, Post Office + Post Point + bpack 24/7)
	 *							  8: Click & Collect Shop
	 *							  15: (1+2+4+8, Post Office + Post Point + bpack 24/7 + C&C Shops)
	 *							  
	 * @param  int   $limit
	 * v3.3.5 extra params
	 * @param string $dd Delivery date DD-MM-YYYY (M)
	 * @param bool $check_date return avail points based on delivery date (M)
	 * @param bool $check_list 0/1 Return known holidaystart / end for the points
	 * @param bool $check_open Refine check_date only points open in the morning of delivery date
	 * 
	 * @return array
	 * @throws EontechModException
	 */
	public function getNearestServicePoint($street, $number, $zone, $country = 'BE', $language = 'nl', $type = 3, $limit = 10,
			$dd = null, $check_date = 1, $check_list = 0, $check_open = 1)
	{
		$parameters = array();
		$parameters['Street'] = (string)$street;
		$parameters['Number'] = (string)$number;
		$parameters['Zone'] = (string)$zone;
		$parameters['Country'] = (string)$country;
		$parameters['Language'] = (string)$language;
		$parameters['Type'] = (int)$type;
		$parameters['Limit'] = (int)$limit;
		//
		if (!is_null($dd))
		{
			$parameters['DD'] = (string)$dd;
			$parameters['CheckDate'] = (int)$check_date;
			$parameters['CheckList'] = (int)$check_list;
			$parameters['CheckOpen'] = (int)$check_open;
		}

		$xml = $this->doCall('search', $parameters);

		if (!isset($xml->PoiList->Poi))
			throw new EontechModException('Invalid XML-response');

		$pois = array();
		foreach ($xml->PoiList->Poi as $poi)
			$pois[] = array(
				'poi' => EontechModGeo6Poi::createFromXML($poi->Record),
				'distance' => (float)$poi->Distance,
			);

		return $pois;
	}

	/**
	 * The GetServicePointDetails web service delivers the details for a bpost
	 * pick up point referred to by its identifier.
	 *
	 * @param string $id	   Requested point identifier
	 * @param string $language Language, possible values: nl, fr
	 * @param string $country 2-letter iso code, possible values are: BE, FR, getSupportedCountries()
	 * @param int	$type	 Requested point type, possible values are:
	 *							  1: Post Office
	 *							  2: Post Point
	 *							  4: bpack 24/7
	 *							  8: Click & Collect Shop
	 * @return EontechModGeo6Poi
	 * @throws EontechModException
	 */
	public function getServicePointDetails($id, $language = 'nl', $country = 'BE', $type = 3)
	{
		$parameters = array();
		$parameters['Id'] = (string)$id;
		$parameters['Language'] = (string)$language;
		$parameters['Country'] = (string)$country;
		$parameters['Type'] = (int)$type;

		$xml = $this->doCall('info', $parameters);

		if (!isset($xml->Poi->Record))
			throw new EontechModException('Invalid XML-response.');

		return EontechModGeo6Poi::createFromXML($xml->Poi->Record);
	}

	/**
	 * @param  $id
	 * @param  string $language
	 * @param  int	$type
	 * @return string
	 */
	public function getServicePointPage($id, $language = 'nl', $type = 3)
	{
		$parameters = array();
		$parameters['Id'] = (string)$id;
		$parameters['Language'] = (string)$language;
		$parameters['Type'] = (int)$type;

		return $this->buildUrl('page', $parameters);
	}
}
