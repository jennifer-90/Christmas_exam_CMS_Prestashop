<?php
/**
 * Main Service God Class
 *
 * @author    Serge <serge@stigmi.eu>
 * @copyright 2015 Stigmi
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

require_once(_PS_CLASS_DIR_.'Tools.php');
require_once(_PS_MODULE_DIR_.'bpostshm/bpostshm.php');

class Service
{
	private static $_home_slugs = array(
		array(	// 0 - national
			'24h Pro',
			'24h business',
		),
		array(	// 0 - international
			'World Express Pro',
			'World Business',
		),
	);

	private static $_extended_slugs = array(
		BpostShm::SHM_HOME => array(
			array(	// 0 - national
				'24h Pro',
				'24h business',
			),
			array(	// 0 - international
				'World Express Pro',
				'World Business',
			),
		),
		BpostShm::SHM_PPOINT => array(
			'@bpost',
			'@bpost international',
		),
		BpostShm::SHM_PLOCKER => array(
			'Parcel locker',
			'ERR:support',
		),
	);

	const GEO6_APP_ID = '';
	const GEO6_PARTNER = 999999;

	/* bpost accepted min, max weights (g) */
	const WEIGHT_MIN = 10;
	const WEIGHT_MAX = 30000;

	public static $cache = array();

	/**
	 * @var Service
	 */
	protected static $instance;
	private $context;
	private $geo6;
	public $bpost;

	/**
	 * @param Context $context
	 */
	public function __construct(Context $context)
	{
		require_once(_PS_MODULE_DIR_.'bpostshm/classes/Autoloader.php');
		spl_autoload_register(array(Autoloader::getInstance(), 'load'));

		$this->context = $context;

		// $this->bpost = new EontechBpostServiceTest(
		// Srg: 28-aug-18
		$this->bpost = new EontechBpostService(
			Configuration::get('BPOST_ACCOUNT_ID'),
			Configuration::get('BPOST_ACCOUNT_PASSPHRASE'),
			// Configuration::get('BPOST_ACCOUNT_API_URL')
			BpostShm::API_URL,
			(bool)Configuration::get('BPOST_DEBUG_LOG_ENABLE')
		);
		//
		$this->geo6 = new EontechModGeo6(
			self::GEO6_PARTNER,
			self::GEO6_APP_ID
		);
		$this->module = new BpostShm();
	}

	/**
	 * @param Context $context
	 * @return Service
	 */
	public static function getInstance(Context $context = null)
	{
		if (!Service::$instance)
		{
			if (is_null($context))
				$context = Context::getContext();
			self::$instance = new Service($context);
		}

		return Service::$instance;
	}

	public static function isPrestashop155plus()
	{
		return version_compare(_PS_VERSION_, '1.5.5.0', '>=');
	}
