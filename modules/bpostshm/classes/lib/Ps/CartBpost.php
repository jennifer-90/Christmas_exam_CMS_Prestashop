<?php
/**
* cart_bpost table encapsulation class
*  
* @author    Serge <serge@stigmi.eu>
* @version   0.5.0
* @copyright Copyright (c), Eontech.net. All rights reserved.
* @license   BSD License
*/

class PsCartBpost extends ObjectModel
{

	/** @var integer */
	public $id_cart_bpost;

	/** @var integer ps_cart id */
	public $id_cart;

	/* delivery_int bigint(15) */
	// public $delivery_int = 0;

	/* int service point choice id */
	public $service_point_id = 0;

	/* int service point type @bpost(1 or 2) @247(4) */
	public $sp_type = 0;

	/* int keep me informed choice value (default 0 => email) */
	public $option_kmi = 0;

	/** @var string delivery codes */
	public $delivery_codes = '0,0,0';

	/* json encoded unregistered parcel locker customer info */
	public $upl_info;

	/* json encoded parcel locker customer info */
	public $bpack247_customer;

	/* may not need dates */
	/** @var string Object creation date */
	public $date_add;

	/** @var string Object last modification date */
	public $date_upd;

	/* delivery_int mask
	 * cent-shm-day-date
	 * 00000-0-0-00000000
	 */
	// protected static $dmask = array(
	// 	'shm' => 10000000000,	/* 10z */
	// 	'day' => 1000000000,	/* 9z  */
	// 	'date' => 100000000,	/* 8z  */
	// );
	protected $delivery_cache = null;

	protected static $mask_date = 100000000;
	protected static $delivery_keys = array(1, 2, 4);

	/**
	 * @see 1.5+ ObjectModel::$definition
	 */
	public static $definition;

	/**
	 * @see 1.4 ObjectModel->$table
	 *      	ObjectModel->$identifier
	 * @see 1.4 ObjectModel->$fieldsRequired
	 *      	ObjectModel->$fieldsValidate
	 */
	protected $table = 'cart_bpost';
	protected $identifier = 'id_cart_bpost';
	protected $fieldsRequired = array('id_cart');
	protected $fieldsValidate = array(
		'id_cart' => 			'isUnsignedId',
		// 'delivery_int' => 		'isInt',
		'service_point_id' =>	'isUnsignedId',
		'sp_type' =>			'isUnsignedId',
		'option_kmi' =>			'isUnsignedId',
		'delivery_codes' =>		'isString',
		'upl_info' =>			'isString',
		'bpack247_customer' =>	'isString',
		);

	/**
	 * @see  ObjectModel::$webserviceParameters
	 */
	protected $webserviceParameters = array(
		'fields' => array(
			'id_cart' => array('required' => true, 'xlink_resource'=> 'cart'),
			),
	);

	/* 1.4 db save required */
	public function getFields()
	{
		parent::validateFields();

		$fields['id_cart'] = 			(int)$this->id_cart;
		// $fields['delivery_int'] = 		(int)$this->delivery_int;
		$fields['service_point_id'] =	(int)$this->service_point_id;
		$fields['sp_type'] = 			(int)$this->sp_type;
		$fields['option_kmi'] = 		(int)$this->option_kmi;
		$fields['delivery_codes'] = 	pSQL($this->delivery_codes);
		$fields['upl_info'] = 			pSQL($this->upl_info);
		$fields['bpack247_customer'] = 	pSQL($this->bpack247_customer);
		$fields['date_add'] = 			pSQL($this->date_add);
		$fields['date_upd'] = 			pSQL($this->date_upd);

		return $fields;
	}

	public function __construct($id = null, $id_lang = null)
	{
		// 1.4 is retarded to the max
		if (version_compare(_PS_VERSION_, '1.5', '>='))
		{
			self::$definition = array(
				'table' => 'cart_bpost',
				'primary' => 'id_cart_bpost',
				'fields' => array(
					'id_cart' =>			array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
					// 'delivery_int' =>		array('type' => self::TYPE_INT, 'validate' => 'isInt'),
					'service_point_id' =>	array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
					'sp_type' =>			array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
					'option_kmi' =>			array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
					'delivery_codes' =>		array('type' => self::TYPE_STRING, 'validate' => 'isString'),
					'upl_info' =>			array('type' => self::TYPE_STRING, 'validate' => 'isString'),
					'bpack247_customer' =>	array('type' => self::TYPE_STRING, 'validate' => 'isString'),
					'date_add' =>			array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
					'date_upd' =>			array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
					),
				);
		}

		parent::__construct($id, $id_lang);
	}

	/**
	 * Save current object to database (add or update)
	 *
	 * @param bool $null_values
	 * @param bool $autodate
	 * @return boolean Insertion result
	 */
	public function save($null_values = true, $autodate = true)
	{
		return parent::save($null_values, $autodate);
	}

	public function update($null_values = true)
	{
		return parent::update($null_values);
	}

