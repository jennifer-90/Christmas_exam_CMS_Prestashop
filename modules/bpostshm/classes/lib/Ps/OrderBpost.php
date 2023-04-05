<?php
/**
* order_bpost table encapsulation class
*  
* @author    Serge <serge@stigmi.eu>
* @version   0.5.0
* @copyright Copyright (c), Eontech.net. All rights reserved.
* @license   BSD License
*/

class PsOrderBpost extends ObjectModel
{

	public $id_shop_group;

	public $id_shop;

	/** @var boolean True when order state changes to 'Treated' as per settings */
	public $treated = 0;

	/** @var integer Order State id */
	public $current_state = 0;

	/** @var string Actual Bpost order status */
	public $status;

	/** @var int shipping method (8+1) if international */
	public $shm = 0;

	/** @var integer order drop date */
	public $dt_drop = 0;

	/** @var string Displayed delivery method in delivery options */
	public $delivery_method;

	/** @var string Displayed recipient */
	public $recipient;

	/** @var string Object creation date */
	public $date_add;

	/** @var string Object last modification date */
	public $date_upd;

	/**
	 * @var string Bpost Order reference, should be unique
	 */
	public $reference;

	/**
	 * @see ObjectModel::$definition
	 */
	public static $definition;

	/**
	 * @see 1.4 ObjectModel->$table
	 *      	ObjectModel->$identifier
	 * @see 1.4 ObjectModel->$fieldsRequired
	 *      	ObjectModel->$fieldsValidate
	 */
	protected $table = 'order_bpost';
	protected $identifier = 'id_order_bpost';
	protected $fieldsRequired = array('reference', 'current_state', 'shm', 'delivery_method', 'recipient');
	protected $fieldsValidate = array(
		'reference' =>			'isString',
		'treated' =>			'isBool',
		'current_state' =>		'isUnsignedId',
		'shm' =>				'isUnsignedId',
		'dt_drop' =>			'isUnsignedId',
		'delivery_method' =>	'isString',
		'recipient' =>			'isString',
		);