/*
	public static function isPrestashop15plus()
	{
		return version_compare(_PS_VERSION_, '1.5', '>=');
	}
*/
	public static function isPrestashop16plus()
	{
		return version_compare(_PS_VERSION_, '1.6', '>=');
	}

	public static function isPrestashop161plus()
	{
		return version_compare(_PS_VERSION_, '1.6.1.0', '>=');
	}

	public static function isPrestashop17plus()
	{
		return version_compare(_PS_VERSION_, '1.7', '>=');
	}

	public static function getPsVer()
	{
		return (string)Tools::substr(_PS_VERSION_, 0, 3);
	}

	public static function isMultistore()
	{
		// return (bool)Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE');
		return (bool)Shop::isFeatureActive();
	}

	public static function isValidJSON($json)
	{
		$json = trim($json);
		$valid = !empty($json) && !is_numeric($json);
		if ($valid = $valid && in_array(Tools::substr($json, 0, 1), array('{', '[')))
		{
			Tools::jsonDecode($json);
			$valid = JSON_ERROR_NONE === json_last_error();
		}

		return $valid;
	}

	public static function isoInEU($iso_code)
	{
		return (bool)in_array(Tools::strtoupper($iso_code), array(
			// EU 28
			'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI',
			'FR', 'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU',
			'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE', 'XI',
			// OMR + special case
			'GF', 'GP', 'MQ', 'ME', 'YT', 'RE', 'MF', 'GI', 'AX',
    		// OCT + microstates
    		'PM', 'GL', 'BL', 'SX', 'AW', 'CW', 'WF', 'PF', 'NC',
    		'TF', 'AI', 'BM', 'IO', 'VG', 'KY', 'FK', 'MS', 'PN',
    		'SH', 'GS', 'TC', 'AD', 'LI', 'MC', 'SM', 'VA', 'JE', 'GG',
		));
	}

	public static function getActualShm($db_shm)
	{
		// actual shipping method in 1st 3-bits
		return $db_shm & 7;
	}

	public static function isNonEu($db_shm)
	{
		return (bool)((int)$db_shm & BpostShm::SHM_IMASK_EAD);
	}

	public static function isInternational($db_shm)
	{
		// return BpostShm::SHM_INTL == (int)$db_shm;
		return (bool)((int)$db_shm & BpostShm::SHM_IMASK);
		// return (bool)((int)$db_shm & (BpostShm::SHM_IMASK | BpostShm::SHM_IMASK_EAD));
	}

	public static function isAtHome($shm)
	{
		$is_athome = $shm & BpostShm::SHM_HOME;
		return (bool)$is_athome;
	}

	public static function isServicePoint($db_shm)
	{
		return (bool)((int)$db_shm & BpostShm::SHM_SPMASK);
	}

	public static function getOrderIDFromReference($reference = '')
	{
		$return = false;

		$ref_parts = explode('_', $reference);
		if (3 === count($ref_parts))
			$return = (int)$ref_parts[1];

		return $return;
	}

	/**
	 * @param string|null $reference
	 * @param object|null $bpost 
	 * @return string|false
	 */
	public static function getBpostOrderStatus($bpost = null, $reference = null)
	{
		if (is_null($bpost) || is_null($reference))
			return false;

		$status = false;
		// $reference = Tools::substr($reference, 0, 50);

		try {
			$bpost_order = $bpost->fetchOrder((string)$reference);
			$boxes = $bpost_order->getBoxes();
			foreach ($boxes as $box)
			{
				$box_status = (string)$box->getStatus();
				if (false === $status)
					$status = $box_status;
				elseif ($status !== $box_status)
					$status = 'MULTIPLE';
			}

		} catch (EontechModException $e) {
			$status = false;
			throw $e;
			// self::logError('getOrderStatus Ref: '.$reference, $e->getMessage(), $e->getCode(), 'Order', isset($order->id) ? $order->id : 0);
		}

		return $status;
	}

	/**
	 * @param string $reference
	 * @param  bool $is_retour
	 * @return bool
	 */
	public static function addLabel($reference = '', $is_retour = false)
	{
		$order_bpost = PsOrderBpost::getByReference($reference);
		return isset($order_bpost) && $order_bpost->addLabel((bool)$is_retour);
	}

	/**
	 * @param string $reference
	 * @param  int $count number of labels to add
	 * @param  bool $is_retour
	 * @return bool
	 */
	public static function addLabels($reference = '', $count = 1, $is_retour = false)
	{
		$order_bpost = PsOrderBpost::getByReference($reference);
		return isset($order_bpost) && $order_bpost->addLabels((int)$count, (bool)$is_retour);
	}

	/**
	 * bulkPrintLabels (1.5+ only)
	 * @param  array $refs order references
	 * @return array       label links array (optional error[reference] array)
	 */
	public static function bulkPrintLabels($refs)
	{
		$links = array();
		if (empty($refs) || !is_array($refs))
		{
			$links['error']['dev'][] = 'No orders to bulk print';
			return $links;
		}

		$shop_orders = PsOrderBpost::fetchOrdersbyRefs($refs);
		if (empty($shop_orders))
		{
			$links['error']['dev'][] = 'Invalid reference(s)';
			return $links;
		}

		$tt_links = array();
		$orders_status = array();
		foreach ($shop_orders as $id_shop => $ref_orders)
		{
			Shop::setContext(Shop::CONTEXT_SHOP, (int)$id_shop);
			$printing_tt = (bool)Configuration::get('BPOST_LABEL_TT_INTEGRATION');

			$svc = new self(Context::getContext());
			foreach ($ref_orders as $order_ref)
			{
				if ('CANCELLED' === (string)$order_ref['status'])
					continue;

				$order_bpost = new PsOrderBpost((int)$order_ref['id_order_bpost']);
				$new_links = $svc->printOrderLabels($order_bpost);

				if (!isset($new_links['error']))
				{
					$reference = (string)$order_bpost->reference;
					try {
						$new_status = self::getBpostOrderStatus($svc->bpost, $reference);
						if ($printing_tt)
							$tt_links[] = $reference;

					} catch (Exception $e) {
						$msg = 'bad XML in RetrieveOrder Service: '.$e->getMessage();
						$links['error'][$reference][] = $msg;
						// the order must nevertheless, be valid and printed to be here
						$new_status = 'PRINTED';
					}

					if (false !== $new_status && $new_status !== $order_bpost->status)
						$orders_status[] = array(
							'id_order_bpost' => (int)$order_ref['id_order_bpost'],
							'status' => (string)$new_status,
						);

				}

				$links = array_merge_recursive($links, $new_links);

			}
		}
		PsOrderBpost::updateBulkOrderStatus($orders_status);
		$links = EontechPdfManager::mergedLinks($links);
		if (! empty($tt_links))
			$links['tt'] = $tt_links;

		return $links;
	}

	public static function refreshBulkOrderBpostStatus()
	{
		$orders_status = array();
		$shops_id = Shop::getCompleteListOfShopsID();
		foreach ($shops_id as $id_shop)
		{
			$proceed = (bool)Configuration::get('BPOST_USE_PS_LABELS', null, null, (int)$id_shop);
			if ($proceed &&
				($order_refs = PsOrderBpost::fetchBulkOrderRefs((int)BpostShm::DEF_ORDER_BPOST_DAYS, (int)$id_shop)))
				try {
					// Srg: 28-aug-18
					$settings = array(
						'BPOST_ACCOUNT_ID',
						'BPOST_ACCOUNT_PASSPHRASE',
						// 'BPOST_ACCOUNT_API_URL',
						);

					$settings = Configuration::getMultiple($settings, null, null, (int)$id_shop);
					$bpost = new EontechBpostService(
						$settings['BPOST_ACCOUNT_ID'],
						$settings['BPOST_ACCOUNT_PASSPHRASE'],
						// $settings['BPOST_ACCOUNT_API_URL']
						BpostShm::API_URL
					);
					//
					foreach ($order_refs as $order_ref)
						if ($new_status = self::getBpostOrderStatus($bpost, $order_ref['reference']))
							if ($new_status !== (string)$order_ref['status'])
								$orders_status[] = array(
									'id_order_bpost' => $order_ref['id_order_bpost'],
									'status' => (string)$new_status,
									);

				} catch (Exception $e) {
					$msg = 'refreshBulkOrderBpostStatus';
					if (isset($order_ref['reference']))
						$msg .= ' Ref:'.$order_ref['reference'];
					self::logError($msg, $e->getMessage(), $e->getCode(), 'OrderBpost', 0);
				}
		}

		PsOrderBpost::updateBulkOrderStatus($orders_status);
	}

	public static function refreshBpostStatus($bpost = null, $reference = null)
	{
		try {
			if ($status = self::getBpostOrderStatus($bpost, $reference))
				if ($bpost_order = PsOrderBpost::getByReference($reference))
					if ((string)$status !== $bpost_order->status)
					{
						$bpost_order->status = $status;
						$bpost_order->update();
					}

		} catch (Exception $e) {
			$status = false;
			self::logError('refreshBpostStatus Ref:'.$reference, $e->getMessage(), $e->getCode(), 'OrderBpost', 0);
		}

		return $status;
	}

	/**
	 * Mimic 1.5+ order reference field for 1.4
	 *
	 * @return String
	 */
	public static function generateReference()
	{
		return Tools::strtoupper(Tools::passwdGen(9, 'NO_NUMERIC'));
	}

	public static function getSupportedLangIso($iso_code = '', $lower_case = false)
	{
		$iso_code = Tools::strtoupper($iso_code);
		$iso_code = (bool)in_array($iso_code, array('EN', 'NL', 'FR', 'DE')) ? $iso_code : 'EN';

		return $lower_case ? Tools::strtolower($iso_code) : $iso_code;
	}

	public static function getCartLangIso($id_cart = 0)
	{
		if (empty($id_cart))
			return false;

		$cart = new Cart((int)$id_cart);

		return Validate::isLoadedObject($cart) ? self::getSupportedLangIso(Language::getIsoById((int)$cart->id_lang)) : false;
	}

	public static function getBpostring($str, $max = false)
	{
		$pattern = '/[^\pL0-9,-_\.\s\'\(\)\&]/u';
		$rpl = '-';
		$str = preg_replace($pattern, $rpl, trim($str));
		$str = str_replace(array('/', '\\'), $rpl, $str);
		if (false === strpos($str, '&amp;'))
			$str = str_replace('&', '&amp;', $str);

		// Tools:: version fails miserably, so don't even...
		// return Tools::substr($str, 0, $max);
		return mb_substr($str, 0, $max ? $max : mb_strlen($str));
	}

	/**
	 * extract number, street from address1 string
	 * @param  string $address1
	 * @return array $result [nr, street]
	 */
	public static function getAddress1StreetNr($address1 = '')
	{
		$result = array();
		preg_match('#([0-9]+\w?)?[, ]*([\pL&;\'\. -]+)[, ]*([0-9]+[a-z]*)?[, ]*(.*)?#iu', $address1, $matches);
		if (!empty($matches[1]))
			$nr = $matches[1];
		elseif (!empty($matches[3]))
			$nr = $matches[3];

		$result['nr'] = isset($nr) ? $nr : '';
		$result['street'] = !empty($matches[2]) ? $matches[2] : '';

		return $result;
	}

	public function getBpostDebugLog()
	{
		return $this->bpost->getDebugLog();
	}

	public function getDebugXmlLink($key, $qr)
	{
		return $this->bpost->getDebugXmlLink($key, $qr);
	}

	/**
	 * @param int $type
	 * @param string|false $delivery_date dd-mm-YYYY
	 * @return array
	 */
	public function getNearestValidServicePoint($type = BpostShm::PPT_ALL, $delivery_date = false)
	{
		$delivery_address = new Address($this->context->cart->id_address_delivery, $this->context->language->id);
		$iso_country = Tools::strtoupper(Country::getIsoById($delivery_address->id_country));
		$result = array(
			'city' => $delivery_address->city,
			'postcode' =>  $delivery_address->postcode,
		);

		$try_zones = array(
			2 => $delivery_address->postcode.' '.$delivery_address->city,
			1 => $delivery_address->postcode,
			0 => '1000', // last resort brussels
		);

		$search_params = array(
			'street' 	=> '',
			'nr' 		=> '',
			'country'	=> $iso_country,
		);

		// srg 12-sep-2021 (BE) address1 integration
		if ('BE' == $iso_country)
		{
			$street_nr = static::getAddress1StreetNr((string)$delivery_address->address1);
			$search_params['street'] = $street_nr['street'];
			$search_params['nr'] = $street_nr['nr'];
		}
		// 

		if ($delivery_date) $search_params['dd'] = (string)$delivery_date;
		foreach ($try_zones as $valid_key => $zone)
		{
			$search_params['zone'] = $zone;
			$service_points = $this->getNearestServicePoint($search_params, $type);
			if (!empty($service_points))
				break;

		}
		$result['is_valid'] = (bool)$valid_key;
		$result['servicePoints'] = $service_points;
		// srg 12-sep-2021 ...
		if ('BE' == $iso_country)
			$result['address1'] = $street_nr['street'].(!empty($street_nr['nr']) ? ' '.$street_nr['nr'] : '');
		//

		return $result;
	}

	/**
	 * @param array $search_params
	 * @param int $type
	 * @return array
	 */
	public function getNearestServicePoint($search_params = array(), $type = BpostShm::PPT_ALL)
	{
		$limit = 20;
		$service_points = array();

		$search_params = array_merge(array(
				'street' 	=> '',
				'nr' 		=> '',
				'zone'		=> '',
			'country' => '',
				'dd'		=> null,
			), $search_params);

		try {
			if ($response = $this->geo6->getNearestServicePoint($search_params['street'], $search_params['nr'], $search_params['zone'],
				$search_params['country'],
				$this->context->language->iso_code, $type, $limit, $search_params['dd']))
			{
				foreach ($response as $row)
				{
					$service_points['coords'][] = array(
						$row['poi']->getLatitude(),
						$row['poi']->getLongitude(),
					);
					$service_points['list'][] = array(
						'id' 			=> $row['poi']->getId(),
						'type' 			=> $row['poi']->getType(),
						'office' 		=> $row['poi']->getOffice(),
						'street' 		=> $row['poi']->getstreet(),
						'nr' 			=> $row['poi']->getNr(),
						'zip' 			=> $row['poi']->getZip(),
						'city' 			=> $row['poi']->getCity(),
					);
					$service_points['distance'] = $row['distance'];
				}
			}
		} catch (EontechModException $e) {
			$service_points = array();
		}

		return $service_points;
	}

	/**
	 * Country specific adapter function
	 * @param int $service_point_id
	 * @param int $type
	 * @return array
	 */
	public function getCountryServicePointDetails($service_point_id = 0, $type = BpostShm::PPT_ALL, $id_cart = 0)
	{
		$cart = empty($id_cart) ? $this->context->cart : new Cart($id_cart);
		// $delivery_address = new Address($this->context->cart->id_address_delivery, $this->context->language->id);
		$delivery_address = new Address($cart->id_address_delivery, $cart->id_lang);
		$country = Tools::strtoupper(Country::getIsoById($delivery_address->id_country));
		$language = $this->context->language->iso_code;
		
		return $this->geo6->getServicePointDetails($service_point_id, $language, $country, $type);
	}

	/**
	 * @param int $service_point_id
	 * @param int $type
	 * @return array
	 */
	public function getServicePointDetails($service_point_id = 0, $type = BpostShm::PPT_ALL, $id_cart = 0)
	{
		$service_point_details = array();
		try {
			if ($poi = $this->getCountryServicePointDetails($service_point_id, $type, $id_cart))
			{
				$service_point_details['id'] 		= $poi->getId();
				$service_point_details['office'] 	= $poi->getOffice();
				$service_point_details['street'] 	= $poi->getStreet();
				$service_point_details['nr'] 		= $poi->getNr();
				$service_point_details['zip'] 		= $poi->getZip();
				$service_point_details['city'] 		= $poi->getCity();
				$service_point_details['country'] 	= $poi->getCountry();
			}
		} catch (EontechModException $e) {
			$service_point_details = array();
			/*if (2 === $type)
				$service_point_details = $this->getServicePointDetails($service_point_id, 1, $id_cart);*/
			if (bpostshm::PPT_POST_POINT === $type)
				$service_point_details = $this->getServicePointDetails($service_point_id, bpostshm::PPT_POST_OFFICE, $id_cart);

		}

		return $service_point_details;
	}

	/**
	 * @param int $service_point_id
	 * @param int $type
	 * @return array
	 */
	public function getServicePointHours($service_point_id = 0, $type = BpostShm::PPT_ALL)
	{
		$service_point_hours = array();

		try {
			if ($response = $this->getCountryServicePointDetails($service_point_id, $type))
				if ($service_point_days = $response->getHours())
					foreach ($service_point_days as $day)
						$service_point_hours[$day->getDay()] = array(
							'am_open' => $day->getAmOpen(),
							'am_close' => $day->getAmClose(),
							'pm_open' => $day->getPmOpen(),
							'pm_close' => $day->getPmClose(),
						);
		} catch (EontechModException $e) {
			$service_point_hours = array();
		}

		return $service_point_hours;
	}

	/**
	 * extract number, street and line2 from address fields
	 * @author Serge <serge@stigmi.eu>
	 * @param  array $address
	 * @return array $address
	 */
	public function getAddressStreetNr($address = '')
	{
		if (empty($address) || !is_array($address))
			return false;

		$line2 = $address['line2'];
		preg_match('#([0-9]+)?[, ]*([\pL&;\'\. -]+)[, ]*([0-9]+[a-z]*)?[, ]*(.*)?#iu', $address['street'], $matches);
		if (!empty($matches[1]))
			$nr = $matches[1];
		elseif (!empty($matches[3]))
			$nr = $matches[3];
		elseif (!empty($line2) && is_numeric($line2))
		{
			$nr = $line2;
			$line2 = '';
		}

		$address['nr'] = $nr;
		$address['line2'] = !empty($matches[4]) ? $matches[4].(!empty($line2) ? ', '.$line2 : '') : $line2;
		if (!empty($matches[2]))
			$address['street'] = $matches[2];

		return $address;
	}

	/**
	 * Rearrange address fields depending on Address2! because of stingy WS 40 char max fields
	 * @author Serge <serge@stigmi.eu>
	 * @param  array $person shop or client
	 * @return array Bpost formatted shipper
	 */
	protected function getBpostShipper($person = '')
	{
		if (empty($person))
			return false;

		$address = array(
			'nr' => ',',
			'street' => $person['address1'],
			'line2' => isset($person['address2']) ? $person['address2'] : '',
			);

		$iso_code = isset($person['id_country']) ? Tools::strtoupper(Country::getIsoById($person['id_country'])) : 'BE';
		/* if ('BE' === $iso_code)
			$address = $this->getAddressStreetNr($address);
		*/

		$shipper = array(
			'name' => $person['name'],
			'company' => isset($person['company']) ? $person['company'] : '',
			'number' => $address['nr'],
			'street' => $address['street'],
			'line2' => $address['line2'],
			'postcode' => $person['postcode'],
			'locality' => $person['city'],
			'countrycode' => $iso_code,
			'phone' => $person['phone'],
			'email' => $person['email'],
			);

		return $shipper;
	}

	/**
	 * @param Order $ps_order
	 * @param bool $is_retour
	 * @param int $shm_at_home bpost address field limits require differences for @home !
	 * @return array 'sender' & 'receiver' + formatted 'recipient'
	 */
	public function getReceiverAndSender($ps_order, $is_retour = false, $shm_at_home = false)
	{
		$customer = new Customer((int)$ps_order->id_customer);
		$delivery_address = new Address($ps_order->id_address_delivery, $this->context->language->id);
		// $invoice_address = new Address($ps_order->id_address_invoice, $this->context->language->id);
		$company = self::getBpostring($delivery_address->company);
		$client_line1 = self::getBpostring($delivery_address->address1);
		$client_line2 = self::getBpostring($delivery_address->address2);

		// $shippers = array(
		// 	'client' => array(
		// 		'name'		=> $delivery_address->firstname.' '.$delivery_address->lastname,
		// 		'address1' 	=> $client_line1,
		// 		'address2' 	=> $client_line2,
		// 		'city' 		=> $delivery_address->city,
		// 		'postcode' 	=> $delivery_address->postcode,
		// 		'id_country'=> $delivery_address->id_country,
		// 		'email'		=> $customer->email,
		// 		'phone'		=> !empty($delivery_address->phone) ? $delivery_address->phone : $delivery_address->phone_mobile,
		// 	),
		// 	'shop' =>  array(
		// 		'name'		=> self::getBpostring(Configuration::get('PS_SHOP_NAME')),
		// 		'address1' 	=> Configuration::get('PS_SHOP_ADDR1'),
		// 		'address2' 	=> Configuration::get('PS_SHOP_ADDR2'),
		// 		'city' 		=> Configuration::get('PS_SHOP_CITY'),
		// 		'postcode' 	=> Configuration::get('PS_SHOP_CODE'),
		// 		'id_country'=> Configuration::get('PS_SHOP_COUNTRY_ID'),
		// 		'email' 	=> Configuration::get('PS_SHOP_EMAIL'),
		// 		'phone'		=> Configuration::get('PS_SHOP_PHONE'),
		// 	),
		// );

		// $client = $this->getBpostShipper($shippers['client']);
		$client = array(
			'name'		=> $delivery_address->firstname.' '.$delivery_address->lastname,
			'address1' 	=> $client_line1,
			'address2' 	=> $client_line2,
			'city' 		=> $delivery_address->city,
			'postcode' 	=> $delivery_address->postcode,
			'id_country'=> $delivery_address->id_country,
			'email'		=> $customer->email,
			'phone'		=> !empty($delivery_address->phone) ? $delivery_address->phone : $delivery_address->phone_mobile,
		);
		$client = $this->getBpostShipper($client);
		$recipient = $client['name'];
		if (!empty($client['line2']) && (bool)$shm_at_home)
		{
			$company = !empty($company) ? ' ('.$company.')' : '';
			$company = $client['name'].$company;
			$client['name'] = $client['line2'];
			$recipient = $company;
		}
		$client['company'] = $company;
		// $shop = $this->getBpostShipper($shippers['shop']);
		$shop = Tools::jsonDecode(Configuration::get('BPOST_STORE_DETAILS'), true);
		if (isset($shop['name']))
			$shop = $this->getBpostShipper($shop);
		else
		{
			$shop = array(
				'name'		=> self::getBpostring(Configuration::get('PS_SHOP_NAME')),
				'address1' 	=> Configuration::get('PS_SHOP_ADDR1'),
				'address2' 	=> Configuration::get('PS_SHOP_ADDR2'),
				'city' 		=> Configuration::get('PS_SHOP_CITY'),
				'postcode' 	=> Configuration::get('PS_SHOP_CODE'),
				'id_country'=> Configuration::get('PS_SHOP_COUNTRY_ID'),
				'email' 	=> Configuration::get('PS_SHOP_EMAIL'),
				'phone'		=> Configuration::get('PS_SHOP_PHONE'),
			);
			$shop = $this->getBpostShipper($shop);
		}

		$sender = $shop;
		$receiver = $client;
		if ($is_retour)
		{
			$sender = $client;
			$receiver = $shop;
		}

		// sender
		$address = new EontechModBpostOrderAddress();
		$address->setNumber(Tools::substr($sender['number'], 0, 8));
		$address->setStreetName(self::getBpostring($sender['street'], 40));
		$address->setPostalCode(self::getBpostring($sender['postcode'], 32));
		$address->setLocality(self::getBpostring($sender['locality'], 40));
		$address->setCountryCode($sender['countrycode']);

		$bpost_sender = new EontechModBpostOrderSender();
		$bpost_sender->setAddress($address);
		$bpost_sender->setName(self::getBpostring($sender['name'], 40));
		if (!empty($sender['company']))
			$bpost_sender->setCompany(self::getBpostring($sender['company'], 40));
		$sender_phone = Tools::substr($sender['phone'], 0, 20);
		if (!(empty($sender_phone)))
			$bpost_sender->setPhoneNumber($sender_phone);
		// $bpost_sender->setEmailAddress(Tools::substr($sender['email'], 0, 50));
		$sender_email = Tools::substr($sender['email'], 0, 50);
		if (!(empty($sender_email)))
			$bpost_sender->setEmailAddress($sender_email);

		// receiver
		$address = new EontechModBpostOrderAddress();
		$address->setNumber(Tools::substr($receiver['number'], 0, 8));
		$address->setStreetName(self::getBpostring($receiver['street'], 40));
		$address->setPostalCode(self::getBpostring($receiver['postcode'], 32));
		$address->setLocality(self::getBpostring($receiver['locality'], 40));
		$address->setCountryCode($receiver['countrycode']);

		$bpost_receiver = new EontechModBpostOrderReceiver();
		$bpost_receiver->setAddress($address);
		$bpost_receiver->setName(self::getBpostring($receiver['name'], 40));
		if (!empty($receiver['company']))
			$bpost_receiver->setCompany(self::getBpostring($receiver['company'], 40));
		$receiver_phone = Tools::substr($receiver['phone'], 0, 20);
		if (!(empty($receiver_phone)))
			$bpost_receiver->setPhoneNumber($receiver_phone);
		// $bpost_receiver->setEmailAddress(Tools::substr($receiver['email'], 0, 50));
		$receiver_email = Tools::substr($receiver['email'], 0, 50);
		if (!(empty($receiver_email)))
			$bpost_receiver->setEmailAddress($receiver_email);

		// recipient continued (* only when not retour *)
		if (false === $is_retour)
		{
			$nb = $address->getNumber();
			$country_code = Tools::strtoupper($address->getCountryCode());
			// $nb_part = is_numeric($nb) ? ' '.$nb : '';
			$nb_part = 'BE' === $country_code && ',' !== $nb ? ' '.$nb : '';
			$street2 = empty($client['line2']) ? '' : ', '.$client['line2'];
			$recipient .= ', '.$address->getStreetName().$nb_part.$street2
				.' '.$address->getPostalCode().' '.$address->getLocality().' ('.$country_code.')';
		}

		return array(
			'receiver' => $bpost_receiver,
			'sender' => $bpost_sender,
			'recipient' => html_entity_decode($recipient),
		);
	}

	/**
	 * @author Serge <serge@stigmi.eu>
	 * @param int $id_order
	 * @return boolean
	 */
	public function prepareBpostOrder($id_order = 0)
	{
		if (empty($id_order) || !is_numeric($id_order))
			return false;

		$response = true;
		$ps_order = new Order((int)$id_order);

		// create a unique reference
		$ref = $ps_order->reference;
		$reference = Configuration::get('BPOST_ACCOUNT_ID').'_'.Tools::substr($ps_order->id, 0, 42).'_'.$ref;

		// >> Srg: 2 Jul 17
		$shm = (int)$this->module->getShmFromCarrierID($ps_order->id_carrier);
		// >> Srg 6 Jul 17: throw execption if non-home cart doesn't have a valid service-point
		$cart_bpost = PsCartBpost::getByPsCartID((int)$ps_order->id_cart);
		switch ($shm)
		{
			case BpostShm::SHM_HOME:
				// service point type & id are no longer valid
				$cart_bpost->reset();
				break;

			default:
				if (! $cart_bpost->validServicePointForSHM($shm))
					throw new PrestaShopException('Error: No service point, No order, friend');
		}
		// << Srg

		$delivery_address = new Address((int)$ps_order->id_address_delivery);
		$iso_country = Tools::strtoupper(Country::getIsoById($delivery_address->id_country));
		$intl = 'BE' !== $iso_country;
		$shm |= (int)$intl * BpostShm::SHM_IMASK;
		// >> Srg: 4 may 21
		// if (! self::isoInEU($iso_country))
		// 	$shm |= BpostShm::SHM_IMASK_EAD;
		// 
		// $dm_text = $this->getDeliveryMethodSlug($shm, $intl);
		$dm_text = $this->getDbShmSlug($shm);
		// << Srg
		$dates = $this->getDropDeliveryDates($shm, $ps_order);
		$options_keys = $this->getEffectiveDeliveryOptions($shm, $ps_order->total_products, $dates['sat']);

		//
		// >> Srg: 12 may 21
		// needed to be deferred till after "$option_keys"
		// as nonEu shm isn't included in deliveryOptions
		if (! self::isoInEU($iso_country))
			$shm |= BpostShm::SHM_IMASK_EAD;
		// $weight = 0;
		// $pb_service = new EontechProductBoxService($ps_order->getProducts());
		// $pbx = $pb_service->getProductBoxes();
		$ops = EontechOrderParcelService::fromOrder($ps_order);
		$ops->setParcels();
		$label_count = $ops->isValid() ? (int)$ops->getNumParcels() : 1;
		//
		if ((bool)Configuration::get('BPOST_USE_PS_LABELS'))
		{
			// Labels managed are within Prestashop
			$shippers = $this->getReceiverAndSender($ps_order, false, self::isAtHome($shm));
			$recipient = $shippers['recipient'];

			$order_bpost = new PsOrderBpost();
			$order_bpost->reference = (string)$reference;
			$order_bpost->recipient = (string)$recipient;
			$order_bpost->shm = (int)$shm;
			// set drop date if applicable
			if ($dates['drop'])
				$order_bpost->dt_drop = (int)$dates['drop'];

			$order_bpost->delivery_method = (string)$this->getDeliveryMethodString($dm_text, $options_keys);
			$response = $response && $order_bpost->save();
			$response = $response && $order_bpost->addLabels($label_count);
		}
		else
		{
			// Send order for SHM only processing
			$bpost_order = new EontechModBpostOrder($reference);
			foreach ($ops->getProductLines() as $pline)
			{
				$line = new EontechModBpostOrderLine(self::getBpostring($pline['name']), $pline['quantity']);
				$bpost_order->addLine($line);
			}
			
			try {

				for ($n_boxes = $label_count; (bool)$n_boxes; $n_boxes--)
				{
					$box = $this->createBox($reference, $shm, $ops);
					$bpost_order->addBox($box);
				}
			
				$response = $this->bpost->createOrReplaceOrder($bpost_order);

			} catch (Exception $e) {
				self::logError('prepareBpostOrder Ref: '.$reference, $e->getMessage(), $e->getCode(), 'Order', $id_order);
				$response = false;

			}
		}

		return $response;
	}

	/**
	 * @param string $reference Bpost order reference
	 * @param int $db_shm Shipping method (regular 3 + other bits for international)
	 * @param OrderParcelService $ops
	 * @param bool $is_retour if True create a retour box
	 * @return EontechModBpostOrderBox
	 */
	public function createBox($reference = '',
		$db_shm = 0,
		$ops = null,
		$is_retour = false)
	{
		if (empty($reference) || empty($db_shm) || !isset($ops))
			return false;

		$ps_order = $ops->getOrder();
		$id_cart = (int)$ps_order->id_cart;
		$intl = self::isInternational($db_shm);
		$shipping_method = (int)self::getActualShm($db_shm);
		$has_service_point = !self::isAtHome($shipping_method);
		if ($has_service_point)
		{
			$cart_bpost = PsCartBpost::getByPsCartID($id_cart);
			$service_point_id = (int)$cart_bpost->service_point_id;
			$sp_type = (int)$cart_bpost->sp_type;

			// >> SRG 10-11-17
			if ($is_retour)
			// if ($is_retour && !$intl)
			// << SRG
				// effective $shipping_method if retour is on is always @home!
				$shipping_method = (int)BpostShm::SHM_HOME;
		}

		$shippers = $this->getReceiverAndSender($ps_order, $is_retour, !$has_service_point);
		$sender = $shippers['sender'];
		$receiver = $shippers['receiver'];

		$box = new EontechModBpostOrderBox();
		$box->setStatus('OPEN');
		$box->setSender($sender);

		$option_keys = $this->getOrderDeliveryOptions((int)$db_shm, $ps_order);
		// >> Srg: 12-may-21
		$parcel = $ops->getNextParcel($is_retour);
		$parcel_weight = (int)$parcel['total_weight'];
		$is_non_eu = self::isNonEu($db_shm);
		if ($intl)
		{
			$customs_info = new EontechModBpostOrderBoxCustomsInfo();
			// Srg: 10-may-21
			// $parcel_value = $ps_order->total_products_wt * 100;
			// $parcel_value = round($ps_order->total_products_wt * 100, 2);
			$parcel_value = (int)$parcel['total_value'];
			// 
			$customs_info->setParcelValue($parcel_value);
			$customs_info->setContentDescription(self::getBpostring('ORDER '.Configuration::get('PS_SHOP_NAME'), 50));
			$customs_info->setShipmentType('GOODS');
			$customs_info->setParcelReturnInstructions('RTS');
			$customs_info->setPrivateAddress(false);
			if ($is_non_eu)
			{
				$pids = array_column($parcel['contents'], 'id_product');
				// Throws exceptions
				$ead_codes = $this->validEadCodes($pids);
				//
				$ead_cats = $this->getEadCategories($pids);
				if (!empty($ead_cats))
					$customs_info->setContentDescription(self::getBpostring($ead_cats, 50));
				$customs_info->setCurrency($ops->getCurrencyIso());
				$customs_info->setAmtPostagePaidByAddresse($ops->getPostagePaidPerParcel());
			}
		}
		// << Srg

		switch ($shipping_method)
		{
			case BpostShm::SHM_HOME:
				$product_tag = 'bpack '.$this->getDeliveryMethodSlug($shipping_method, $intl);
				if ($intl)
				{
					// @International
					$at_intl_home = new EontechModBpostOrderBoxAtIntlHome();
					$at_intl_home->setReceiver($receiver);
					$at_intl_home->setParcelWeight($parcel_weight);
					$at_intl_home->setCustomsInfo($customs_info);
					if ($is_non_eu)
						/// parcel contents
						foreach ($parcel['contents'] as $content)
						{
							$pc = new EontechModBpostOrderBoxParcelContent();
							$pc->setNumberOfItemType((int)$content['qty']);
							$pc->setValueOfItem((int)$content['value']);
							$pc->setItemDescription(self::getBpostring($content['name'], 30));
							$pc->setNettoWeight((int)$content['weight']);
							$pid = (int)$content['id_product'];
							// internal validation
							$hscode = (string)$ead_codes[$pid]['hscode'];
							// srg 13-dec-2021
							// $pc->setHsTariffCode($hscode);
							$pc->setHsTariffCode(preg_replace('/[^0-9]/', '', $hscode));
							//
							$origin = (string)$ead_codes[$pid]['origin'];
							$pc->setOriginOfGoods($origin);
							//
							$at_intl_home->addParcelContent($pc);
						}

					if ($is_retour)
						$at_intl_home->setProduct('bpack World Easy Return');
					else
					{
						$at_intl_home->setProduct($product_tag);
						$delivery_options = $this->getDeliveryBoxOptions($option_keys);
						foreach ($delivery_options as $option)
							$at_intl_home->addOption($option);
					}

					$box->setInternationalBox($at_intl_home);
				}
				else
				{
					// @Home
					$at_home = new EontechModBpostOrderBoxAtHome();
					$at_home->setReceiver($receiver);
					$at_home->setWeight($parcel_weight);
					if ($is_retour)
						$at_home->setProduct('bpack Easy Retour');
					else
					{
						$at_home->setProduct($product_tag);

						$delivery_options = $this->getDeliveryBoxOptions($option_keys);
						foreach ($delivery_options as $option)
							$at_home->addOption($option);
					}

					$box->setNationalBox($at_home);
				}
				break;

			case BpostShm::SHM_PPOINT:
				// @Bpost
				// Never retour
				$service_point = $this->getServicePointDetails($service_point_id, $sp_type, $id_cart);
				$iso_country = (string)$service_point['country'];
				$pugo_address_class = $intl ? 'EontechModBpostOrderIntlPugoAddress' : 'EontechModBpostOrderPugoAddress';
				$pugo_address = new $pugo_address_class(
					$service_point['street'],
					$service_point['nr'],
					null,
					$service_point['zip'],
					$service_point['city'],
					$iso_country
				);

				$at_bpost = $intl ?
					new EontechModBpostOrderBoxAtIntlPugo() :
					new EontechModBpostOrderBoxAtBpost();
				$at_bpost->setPugoId(sprintf('%06s', $service_point_id));
				$at_bpost->setPugoName(self::getBpostring($service_point['office'], 40));
				$at_bpost->setPugoAddress($pugo_address);
				if ($intl)
				{
					$at_bpost->setReceiver($receiver);
					$at_bpost->setParcelWeight($parcel_weight);
					$at_bpost->setCustomsInfo($customs_info);
				}
				else
				{
					$at_bpost->setReceiverName(Tools::substr($receiver->getName(), 0, 40));
					$at_bpost->setReceiverCompany(Tools::substr($receiver->getCompany(), 0, 40));
				}

				if ($iso_lang = self::getCartLangIso($id_cart))
					$at_bpost->addOption(
						new EontechModBpostOrderBoxOptionMessaging(
							'keepMeInformed',
							$iso_lang,
							$receiver->getEmailAddress()
						));
				$delivery_options = $this->getDeliveryBoxOptions($option_keys);
				foreach ($delivery_options as $option)
					$at_bpost->addOption($option);

				if ($intl)
					$box->setInternationalBox($at_bpost);
				else
					$box->setNationalBox($at_bpost);
				break;

			case BpostShm::SHM_PLOCKER:
				// @24/7
				// Never retour
				$service_point = $this->getServicePointDetails($service_point_id, BpostShm::SHM_PLOCKER, $id_cart);
				$parcels_depot_address = new EontechModBpostOrderParcelsDepotAddress(
					$service_point['street'],
					$service_point['nr'],
					'A',
					$service_point['zip'],
					$service_point['city'],
					'BE'
				);

				for ($i = Tools::strlen($service_point['id']); $i < 6; $i++)
					$service_point['id'] = '0'.$service_point['id'];

				$at_upl = new EontechModBpostOrderBoxAtUPL();
				$at_upl->setParcelsDepotId($service_point['id']);
				$at_upl->setParcelsDepotName(self::getBpostring($service_point['office'], 40));
				$at_upl->setParcelsDepotAddress($parcels_depot_address);
				//
				$upl_info = EontechBpostUPLInfo::createFromJson($cart_bpost->upl_info);
				$at_upl->setUnregisteredInfo($upl_info);
				$at_upl->setReceiverName(Tools::substr($receiver->getName(), 0, 40));
				$at_upl->setReceiverCompany(Tools::substr($receiver->getCompany(), 0, 40));

				$delivery_options = $this->getDeliveryBoxOptions($option_keys);
				foreach ($delivery_options as $option)
					$at_upl->addOption($option);

				$box->setNationalBox($at_upl);
				break;
		}
		// new field to insert PS version once per box, instead of once per Order!
		$additional_customer_reference = sprintf('PrestaShop_%s/V%s/D%bC%b', (string)_PS_VERSION_,
				(string)$this->module->version,
				(int)Configuration::get('BPOST_DISPLAY_DELIVERY_DATE'),
				(int)Configuration::get('BPOST_CHOOSE_DELIVERY_DATE'));
		$box->setAdditionalCustomerReference((string)$additional_customer_reference);

		return $box;
	}

	/**
	 * @param object $order_bpost instance
	 * @return array links to printed labels
	 */
	public function printOrderLabels($order_bpost)
	{
		$links = array();

		// if (true) // trace error
		if (!Validate::isLoadedObject($order_bpost))
		{
			$links['error']['dev'][] = 'invalid bpost order '.(int)$order_bpost->id;
			return $links;
		}

		$errors = array();
		$reference = $order_bpost->reference;
		try {
			// Srg: 25-apr-19
			$pdf_manager = new EontechPdfManager($this->module->name, 'pdf', true);
			$pdf_manager->setActiveFolder($reference);

			// >> Srg: 16-aug-17
			//// $db_shm = $order_bpost->shm;
			// No retour for Intl PUGO
			$db_shm = (int)$order_bpost->shm;
			// SRG >>: 29-09-17
			/*if (BpostShm::SHM_PPI === $db_shm &&
				Configuration::get('BPOST_AUTO_RETOUR_LABEL'))
				throw new Exception($this->module->l('Cannot print Intl PUGO return / auto-return labels'));*/
			// << Srg
			$is_intl = self::isInternational($db_shm);
			// get all unprinted labels
			$ps_labels = $order_bpost->getNewLabels();
			if (count($ps_labels))
			{
				$ps_order = PsOrderBpost::getPsOrderByReference($reference);
				$bpost_order = new EontechModBpostOrder($reference);
				$ops = EontechOrderParcelService::fromOrder($ps_order, true);
				foreach ($ops->getProductLines() as $pline)
				{
					$line = new EontechModBpostOrderLine(self::getBpostring($pline['name']), $pline['quantity']);
					$bpost_order->addLine($line);
				}

				foreach ($ps_labels as $has_retour => $cur_labels)
				{
					// reset boxes
					$bpost_order->setBoxes(array());
					///
					$num_labels = 0;
					foreach ($cur_labels as $label)
						$num_labels += $label->is_retour ? 0 : 1;
					$ops->setParcels($num_labels);
					///
					foreach ($cur_labels as $label_bpost)
					{
						$is_retour = (bool)$label_bpost->is_retour;
						$box = $this->createBox($reference, $db_shm, $ops, $is_retour);
						$bpost_order->addBox($box);
					}

					$this->bpost->createOrReplaceOrder($bpost_order);

					$bcc = new EontechBarcodeCollection();
					$bpost_labels_returned = $this->createLabelForOrder($reference, (bool)$has_retour);
					// save the labels and record the barcodes
					foreach ($bpost_labels_returned as $bpost_label)
					{
						$bcc->addBarcodes($bpost_label->getBarcodes());
						$pdf_manager->writePdf($bpost_label->getBytes());
					}
					// set local label barcodes
					foreach ($cur_labels as $label_bpost)
					{
						$is_retour = (bool)$label_bpost->is_retour;
						if ($has_retour)
						{
							$barcodes = $bcc->getNextAutoReturn($is_intl);
							$label_bpost->barcode = $barcodes[EontechBarcodeCollection::TYPE_NORMAL];
							$label_bpost->barcode_retour = $barcodes[EontechBarcodeCollection::TYPE_RETURN];
						}
						else
							$label_bpost->barcode = $bcc->getNext($is_retour, $is_intl);

						$label_bpost->status = 'PRINTED';
						$label_bpost->save();
					}
				}
			}

			// Srg 3-8-16: Auto treat?
			if (!(bool)$order_bpost->treated &&
				empty($errors) &&
				(bool)Configuration::get('BPOST_TREAT_PRINTED_ORDER'))
				$this->module->changeOrderState($reference, 'Treated');
			//

		} catch (Exception $e) {
			$errors[] = $e->getMessage();
		}

		//
		if (!empty($errors))
		{
			$links['error'][$reference] = $errors;
			return $links;
		}
		//

		return $pdf_manager->getLinks();
	}

	/**
	 * @param null|string $reference
	 * @return bool
	 */
	public function createLabelForOrder($reference = null, $with_return_labels = false)
	{
		$response = false;

		if (!is_null($reference))
		{
			$reference = Tools::substr($reference, 0, 50);
			$format = Configuration::get('BPOST_LABEL_PDF_FORMAT');

			try {
				$response = $this->bpost->createLabelForOrder($reference, $format, $with_return_labels, true);
			} catch (EontechModException $e) {
				$response = false;
			}

		}

		return $response;
	}

	/**
	 * @param null|string $barcode
	 * @return bool
	 */
	public function createLabelForBox($barcode = null, $with_return_labels = false)
	{
		$response = false;

		if (!is_null($barcode))
		{
			$format = Configuration::get('BPOST_LABEL_PDF_FORMAT');

			try {
				$response = $this->bpost->createLabelForBox($barcode, $format, $with_return_labels, true);
			} catch (EontechModException $e) {
				$response = false;
			}

		}

		return $response;
	}

	/**
	 * hide OOS products ?
	 * for drop & delivery date calculation
	 * @param  object $cartOrOrder either a PS Cart or Order object with a public getProducts method
	 * @return bool  yes or no
	 */
	public function hidingOOS($ps_cart_or_order)
	{
		if ($hide_oos = (bool)Configuration::get('BPOST_HIDE_DATE_OOS'))
			if (Validate::isLoadedObject($ps_cart_or_order) && method_exists($ps_cart_or_order, 'getProducts'))
			{
				$one_oos = false;
				$products = $ps_cart_or_order->getProducts();
				foreach ($products as $product)
					if ($one_oos = ($one_oos ||
						(isset($product['quantity_available']) && $product['quantity_available'] <= 0) ||
						(isset($product['product_quantity']) && $product['product_quantity'] <= 0)))
						break;
				$hide_oos = $one_oos;
			}

		return (bool)$hide_oos;
	}

	private static function logError($func, $msg, $err_code, $obj, $obj_id)
	{
		$msg_format = 'BpostSHM::Service: '.$func.' - '.$msg;
		Logger::addLog(
			$msg_format,
			3,
			$err_code,
			$obj,
			(int)$obj_id,
			true
		);
	}

	private function getDeliveryMethodSlug($dm, $intl = false)
	{
		$dm = (int)$dm & (BpostShm::SHM_IMASK - 1);
		switch ($dm)
		{
			case BpostShm::SHM_HOME:
				$key = $intl ? 'BPOST_INTERNATIONAL_DELIVERY' : 'BPOST_HOME_24H_BUSINESS';
				$setting = (int)Configuration::get($key);
				$slug = self::$_extended_slugs[$dm][$intl][$setting];
				break;

			case BpostShm::SHM_PPOINT:
			case BpostShm::SHM_PLOCKER:
				$slug = self::$_extended_slugs[$dm][$intl];
				break;

			default:
				$slug = 'ERR:unspecified';
		}

		return $slug;
	}

	private function getDbShmSlug($db_shm)
	{
		$intl = (bool)self::isInternational($db_shm);
		$dm = (int)$db_shm & (BpostShm::SHM_IMASK - 1);
		switch ($dm)
		{
			case BpostShm::SHM_HOME:
				$key = $intl ? 'BPOST_INTERNATIONAL_DELIVERY' : 'BPOST_HOME_24H_BUSINESS';
				$setting = (int)Configuration::get($key);
				$slug = self::$_extended_slugs[$dm][$intl][$setting];
				break;

			case BpostShm::SHM_PPOINT:
			case BpostShm::SHM_PLOCKER:
				$slug = self::$_extended_slugs[$dm][$intl];
				break;

			default:
				$slug = 'ERR:unspecified';
		}

		return $slug;
	}

	private function getDeliveryMethodString($dm_text, $dm_options)
	{
		$dms = (string)$dm_text;
		if (!empty($dm_options))
			$dms .= ':'.implode('|', $dm_options);

		return $dms;
	}

	/**
	 * [getEffectiveDeliveryOptions list of options triggered by order products total]
	 * @author Serge <serge@stigmi.eu>
	 * @param  int $shm shipping method
	 * @param  float $total_products 	(order total before shipping & tax)
	 * @param  bool $sat_delivery  		is delivery date a Saturday ?
	 * @return array	option keys
	 */
	private function getEffectiveDeliveryOptions($shm, $total_products, $sat_delivery = false)
	{
		$opts = array();
		if ($options_list = Configuration::get('BPOST_DELIVERY_OPTIONS_LIST'))
		{
			$options_list = Tools::jsonDecode($options_list, true);
			if (isset($options_list[$shm]))
				foreach ($options_list[$shm] as $key => $from)
				{
					if (is_array($from))
					{
						if (!$sat_delivery)	continue;
						$from = $from[0];
					}
					if ((float)$from <= (float)$total_products)
						$opts[] = $key;
				}

		}

		return $opts;
	}

	/**
	 * [getOrderDeliveryOptions list of options stored | triggered by order]
	 * @author Serge <serge@stigmi.eu>
	 * @param  int $shm 		shipping method
	 * @param  Order $ps_order 	Prestashop order
	 * @return array			option keys
	 */
	private function getOrderDeliveryOptions($shm, $ps_order)
	{
		$opts = array();
		if (Validate::isLoadedObject($ps_order))
			if ($bpost_order = PsOrderBpost::getByPsOrderID($ps_order->id))
			{
				if (isset($bpost_order->delivery_method))
				{
					$delivery_method = explode(':', $bpost_order->delivery_method);
					if (count($delivery_method) > 1)
						$opts = explode('|', $delivery_method[1]);
				}
			}
			else
			{
				$dates = $this->getDropDeliveryDates($shm, $ps_order);
				$opts = $this->getEffectiveDeliveryOptions($shm, $ps_order->total_products, $dates['sat']);
			}

		return $opts;
	}

	private function getDropDeliveryDates($shm, $ps_order)
	{
		$dates = array(
			'drop' => 0,
			'delivery' => 0,
			'sat' => false,
			);
		// if (BpostShm::SHM_INTL !== (int)$shm &&
		if ((int)$shm <= BpostShm::SHM_IMASK &&
			Configuration::get('BPOST_DISPLAY_DELIVERY_DATE') &&
			Validate::isLoadedObject($ps_order) &&
			!$this->hidingOOS($ps_order))
		{
			$cart_bpost = PsCartBpost::getByPsCartID((int)$ps_order->id_cart);
			if ($delivery = $cart_bpost->getDeliveryCode($shm))
			{
				$dt_delivery = $dates['delivery'] = (int)$delivery['date'];
				$date_service = new EontechDateService();
				$dates['sat'] = (bool)$date_service->isSaturday($dt_delivery);
				$dates['drop'] = (int)$date_service->getDropDate($dt_delivery);
			}
		}

		return $dates;
	}

	/**
	 * [getDeliveryBoxOptions provide delivery options in optimal order]
	 * @param  array $option_keys  numeric order 
	 * @return array option xml classes in effctive optimal order '330|350(540)|300|470'
	 */
	private function getDeliveryBoxOptions($option_keys = '')
	{
		$options = array();
		if (!empty($option_keys) && is_array($option_keys))
		{
			$sequence = array(330, 350, 540, 300, 470);
			foreach ($sequence as $key)
				if (in_array($key, $option_keys))
					switch ($key)
					{
						case 300: // Signature has to be at the end !?
							$options[] = new EontechModBpostOrderBoxOptionSigned();
							break;

						case 330: // 2nd Presentation
							$options[] = new EontechModBpostOrderBoxOptionAutomaticSecondPresentation();
							break;

						case 350:
						case 540: // Insurance
							$options[] = new EontechModBpostOrderBoxOptionInsured('basicInsurance');
							break;

						case 470: // Saturday delivery
							$options[] = new EontechModBpostOrderBoxOptionSaturdayDelivery();
							break;

						// default:
						// 	throw new Exception('Not a valid delivery option');
							// break;
					}
		}

		return $options;
	}

	/**
	 * @author Serge <serge@stigmi.eu>
	 * @param  int 			$shm shipping method
	 * @param  float|false 	$total_cart (cart total before shipping & tax)
	 * @return mixed 		setting options array | false
	 */
	public function getSaturdayDeliveryOption($shm, $total_cart = false)
	{
		$return = false;
		if ($options_list = Configuration::get('BPOST_DELIVERY_OPTIONS_LIST'))
		{
			$options_list = Tools::jsonDecode($options_list, true);
			if (isset($options_list[$shm]))
				// foreach ($options_list[$shm] as $key => $option)
				foreach ($options_list[$shm] as $option)
					if (is_array($option) &&
						(false === $total_cart ||
						(float)$option[0] <= (float)$total_cart))
							$return = array(
								'from' => $option[0],
								'cost' => $option[1],
								);

		}

		return $return;
	}

	public function getDeliveryOptions($selection)
	{
		return $this->module->getDeliveryOptions($selection);
	}

	/**
	 * getBpostLink universal getModuleLink for this module
	 * @param  array $params 		request params
	 * @param  string $controller 	name
	 * @return string             	Module front controller link
	 */