	/**
	 * Get prestashop order using reference
	 * 
	 * @param int $ps_cart_id
	 * @return PsCartBpost Order
	 */
	public static function getByPsCartID($ps_cart_id)
	{
		$result = Db::getInstance()->getRow('
		SELECT `id_cart_bpost`
		FROM `'._DB_PREFIX_.'cart_bpost`
		WHERE id_cart = '.(int)$ps_cart_id);

		if (isset($result['id_cart_bpost']))
			return new PsCartBpost((int)$result['id_cart_bpost']);

		$cart_bpost = new PsCartBpost();
		$cart_bpost->id_cart = (int)$ps_cart_id;
		$cart_bpost->save();

		return $cart_bpost;
	}

	public function reset($inc_dcodes = false)
	{
		$result = (bool)$this->setServicePoint(0, 0);
		if ($inc_dcodes)
			$result = $result && $this->resetDeliveryCodes();

		return $result;
	}

	public function setServicePoint($id = 0, $type = 0)
	{
		if (!is_numeric($id) || !is_numeric($type))
			return false;

		$this->service_point_id = $id;
		$this->sp_type = $type;
		return $this->update();
	}

	public function validServicePointForSHM($shipping_method = 0)
	{
		$valid = (bool)$this->service_point_id;
		switch ((int)$shipping_method & (BpostShm::SHM_IMASK - 1))
		{
			case BpostShm::SHM_HOME: // @home
				$valid = true; //empty($this->service_point_id);
				break;

			case BpostShm::SHM_PPOINT: // @bpost
				// $valid &= in_array($this->sp_type, array(1, 2));
				$valid &= (bool)($this->sp_type & BpostShm::PPT_ALL);
				break;

			case BpostShm::SHM_PLOCKER: // @24/7
				$valid &= $shipping_method == $this->sp_type;
				break;
		}

		return (bool)$valid;
	}

	/*public function validServicePointForSHM($shipping_method = 0)
	{
		$valid = false;
		switch ((int)$shipping_method)
		{
			case 1: // @home
				$valid = true; //empty($this->service_point_id);
				break;

			case 2: // @bpost
				$valid = in_array($this->sp_type, array(1, 2));
				break;

			case 4: // @24/7
				$valid = $shipping_method == $this->sp_type;
				break;
		}

		return $valid;
	}*/

	/* delivery codes accessors */
	public static function intEncodeDeliveryCode($date = 0, $cents = 0)
	{
		return intval((int)$cents * self::$mask_date) + (int)$date;
	}

	public static function intDecodeDeliveryCode($delivery_code = 0)
	{
		return array(
			'cents' => $delivery_code ? intval($delivery_code / self::$mask_date) : 0,
			'date' => $delivery_code ? intval($delivery_code % self::$mask_date) : 0,
			);
	}

	public function getDeliveryCode($shm = 0)
	{
		if (is_null($this->delivery_cache))
			$this->delivery_cache = array_combine(self::$delivery_keys, explode(',', $this->delivery_codes));

		return isset($this->delivery_cache[$shm]) ? self::intDecodeDeliveryCode((int)$this->delivery_cache[$shm]) : false;
	}

	public function setDeliveryCode($shm = 0, $date = 0, $cents = 0)
	{
		if (is_null($this->delivery_cache))
			$this->delivery_cache = array_combine(self::$delivery_keys, explode(',', $this->delivery_codes));

		if (isset($this->delivery_cache[$shm]))
		{
			$delivery_code = (int)self::intEncodeDeliveryCode($date, $cents);
			if ($delivery_code !== $this->delivery_cache[$shm])
			{
				$this->delivery_cache[$shm] = $delivery_code;
				$this->delivery_codes = implode(',', array_values($this->delivery_cache));
				// $this->update();
			}
		}
	}

	public function resetDeliveryCodes()
	{
		$this->delivery_codes = '0,0,0';

		return $this->update();
	}
/*
	public static function intEncodeDelivery($delivery = array())
	{
		if (empty($delivery))
			return 0;

		$date = isset($delivery['date']) ? (int)$delivery['date'] : 0;
		$day = isset($delivery['day']) ? (int)$delivery['day'] : 0;
		$shm = isset($delivery['shm']) ? (int)$delivery['shm'] : 0;
		$cent = isset($delivery['cent']) ? (int)$delivery['cent'] : 0;

		return (int)$date +
			intval($day * self::$dmask['date']) +
			intval($shm * self::$dmask['day']) +
			intval($cent * self::$dmask['shm']);
	}

	public static function intDecodeDelivery($delivery_int = 0)
	{
		return array(
			'date' => $delivery_int ? intval($delivery_int % self::$dmask['date']) : 0,
			'day' => $delivery_int ? intval(($delivery_int % self::$dmask['day']) / self::$dmask['date']) : 0,
			'shm' => $delivery_int ? intval(($delivery_int % self::$dmask['shm']) / self::$dmask['day']) : 0,
			'cent' => $delivery_int ? intval($delivery_int / self::$dmask['shm']) : 0,
			);
	}

	public function getDelivery()
	{
		return $this->delivery_int ? self::intDecodeDelivery($this->delivery_int) : false;
	}

	public function getDeliveryDate()
	{
		return $this->delivery_int ? intval($this->delivery_int % self::$dmask['date']) : 0;
	}

	public function getDeliveryDay()
	{
		return $this->delivery_int ? intval(($this->delivery_int % self::$dmask['day']) / self::$dmask['date']) : 0;
	}

	public function getDeliveryShm()
	{
		return $this->delivery_int ? intval(($this->delivery_int % self::$dmask['shm']) / self::$dmask['day']) : 0;
	}

	public function getDeliveryCent()
	{
		return $this->delivery_int ? intval($this->delivery_int / self::$dmask['shm']) : 0;
	}

	public function setDelivery($delivery = array())
	{
		// $date = (int)isset($delivery['date']) ? $delivery['date'] : $this->getDeliveryDate();
		// $day = (int)isset($delivery['day']) ? $delivery['day'] : $this->getDeliveryDay();
		// $shm = (int)isset($delivery['shm']) ? $delivery['shm'] : $this->getDeliveryShm();
		// $cent = (int)isset($delivery['cent']) ? $delivery['cent'] : $this->getDeliveryCent();
		$return = true;
		$delivery_int = self::intEncodeDelivery($delivery);
		if ($delivery_int !== $this->delivery_int)
		{
			$this->delivery_int = $delivery_int;
			$return = $this->save();
		}

		return $return;
	}
*/
}