	public function getFields()
	{
		parent::validateFields();

		$fields['reference'] = 			pSQL($this->reference);
		$fields['treated'] = 			(int)$this->treated;
		$fields['current_state'] = 		(int)$this->current_state;
		$fields['status'] = 			pSQL($this->status);
		$fields['shm'] =				(int)$this->shm;
		$fields['dt_drop'] =			(int)$this->dt_drop;
		$fields['delivery_method'] = 	pSQL($this->delivery_method);
		$fields['recipient'] = 			pSQL($this->recipient);
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
				'table' => 'order_bpost',
				'primary' => 'id_order_bpost',
				'multishop' => true,
				'fields' => array(
					'reference' =>			array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true),
					'id_shop_group' =>		array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
					'id_shop' =>			array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
					'treated' =>			array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
					'current_state' =>		array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
					'status' =>				array('type' => self::TYPE_STRING),
					'shm' =>				array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
					'dt_drop' =>			array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
					'delivery_method' =>	array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true),
					'recipient' =>			array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true),
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
		if ((int)$this->id > 0)
			return parent::update($null_values);

		$return = parent::add($null_values, $autodate);

		// must manually set Prestashop 1.5+
		// id_shop, id_shop_group to take hold !
		if (self::isPs15Plus())
		{
			// Context is not dependable ! Only ps_orders values are safe.
			$id_order = (int)Tools::substr($this->reference, 7);
			$sql = 'UPDATE `'._DB_PREFIX_.self::$definition['table'].'` ob, `'._DB_PREFIX_.'orders` o
			SET ob.`id_shop` = o.`id_shop`,
				ob.`id_shop_group` = o.`id_shop_group`
			WHERE ob.`id_order_bpost` = '.(int)$this->id.'
			AND o.`id_order` = '.(int)$id_order;

			$return = $return && Db::getInstance()->execute($sql);
		}

		return $return;
	}

	/**
	 * [isPs15Plus helper static function
	 * @return boolean True if Prestashop is 1.5+
	 */
	private static function isPs15Plus()
	{
		return (bool)version_compare(_PS_VERSION_, '1.5', '>=');
	}

	/**
	 * Get bpost order using reference
	 * 
	 * @param string $reference
	 * @return PsOrderBpost
	 */
	public static function getByReference($reference)
	{
		$result = Db::getInstance()->getRow('
		SELECT `id_order_bpost`
		FROM `'._DB_PREFIX_.'order_bpost`
		WHERE `reference` = "'.pSQL($reference).'"');

		return isset($result['id_order_bpost']) ? new PsOrderBpost((int)$result['id_order_bpost']) : false;
	}

	/**
	 * Get bpost order using Prestashop order id
	 * 
	 * @param int $ps_order_id
	 * @return PsOrderBpost if found false otherwise
	 */
	public static function getByPsOrderID($ps_order_id)
	{
		$result = Db::getInstance()->getRow('
		SELECT `id_order_bpost`
		FROM `'._DB_PREFIX_.'order_bpost`
		WHERE SUBSTRING(`reference`, 8) = '.(int)$ps_order_id);

		return isset($result['id_order_bpost']) ? new PsOrderBpost((int)$result['id_order_bpost']) : false;
	}

	/**
	 * Get prestashop order using reference
	 * 
	 * @param string $reference
	 * @return Prestashop Order
	 */
	public static function getPsOrderByReference($reference)
	{
		return new Order((int)Tools::substr($reference, 7));
	}

	/* bulk actions */
	public static function fetchOrdersbyRefs($refs, $untreated = false)
	{
		$orders = array();
		$valid_refs = array_map('pSQL', $refs);
		$refs_string = (string)implode('","', $valid_refs);

		$sql = '
	SELECT `id_order_bpost`, `reference`, `id_shop`, `status`, `shm`
	FROM `'._DB_PREFIX_.'order_bpost`';
		$where = 'WHERE `reference` IN ("'.$refs_string.'")';
		$where .= (bool)$untreated ? '
	AND `treated` = 0
	AND `status` NOT IN ("DELIVERED", "CANCELLED")
		' : '';
		$orderby = '
	ORDER BY `id_shop` ASC, `id_order_bpost` ASC';

		if ($rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS($sql.$where.$orderby))
			foreach ($rows as $row)
				$orders[(int)$row['id_shop']][] = $row;

		return $orders;
	}

	public static function fetchShopOrdersbyRefs($refs)
	{
		$orders = array();

		$valid_refs = array_map('pSQL', $refs);
		$refs_string = (string)implode('","', $valid_refs);
		$rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
		SELECT `id_order_bpost`, `id_shop`
		FROM `'._DB_PREFIX_.'order_bpost`
		WHERE `reference` IN ("'.$refs_string.'")
		ORDER BY `id_shop` ASC, `id_order_bpost` ASC');

		if ($rows)
			foreach ($rows as $row)
				$orders[(int)$row['id_shop']][] = new self((int)$row['id_order_bpost']);
		
		return $orders;
	}

	/* Cron Pair */
	public static function fetchBulkOrderRefs($num_days, $id_shop = 1)
	{
		$rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
		SELECT `id_order_bpost`, `reference`, `id_shop`, `status`
		FROM `'._DB_PREFIX_.'order_bpost`
		WHERE `status` IS NOT NULL
		AND `id_shop` = '.(int)$id_shop.'
		AND `status` NOT IN ("DELIVERED", "CANCELLED")
		AND DATEDIFF(NOW(), `date_add`) <= '.(int)$num_days.'
		ORDER BY id_order_bpost ASC');

		return $rows;
	}

	public static function updateBulkOrderStatus($orders_status)
	{
		if (!is_array($orders_status) or empty($orders_status))
			return 0;

		$ids = array();
		$cases = '';
		$sql = '
		UPDATE `'._DB_PREFIX_.'order_bpost`
		SET `status` = CASE
		';
		foreach ($orders_status as $row)
		{
			$cases .= '
				WHEN `id_order_bpost` = '.(int)$row['id_order_bpost'].' THEN "'.pSQL($row['status']).'"';
			$ids[] = (int)$row['id_order_bpost'];
		}
		$sql .= $cases.'
			ELSE `status`
			END
		WHERE `id_order_bpost` IN ('.implode(',', $ids).')';

		return Db::getInstance()->execute($sql);
	}

	/**
	 * add bpost labels using is_retour
	 * 
	 * @param int $count number of labels to add
	 * @param bool 	$is_retour
	 * @return bool
	 */
	public function addLabels($count = 1, $is_retour = false, $status = 'PENDING')
	{
		if (!(bool)$this->id || (int)$count < 1)
			return false;

		$return = true;
		$auto_retour = (bool)Configuration::get('BPOST_AUTO_RETOUR_LABEL');

		while ((int)$count > 0)
		{
			$order_label = new PsOrderBpostLabel();
			$order_label->id_order_bpost = $this->id;
			$order_label->is_retour = (bool)$is_retour;
			$order_label->has_retour = (bool)$auto_retour;
			$order_label->status = (string)$status;
			$return = $return && $order_label->save();
			$count--;
		}

		return $return;
	}

	/**
	 * add a single bpost label using is_retour
	 * 
	 * @param bool 	$is_retour
	 * @return bool
	 */
	public function addLabel($is_retour = false, $status = 'PENDING')
	{
		if (!(bool)$this->id)
			return false;

		// Configuration context is not reliable
		// if (self::isPs15Plus())
		// 	Shop::setContext(Shop::CONTEXT_SHOP, (int)$this->id_shop);

		$auto_retour = (bool)Configuration::get('BPOST_AUTO_RETOUR_LABEL');

		$order_label = new PsOrderBpostLabel();
		$order_label->id_order_bpost = $this->id;
		$order_label->is_retour = (bool)$is_retour;
		$order_label->has_retour = (bool)$auto_retour;
		$order_label->status = (string)$status;
		return $order_label->save();
	}

	public function countPrinted()
	{
		$result = Db::getInstance()->getRow('
		SELECT COUNT(`id_order_bpost_label`) AS count_printed
		FROM `'._DB_PREFIX_.'order_bpost_label`
		WHERE `id_order_bpost` = '.(int)$this->id.'
		AND barcode IS NOT NULL');

		return isset($result['count_printed']) ? (int)$result['count_printed'] : false;
	}

	/**
	 * Get new Bpost order labels using id
	 * 
	 * @param bool $separate if true into [1] => has_retour and [0] => hasn't
	 * @return array of PsOrderBpostLabel Collections
	 */
	public function getNewLabels($separate = true)
	{
		$new_labels = array();

		$rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
		SELECT `id_order_bpost_label`, `has_retour`
		FROM `'._DB_PREFIX_.'order_bpost_label`
		WHERE `id_order_bpost` = '.(int)$this->id.' AND barcode IS NULL
		ORDER BY id_order_bpost_label ASC');

		if ($rows)
			foreach ($rows as $row)
			{
				$order_bpost_label = new PsOrderBpostLabel((int)$row['id_order_bpost_label']);
				if ($separate)
					$new_labels[(int)$row['has_retour']][] = $order_bpost_label;
				else
					$new_labels[] = $order_bpost_label;

			}

		return $new_labels;
	}

	/**
	 * Get the 1st (printed, non-retour) barcode
	 * Srg: 18-dec-2020 New mandatory (hopefully temporary) tracking-reference
	 * @return (string) barcode | false
	 */
	public function getPrimaryBarcode()
	{
		// `getRow` explicitly adds 'LIMIT 1' to the query
		$result = Db::getInstance()->getRow('
		SELECT `barcode`
		FROM `'._DB_PREFIX_.'order_bpost_label`
		WHERE `id_order_bpost` = '.(int)$this->id.'
		AND `barcode` IS NOT NULL
		AND `is_retour` = 0
		ORDER BY `id_order_bpost_label` ASC');

		return isset($result['barcode']) ? (string)$result['barcode'] : false;
	}

	/**
	 * Get Bpost order labels using id
	 * 
	 * @return Collection of PsOrderBpostLabel
	 */
/*	public function getLabels()
	{
		$order_labels = new Collection('PsOrderBpostLabel');
		$order_labels->where('id_order_bpost', '=', $this->id);
		return $order_labels->getResults();
	}

	public function getAllNewLabels()
	{
		$new_labels = array();

		$is_intl = (bool)($this->shm > 7);
		if ($rows = $this->_getAllEmptyLabels())
		{
			$labels_regular = array();
			$labels_odd = array();
			foreach ($rows as $row)
			{
				$order_bpost_label = new PsOrderBpostLabel((int)$row['id_order_bpost_label']);
				$is_retour = (int)$row['is_retour'];
				$has_retour = (int)$row['has_retour'];
				//$regular = (int)(!($is_intl && ($is_retour || $has_retour)));
				$odd = (bool)($is_intl && ($is_retour || $has_retour));
				if ($odd)
					$labels_odd[] = $order_bpost_label;
				else
					$labels_regular[$has_retour][] = $order_bpost_label;
			}

			if (count($labels_regular))
				$new_labels[] = $labels_regular;

			foreach ($labels_odd as $label)
				$new_labels[][$label->has_retour][] = $label;
		}

		return $new_labels;
	}

	private function _getAllEmptyLabels()
	{
		return Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
		SELECT `id_order_bpost_label`, `is_retour`, `has_retour`
		FROM `'._DB_PREFIX_.'order_bpost_label`
		WHERE `id_order_bpost` = '.(int)$this->id.' AND barcode IS NULL
		ORDER BY id_order_bpost_label ASC');
	}
*/	
}