/*	public function getBpostLink(array $params = array(), $controller = 'clientbpost', $ssl = null, $id_lang = null, $id_shop = null)
	{
		$ssl = true;
		if (isset($params['mode']))
		{
			$controller = str_replace('-', '', $params['mode']);
			unset($params['mode']);
		}

		return $this->context->link->getModuleLink($this->module->name, $controller, $params, $ssl, $id_lang, $id_shop);
	}
*/
	public function getSupportedDeliveryMethods($iso_country = 'BE')
	{
		$supported = array();
		if ('BE' === (string)$iso_country)
			$supported = array(
				BpostShm::SHM_HOME,
				BpostShm::SHM_PPOINT,
				BpostShm::SHM_PLOCKER,
			);
		else
		{
			$intl_countries = $this->getEnabledIntlCountries();
			if (in_array($iso_country, $intl_countries[BpostShm::SHM_HOME]))
				$supported[] = BpostShm::SHM_HOME;
			if (in_array($iso_country, $intl_countries[BpostShm::SHM_PPOINT]))
				$supported[] = BpostShm::SHM_PPOINT;
		}

		return $supported;
	}

	public function getEnabledIntlCountries()
	{
		$enabled_countries = array(
			BpostShm::SHM_HOME => array(),
			BpostShm::SHM_PPOINT => array(),
		);
		if ((bool)($intl_countries = Configuration::get('BPOST_INTL_COUNTRIES')))
			// Srg 15-dec-2021
			// return array_replace($enabled_countries, Tools::jsonDecode($intl_countries, true));
			return array_replace_recursive(Tools::jsonDecode($intl_countries, true), $enabled_countries);
			//

		try {
			$config = $this->bpost->getProductConfig();
			if (isset($config['countries']['intl']))
				$enabled_countries[BpostShm::SHM_HOME] = $config['countries']['intl'];
			if (isset($config['countries']['ppi']))
				$enabled_countries[BpostShm::SHM_PPOINT] = $config['countries']['ppi'];
			Configuration::updateValue('BPOST_INTL_COUNTRIES', Tools::jsonEncode($enabled_countries));

		} catch (Exception $e) {
			unset($e);
		}

		return $enabled_countries;
	}

	/**
	 * required shm product configuration
	 * @return assoc array
	 */
	public function getProductConfig()
	{
		$product_countries_list = 'BE';
		$config = array();
		try {
			$config = $this->bpost->getProductConfig();
			$countries = $config['countries'];
			if (! empty($countries['intl']))
				$product_countries_list = implode('|', $countries['intl']);

			$config['countries']['intl'] = $this->explodeCountryList($product_countries_list);
			if (! empty($countries['ppi']))
				$config['countries']['ppi'] = $this->explodeCountryList(
					implode('|', $countries['ppi'])
				);

		} catch (Exception $e) {
			$config['Error'] = $e->getMessage();

		}

		return $config;
	}

	/**
	 * get full list bpost enabled countries
	 * @return assoc array
	 */
	public function getProductCountries()
	{
		$product_countries_list = 'BE';

		try {
			if ($product_countries = $this->bpost->getProductCountries())
				$product_countries_list = implode('|', $product_countries);

		} catch (Exception $e) {
			return array('Error' => $e->getMessage());

		}

		return $this->explodeCountryList($product_countries_list);
	}

	/**
	 * [explodeCountryList]
	 * @param  string $iso_list delimited list of iso country codes
	 * @param  string $glue     delimiter
	 * @return array            assoc array of ps_countries [iso => name]
	 */
	protected function explodeCountryList($iso_list, $glue = '|')
	{
		$iso_list = str_replace($glue, "','", pSQL($iso_list));
		$query = '
SELECT
	c.id_country as id, c.iso_code as iso, cl.name
FROM
	`'._DB_PREFIX_.'country` c, `'._DB_PREFIX_.'country_lang` cl
WHERE
	cl.id_lang = '.(int)$this->context->language->id.'
AND
	c.id_country = cl.id_country
AND
	c.iso_code in (\''.$iso_list.'\')
ORDER BY
	name
		';

		$countries = array();
		try {
			$db = Db::getInstance(_PS_USE_SQL_SLAVE_);
			if ($results = $db->ExecuteS($query))
				foreach ($results as $row)
					$countries[$row['iso']] = $row['name'];

		} catch (Exception $e) {
			$countries = array();
		}

		return array_filter($countries);
	}

	protected function validEadCodes($product_ids)
	{
		$ead_config = Configuration::get('BPOST_INTL_EAD');
		if (empty($ead_config))
			throw new Exception($this->module->l('Missing international non-EU settings'));

		// anomaly
		if (empty($product_ids))
			return array('error' => 'no products');

		$ead_config = Tools::jsonDecode($ead_config, true);
		$ead_codes = array();
		$feature_ids = array();
		$common_codes = array();

		foreach (['hscode', 'origin'] as $key)
		{
			$common_codes[$key] = $ead_config[$key]['code'];
			if ((int)$ead_config[$key]['id'] > 0)
				$feature_ids[$ead_config[$key]['id']] = $key;
		}
				

		$product_features = array();
		if (!empty($feature_ids))
		{
			$sql_fids = implode(',', array_keys($feature_ids));
			$sql_pids = implode(',', $product_ids);
			$pf_result = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
				SELECT pf.`id_product`, pf.`id_feature`, `value`
	            FROM `'._DB_PREFIX_.'feature_product` pf
	            LEFT JOIN `'._DB_PREFIX_.'feature_value_lang` fvl ON (fvl.`id_feature_value` = pf.`id_feature_value` AND fvl.`id_lang` = '.(int)$this->context->language->id.')
	            WHERE pf.`id_product` in ('.pSQL($sql_pids).')
	            AND pf.`id_feature` in ('.pSQL($sql_fids).')
	            ORDER BY pf.`id_product` ASC');
	            	
	        foreach ($pf_result as $row)
	        	$product_features[$row['id_product']][$row['id_feature']] = $row['value'];
		}
		
		foreach ($product_ids as $pid)
		{
			$pid = (int)$pid;
			foreach ($common_codes as $name => $code)
				$ead_codes[$pid][$name] = (string)$code;

			if (isset($product_features[$pid]))
			{
				$pf = $product_features[$pid];
				foreach ($feature_ids as $fid => $name)
					if (isset($pf[$fid]))
						$ead_codes[$pid][$name] = (string)$pf[$fid];

			}
		}

		return $ead_codes;
	}

	protected function getEadCategories($product_ids)
	{
		if (empty($product_ids))
			return false;

		$pids = implode(',', $product_ids);
		$cats = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
			SELECT DISTINCT cl.`name` FROM `'._DB_PREFIX_.'category_product` cp
			LEFT JOIN `'._DB_PREFIX_.'category` c ON (c.id_category = cp.id_category)
			LEFT JOIN `'._DB_PREFIX_.'category_lang` cl ON (cp.`id_category` = cl.`id_category`'.Shop::addSqlRestrictionOnLang('cl').')
			'.Shop::addSqlAssociation('category', 'c').'
			WHERE cp.`id_product` IN ('.pSQL($pids).')
			AND cl.`id_lang` = '.(int)$this->context->language->id.'
			ORDER BY cp.`id_category` DESC
			LIMIT 2
		');

		return implode(', ', array_column($cats, 'name'));
	}
}