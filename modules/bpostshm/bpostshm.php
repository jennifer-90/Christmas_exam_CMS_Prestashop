<?php
/**
 * 2014-2019 Stigmi
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

if (!defined('_PS_VERSION_'))
	exit;

require_once(_PS_MODULE_DIR_.'bpostshm/classes/Service.php');

class BpostShm extends CarrierModule
{
	/**
	 * 1: @home
	 * 2: @bpost
	 * 4: @24/7
	 * 7: (1+2+4, Post Office + Post Point + bpack 24/7)
	 */
	const SHM_HOME = 1;
	const SHM_PPOINT = 2;
	const SHM_PLOCKER = 4;
	/* Psudo SHM international, PostPointInternational
	 * 9:	(1+8)
	 * 10:	(2+8)
	 */
	const SHM_SPMASK = 6;
	const SHM_IMASK = 8;
	//
	const SHM_INTL = 9;
	const SHM_PPI = 10;
	/* International EAD (non-EU)
	 *
	 */
	const SHM_IMASK_EAD = 0x10;
	const SHM_INTL_EAD = 0x19;
	/**
	 * 16: @kariboo
	 * 19: (Post Office + Post Point + Kariboo)
	 */
	const PPT_POST_OFFICE = 1;
	const PPT_POST_POINT = 2;
	const PPT_KARIBOO = 16;
	const PPT_ALL = 19;

	const MODULE_NAME = 'bpostshm';
	const ADMIN_CTLR = 'AdminOrdersBpost';
	// const TRACKING_URL = 'http://track.bpost.be/etr/light/performSearch.do?searchByCustomerReference=1&';
	const TRACKING_URL = 'https://track.bpost.cloud/btr/web/#/search?';
	const API_URL = 'https://api.bpost.be/services/shm';
	const API_URL_TEST = 'https://test2api.bpost.be/services/shm';
	const DEF_GMAK = 'AIzaSyDWSs8ppcHqSKj2TQsvM8g6PLduGj5PbQ0';
	const DEF_CUTOFF = '1500';
	const DEF_TREATED_ORDER_STATE = 3;	/* Preparation in progress */
	const DEF_ORDER_BPOST_DAYS = 15;

	public $carriers = array();
	public $shipping_methods = array();

	private $order_states_inc = array();

	protected static $_shm_pos = array(
		self::SHM_PPOINT,
		self::SHM_PLOCKER,
		self::SHM_HOME,
	);

	protected static $_hooks_common = array(
		'displayBackOfficeHeader',		// backOfficeHeader
		'displayBeforeCarrier',			// beforeCarrier
		'displayPaymentTop',			// paymentTop
		'actionCarrierProcess',			// processCarrier
		'actionValidateOrder',			// newOrder
		'actionOrderStatusPostUpdate',	// postUpdateOrderStatus
		'actionCarrierUpdate',			// updateCarrier
		'displayOrderDetail',			// orderDetailDisplayed
		'displayHeader',				// header
		//
		'displayAdminListBefore',
	);

	protected static $_hooks_specific = array(
		'1.6' => array(
			'displayMobileHeader',
		),
		'1.7' => array(
		),
	);

	public function __construct()
	{
		$this->author = 'Stigmi.eu';
		$this->bootstrap = true;
		$this->name = 'bpostshm';	//self::MODULE_NAME;
		$this->need_instance = 0;
		$this->tab = 'shipping_logistics';
		$this->version = '1.65.4';
		//
		$this->ps_versions_compliancy = array('min' => '1.5', 'max' => '1.7');
		// $this->ps_versions_compliancy = array('min' => '1.5', 'max' => _PS_VERSION_);
		$this->module_key = '5573531ee6e49b17bf91e0ff664b2a07';

		$this->displayName = $this->l('bpost Shipping Manager - bpost customers only');
		$this->description = $this->l('IMPORTANT: bpostshm module description');

		parent::__construct();

		$this->hooks = self::getModuleHooks();

		$this->shipping_methods = array(
			self::SHM_HOME => array(
				'name' 	=> 'Home delivery / Livraison à domicile / Thuislevering',
				'delay' => array(
					'en' =>	'Receive your parcel at home or at the office.',
					'fr' =>	'Recevez votre colis à la maison ou au bureau.',
					'nl' =>	'Ontvang je pakje thuis of op kantoor.',
					),
				'logo' => 'logo-carrier',
				'slug' 	=> '@home',
				'lname'	=> $this->l('Home delivery'),
			),
			self::SHM_PPOINT => array(
				'name' 	=> 'Pick-up point / Point d’enlèvement / Afhaalpunt',
				'delay' => array(
					'en' =>	'2000 bpost Pick-up points, always closeby and on average 30% less CO2 emissions.',
					'fr' =>	'2000 Points d’enlèvement bpost, toujours à proximité et en moyenne 30% d\'émissions de CO2 en moins.',
					'nl' =>	'2000 bpost Afhaalpunten, altijd dichtbij en gemiddeld 30% minder CO2 uitstoot.',
					),
				'logo' => 'logo-carrier-green',
				'slug' 	=> '@bpost',
				'lname'	=> $this->l('Pick-up point'),
			),
			self::SHM_PLOCKER => array(
				'name' 	=> 'Parcel locker / Distributeur de Colis / Pakjesautomaat',
				'delay' => array(
					'en' =>	'Available 24/7 and reduce CO2 emissions on average with 30%.',
					'fr' =>	'Disponible 24/7 et contribue à réduire les émissions de CO2 de 30% en moyenne.',
					'nl' =>	'24/7 beschikbaar en verminderen de CO2 uitstoot gemiddeld met 30%.',
					),
				'logo' => 'logo-carrier-green',
				'slug' 	=> '@24/7',
				'lname'	=> $this->l('Parcel locker'),
			),
		);

		$this->_all_delivery_options = array(
			300 => array(
				'title' => $this->l('Signature'),
				'info' => $this->l('The delivery happens against signature by the receiver.'),
				),
			330 => array(
				'title' => $this->l('2nd Presentation'),
				'info' => $this->l('IMPORTANT: 2nd presentation info'),
				),
			350 => array(
				'title' => $this->l('Insurance'),
				'info' => $this->l('Insurance to insure your goods to a maximum of 500,00 euro.'),
				),
			470 => array(
				'title' => $this->l('Saturday Delivery'),
				'info' => $this->l('Allow delivery of your goods on Saturdays.'),
				),
			540 => array(
				'title' => $this->l('Insurance basic'),
				'info' => $this->l('Insurance to insure your goods to a maximum of 500,00 euro.'),
				),
		);
	}

	/**
	 * @return bool
	 */
	public function install()
	{
		if (!extension_loaded('curl'))
		{
			$this->_errors[] = $this->l('This module requires CURL to work properly');
			return false;
		}
		$return = true;

		$return = $return && parent::install();
		$return = $return && $this->addReplaceCarriers();
		$return = $return && $this->resetCarrierPositions();

		foreach ($this->hooks as $hook)
			if (!$this->isRegisteredInHook($hook))
				$return = $return && $this->registerHook($hook);

		// Srg: 28-aug-18 (no longer variable)
		// $return = $return && Configuration::updateGlobalValue('BPOST_ACCOUNT_API_URL', self::API_URL);
		$return = $return && $this->addReplaceOrderState();

		// addCartBpostTable
		$table_cart_bpost_create = array(
			'name' => _DB_PREFIX_.'cart_bpost',
			'primary_key' => 'id_cart_bpost',
			'fields' => array(
				'id_cart_bpost' => 'int(11) unsigned NOT NULL AUTO_INCREMENT',
				'id_cart' => 'int(10) unsigned NOT NULL',
				'service_point_id' => 'INT(10) unsigned NOT NULL DEFAULT 0',
				'sp_type' => 'TINYINT(1) unsigned NOT NULL DEFAULT 0',
				'option_kmi' => 'TINYINT(1) unsigned NOT NULL DEFAULT 0',
				'delivery_codes' => 'varchar(50) NOT NULL DEFAULT "0,0,0"',
				'upl_info' => 'TEXT',
				'bpack247_customer' => 'TEXT',
				'date_add' => 'datetime NOT NULL',
				'date_upd' => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP',
			)
		);
		$return = $return && $this->dbCreateTable($table_cart_bpost_create);

		// addOrderBpostTable
		$table_order_bpost_create = array(
			'name' => _DB_PREFIX_.'order_bpost',
			'primary_key' => 'id_order_bpost',
			'fields' => array(
				'id_order_bpost' => 'int(11) unsigned NOT NULL AUTO_INCREMENT',
				'reference' => 'varchar(50) NOT NULL',
				'id_shop_group' => 'int(11) unsigned NOT NULL DEFAULT 1',
				'id_shop' => 'int(11) unsigned NOT NULL DEFAULT 1',
				'treated' => 'TINYINT(1) unsigned NOT NULL DEFAULT 0',
				'current_state' => 'int(10) unsigned NOT NULL DEFAULT 0',
				'status' => 'varchar(20)',
				'shm' => 'TINYINT(1) unsigned NOT NULL DEFAULT 0',
				'dt_drop' => 'int(10) unsigned NOT NULL DEFAULT 0',
				'delivery_method' => 'varchar(25) NOT NULL',
				'recipient' => 'varchar(255) NOT NULL',
				'date_add' => 'datetime NOT NULL',
				'date_upd' => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP',
			)
		);
		$return = $return && $this->dbCreateTable($table_order_bpost_create);

		// addOrderBpostLabelTable
		$table_order_bpost_label_create = array(
			'name' => _DB_PREFIX_.'order_bpost_label',
			'primary_key' => 'id_order_bpost_label',
			'fields' => array(
				'id_order_bpost_label' => 'int(11) unsigned NOT NULL AUTO_INCREMENT',
				'id_order_bpost' => 'int(11) unsigned NOT NULL',
				'is_retour' => 'TINYINT(1) unsigned NOT NULL DEFAULT 0',
				'has_retour' => 'TINYINT(1) unsigned NOT NULL DEFAULT 0',
				'status' => 'varchar(20) NOT NULL',
				'barcode' => 'varchar(25) DEFAULT NULL',
				'barcode_retour' => 'varchar(25) DEFAULT NULL',
				'date_add' => 'datetime NOT NULL',
				'date_upd' => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP',
			)
		);
		$return = $return && $this->dbCreateTable($table_order_bpost_label_create);
		$return = $return && $this->upgradeTo($this->version);

		if ((bool)Configuration::get('BPOST_USE_PS_LABELS'))
			$this->installModuleTab(
				self::ADMIN_CTLR,
				'bpost',
				Tab::getIdFromClassName('AdminParentOrders')
			);

		return $return;
	}

	/**
	 * @return bool
	 */
	public function upgradeTo($version = '')
	{
		$return = !empty($version);
		require_once(_PS_MODULE_DIR_.$this->name.'/classes/UpgradeService.php');
		$upgrader = UpgradeService::init($this);
		$return = $return && $upgrader->upgradeTo($version);

		return $return;
	}

	/**
	 * @return bool
	 */
	public function uninstall()
	{
		// Do not include into function return because tab might already be uninstalled
		$this->uninstallModuleTab(self::ADMIN_CTLR);

		$return = true;

		$cache_dir = defined('_PS_CACHE_DIR_') ? _PS_CACHE_DIR_ : _PS_ROOT_DIR_.'/cache/';
		if (Tools::file_exists_cache($cache_dir.'class_bpost_index.php'))
			$return = $return && unlink($cache_dir.'class_bpost_index.php');

		$hooks = self::getModuleHooks(true);
		foreach ($hooks as $hook)
			if ($this->isRegisteredInHook($hook))
				$return = $return && $this->unregisterHook($hook);

		$return = $return && $this->removeCarriers();

		$return = $return && parent::uninstall();

		return $return;
	}

	/**
	 * @return  bool
	 */
	public function updateCarriersTitles($with_logo = false)
	{
		$return = true;

		$stored_carrier_ids = $this->getIdCarriers();
		foreach ($this->shipping_methods as $shipping_method => $details)
		{
			// testing int 0 for null
			$id_carrier = (int)$stored_carrier_ids[$shipping_method];
			$carrier = new Carrier($id_carrier);
			if (Validate::isLoadedObject($carrier) &&
				$carrier->external_module_name === (string)$this->name)
			{
				$carrier->name = (string)$details['name'];
				$carrier->delay = $this->getTranslatedFields($details['delay']);
				if ($with_logo)
					$this->setCarrierLogo((int)$carrier->id, (string)$details['logo']);

				$return = $return && $carrier->save();
			}
		}

		return $return;
	}

	/**
	 * @return  bool
	 */
	public function resetCarrierPositions()
	{
		$return = true;

		$carriers = array();
		$cpos = array();
		$stored_carrier_ids = $this->getIdCarriers();
		foreach (self::$_shm_pos as $shipping_method)
		{
			// testing int 0 for null
			$id_carrier = (int)$stored_carrier_ids[$shipping_method];
			$carrier = new Carrier($id_carrier);
			if (Validate::isLoadedObject($carrier) &&
				$carrier->external_module_name === (string)$this->name)
			{
				$cpos[] = (int)$carrier->position;
				$carriers[] = $carrier;
			}
		}

		sort($cpos);
		if ((bool)count($carriers))
		{
			for ($i = 0; $i < (int)count($carriers); $i++)
			{
				$carriers[$i]->position = (int)$cpos[$i];
				$return = $return && $carriers[$i]->save();
			}
			// Sort carriers by position rather than price
			if ($return && false !== Configuration::get('PS_CARRIER_DEFAULT_SORT'))
				Configuration::updateValue('PS_CARRIER_DEFAULT_SORT', Carrier::SORT_BY_POSITION);
		}

		return $return;
	}

	/**
	 * [addReplaceCarriers reverses carrier removal (carrier->deleted = true) if found]
	 * Create new otherwise
	 * [Remark: No need to remove carrier image icons]
	 * @author Serge <serge@stigmi.eu>
	 * @return bool true if success
	 */
	private function addReplaceCarriers()
	{
		$return = true;

		$user_groups_tmp = Group::getGroups($this->context->language->id);
		if (is_array($user_groups_tmp) && !empty($user_groups_tmp))
		{
			$user_groups = array();
			foreach ($user_groups_tmp as $group)
				$user_groups[] = (int)$group['id_group'];
		}

		$stored_carrier_ids = $this->getIdCarriers();
		// foreach ($this->shipping_methods as $shipping_method => $details)
		foreach (self::$_shm_pos as $shipping_method)
		{
			$details = $this->shipping_methods[$shipping_method];
			// testing int 0 for null
			$id_carrier = (int)$stored_carrier_ids[$shipping_method];
			$carrier = new Carrier($id_carrier);
			// Validate::isLoadedObject is no good for us. new object has no id!
			// if (!Validate::isLoadedObject($carrier))
			// 	return false;

			$carrier->deleted = (int)false;
			$carrier->active = true;
			$carrier->external_module_name = $this->name;
			$carrier->name = (string)$details['name'];
			$carrier->delay = $this->getTranslatedFields($details['delay']);
			$carrier->need_range = true;
			$carrier->url = (string)self::getCarrierTrackingUrl();
			$carrier->shipping_external = true;
			$carrier->shipping_handling = false;
			$carrier->is_module = version_compare(_PS_VERSION_, '1.4', '<') ? 0 : 1;

			if ($ret_tmp = $carrier->save())
			{
				$id_zone_be = false;
				$zone_labels = array('België', 'Belgie', 'Belgique', 'Belgium');
				foreach ($zone_labels as $zone_label)
					if ($id_zone = Zone::getIdByName($zone_label))
					{
						$id_zone_be = (int)$id_zone;
						break;
					}

				if (!$id_zone_be)
				{
					$zone = new Zone();
					$zone->name = 'Belgium';
					$zone->active = true;
					$zone->save();
					$id_zone_be = (int)$zone->id;
				}

				Configuration::updateGlobalValue('BPOST_ID_ZONE_BELGIUM', (int)$id_zone_be);

				if ($id_country = Country::getByIso('BE'))
				{
					$country = new Country($id_country);
					if ((int)$country->id_zone != (int)$id_zone_be)
					{
						$country->id_zone = (int)$id_zone_be;
						$country->save();
					}

					if (!$carrier->getZone((int)$id_zone))
						$carrier->addZone((int)$id_zone);

				}

				Configuration::updateGlobalValue('BPOST_SHIP_METHOD_'.$shipping_method.'_ID_CARRIER', (int)$carrier->id);

				// Enable carrier for every user groups
				if (is_array($user_groups) && !empty($user_groups) && method_exists($carrier, 'setGroups'))
					$carrier->setGroups($user_groups);

				// srg 1-feb-2022
				$this->setCarrierLogo((int)$carrier->id, (string)$details['logo']);
			}

			$return = $return && $ret_tmp;
		}

		// Sort carriers by position rather than price
		if ($return && false !== Configuration::get('PS_CARRIER_DEFAULT_SORT'))
			Configuration::updateValue('PS_CARRIER_DEFAULT_SORT', Carrier::SORT_BY_POSITION);

		return $return;
	}

	/**
	 * [deleteCarriers mark carriers for deletion (carrier->deleted = true) if found]
	 * [Remark: No need to remove carrier image icons]
	 * @author Serge <serge@stigmi.eu>
	 * @return bool true if success
	 */
	private function removeCarriers()
	{
		$return = true;
		foreach ($this->getIdCarriers() as $id_carrier)
		{
			$carrier = new Carrier($id_carrier);
			if ($valid_carrier = Validate::isLoadedObject($carrier))
			{
				$carrier->active = false;
				$carrier->deleted = (int)true;
				$valid_carrier = $carrier->save();
			}
			$return = $return && $valid_carrier;
		}
		$return = $return && (method_exists('Carrier', 'cleanPositions') ? Carrier::cleanPositions() : true);

		return $return;
	}

	/**
	 * [addReplaceOrderState add or Replace Edit BPost 'Treated' order status to Prestashop]
	 * @author Serge <serge@stigmi.eu>
	 */
	private function addReplaceOrderState()
	{
		$return = true;
		$treated_names = array(
				'en' => 'Treated',
				'fr' => 'Traitée',
				'nl' => 'Behandeld',
			);

		$id_order_state = null;
		$order_states = OrderState::getOrderStates($this->context->language->id);
		if (!is_array($order_states))
			return false;

		foreach ($order_states as $order_state)
			if (in_array($order_state['name'], array_values($treated_names)))
			{
				// testing int 0 for null
				$id_order_state = (int)$order_state['id_order_state'];
				break;
			}

		// Creates new OrderState if id still null (ie. not found)
		$order_state = new OrderState($id_order_state);
		$order_state->name = $this->getTranslatedFields($treated_names);

		// $order_state->color = '#ddff88';
		$order_state->hidden = true;
		$order_state->logable = true;
		$order_state->paid = true;
		// Srg: 31-mar-19 Statuses revisited
		$order_state->invoice = true;
		$order_state->color = '#aacc55';

		$return = $return && $order_state->save();
		$return = $return && Configuration::updateGlobalValue('BPOST_ORDER_STATE_TREATED', (int)$order_state->id);

		$this->setIcon(_PS_MODULE_DIR_.$this->name.'/views/img/icons/box_closed.png', _PS_IMG_DIR_.'os/'.(int)$order_state->id.'.gif');

		return $return;
	}

	/**
	 * [getTranslatedFields helper for Prestashop mixed id_lang -> string db fields]
	 * @author Serge <serge@stigmi.eu>
	 * @param  mixed $source array of 'iso_code' => 'translated string'
	 * @return mixed         array of 'id_land' => 'translated string'
	 */
	private function getTranslatedFields($source)
	{
		$translated = array();
		if (is_array($source) && count($source) && $default = reset($source))
			if ($languages = Language::getLanguages(false))
				foreach ($languages as $language)
					if (isset($source[$language['iso_code']]))
						$translated[$language['id_lang']] = $source[$language['iso_code']];
					else
						$translated[$language['id_lang']] = $default;
			else
				$translated[$this->context->language->id] = $default;

		return $translated;
	}

	/**
	 * [dbCreateTable Create new prestashop custom named table with field attribs (Alter to match if already exists]
	 * @author Serge <serge@stigmi.eu>
	 * @param  mixed $table array of name, position, field attribs
	 * @return bool        true if no db error
	 */
	private function dbCreateTable($table)
	{
		if (!isset($table['name']) || empty($table['fields']))
			return false;

		$sql = 'CREATE TABLE IF NOT EXISTS `'.pSQL($table['name']).'` ('.PHP_EOL;
		foreach ($table['fields'] as $key => $value)
			$sql .= '`'.$key.'` '.$value.','.PHP_EOL;

		if (isset($table['primary_key']))
			$sql .= 'PRIMARY KEY (`'.$table['primary_key'].'`)';
		else
			// remove final ',' -1 if no EOL char added
			$sql = Tools::substr($sql, 0, -2);

		$sql .= ' );';

		$db = Db::getInstance(_PS_USE_SQL_SLAVE_);
		$db->execute($sql);
		$return = (0 === $db->getNumberError());

		// Check all fields are present
		$table['after'] = 'FIRST';
		return $return && $this->dbAlterTable($table);
	}

	/**
	 * [dbAlterTable Alter prestashop named table to alter/append field values at column position]
	 * @author Serge <serge@stigmi.eu>
	 * @param  mixed $table array of name, position, field attribs
	 * @return bool        true if no db error
	 */
	private function dbAlterTable($table)
	{
		if (!isset($table['name']) || empty($table['fields']))
			return false;

		// check if number of columns match
		$columns = array_keys($table['fields']);
		$columns_list = implode('\',\'', $columns);
		$sql = '
SELECT
	column_name
FROM
	information_schema.columns
WHERE
	table_schema = "'._DB_NAME_.'"
AND
	table_name = "'.$table['name'].'"
AND
	column_name in (\''.$columns_list.'\')';

		$db = Db::getInstance(_PS_USE_SQL_SLAVE_);
		// query columns already present
		$columns_present = $db->ExecuteS($sql);
		$return = (0 === $db->getNumberError());

		if ($return && count($columns_present) !== count($columns))
		{
			// required columns are Not all present
			// create missing columns
			$columns_present = array_map('current', $columns_present);
			$after = !isset($table['after']) ? '' : ('FIRST' === $table['after'] ? 'FIRST' : 'AFTER '.$table['after']);
			$sql = 'ALTER TABLE `'.pSQL($table['name']).'`'.PHP_EOL;
			foreach ($table['fields'] as $key => $value)
			{
				if (!in_array($key, $columns_present))
					$sql .= 'ADD COLUMN `'.$key.'` '.$value.' '.$after.','.PHP_EOL;

				$after = 'AFTER `'.$key.'`';
			}
			// remove final ',' -1 if no EOL char added
			$sql = Tools::substr($sql, 0, -2);

			// add missing columns
			$db->execute($sql);
			$return = (0 === $db->getNumberError());
		}

		return $return;
	}

	/**
	 * @param string $tab_class
	 * @param string $tab_name
	 * @param int $id_tab_parent
	 * @return mixed
	 */
	private function installModuleTab($tab_class, $tab_name, $id_tab_parent)
	{
		if (!Tab::getIdFromClassName($tab_class))
		{
			$tab = new Tab();
			if ($languages = Language::getLanguages(true, $this->context->shop->id))
				foreach ($languages as $language)
					$tab->name[$language['id_lang']] = $tab_name;
			else
				$tab->name = $tab_name;
			$tab->class_name = $tab_class;
			$tab->id_parent = $id_tab_parent;
			$tab->module = $this->name;
			return $tab->save();
		}
	}

	/**
	 * @param string $tab_class
	 * @return bool
	 */
	private function uninstallModuleTab($tab_class)
	{
		$return = true;
		if ($id_tab = (int)Tab::getIdFromClassName($tab_class))
		{
			$tab = new Tab($id_tab);
			$return = $return && Validate::isLoadedObject($tab) && $tab->delete();
		}
		return $return;
	}

	/**
	 * [setIcon copy src image to destination if necessary. replace if different]
	 * @author Serge <serge@stigmi.eu>
	 * @param string $src  Source path
	 * @param string $dest destination path
	 * @return  bool true if icon in place
	 */
	private function setIcon($src, $dest)
	{
		$icon_exists = file_exists($dest) && md5_file($src) === md5_file($dest);
		if (!$icon_exists)
			$icon_exists = Service::isPrestashop155plus() ? Tools::copy($src, $dest) : copy($src, $dest);

		return $icon_exists;
	}

	private function setCarrierLogo($id_carrier, $logo)
	{
		$return = true;

		$return = $return && $this->setIcon(_PS_MODULE_DIR_.$this->name.'/views/img/'.(string)$logo.'.jpg', _PS_SHIP_IMG_DIR_.(int)$id_carrier.'.jpg');
		$return = $return && $this->setIcon(
			_PS_MODULE_DIR_.$this->name.'/views/img/'.(string)$logo.'.jpg',
			_PS_TMP_IMG_DIR_.'carrier_mini_'.(int)$id_carrier.'_'.$this->context->language->id.'.jpg'
		);

		return $return;
	}

	public static function getModuleFolder($folder = '')
	{
		switch ((string)$folder)
		{
			case 'pdf':
			case 'log':
				$folder .= DIRECTORY_SEPARATOR;
				break;

			default:
				$folder = '';
				break;
		}
		$folder = _PS_MODULE_DIR_.self::MODULE_NAME.DIRECTORY_SEPARATOR.$folder;
		if (! is_dir($folder))
		{
			mkdir($folder, 0755);
			$index_file = 'index.php';
			@copy(dirname(__FILE__).DIRECTORY_SEPARATOR.$index_file, $folder.$index_file);
		}

		return $folder;
	}

	public static function getCarrierTrackingUrl()
	{
		// return (string)self::TRACKING_URL.'customerReference=@';
		return (string)self::TRACKING_URL.'itemCode=@';
	}

	public static function getModuleHooks($all = false)
	{
		$specific = array();
		
		if ($all)
			foreach (self::$_hooks_specific as $ver => $hooks)
				$specific = array_merge($specific, $hooks);
		else
		{
			$ver = (string)Service::getPsVer();
			if (isset(self::$_hooks_specific[$ver]))
				$specific = self::$_hooks_specific[$ver];
		}

		return array_merge(self::$_hooks_common, $specific);
	}

	private function getTemplate($tpl = '', $type = 'front')
	{
		return (string)dirname(__FILE__).'/views/templates/'.(string)$type.DIRECTORY_SEPARATOR.(string)$tpl;
	}

	private function getCartBpost($id_cart = 0)
	{
		$cart_bpost = false;
		if ($id_cart)
		{
			// Service instance is required for Autoload to function
			// correctly in the Admin context ?!
			Service::getInstance($this->context);
			$cart_bpost = PsCartBpost::getByPsCartID((int)$id_cart);
		}

		return $cart_bpost;
	}

	private function getOrderBpost($id_order = 0)
	{
		$order_bpost = false;
		if ($id_order)
		{
			Service::getInstance($this->context);
			$order_bpost = PsOrderBpost::getByPsOrderID((int)$id_order);
		}

		return $order_bpost;
	}

	private function shmSupportedAddress($shm = 0, $id_address_delivery = 0)
	{
		$supported = false;
		if ($shm && $id_address_delivery)
		{
			$service = Service::getInstance($this->context);
			$delivery_address = new Address($id_address_delivery);
			$iso_country = Tools::strtoupper(Country::getIsoById($delivery_address->id_country));
			$supported = (bool)in_array($shm, $service->getSupportedDeliveryMethods($iso_country));
		}

		return $supported;
	}

	private function isBpostShmCarrier($id_carrier)
	{
		return (bool)in_array((int)$id_carrier, $this->getIdCarriers());
	}

	public static function invalidGmapsApiKey($gak = '')
	{
		// return (bool)(empty($gak) || 39 !== (int)Tools::strlen($gak) || self::DEF_GMAK == $gak);
		return (bool)(empty($gak) || 39 !== (int)Tools::strlen($gak));
	}

	private function getContextLangIso()
	{
		$iso_code = Tools::strtolower($this->context->language->iso_code);
		
		return in_array($iso_code, array('de', 'fr', 'nl', 'en')) ? $iso_code : 'en';
	}

	private function postValidation()
	{
		$errors = array();

		// bpost last settings tab used
		$last_set_tab = Tools::getValue('last_set_tab', 0);

		$id_account = Tools::getValue(
			'account_id_account',
			Configuration::get('BPOST_ACCOUNT_ID')
		);
		$passphrase = Tools::getValue(
			'account_passphrase',
			Configuration::get('BPOST_ACCOUNT_PASSPHRASE')
		);
		// Srg: 28-aug-18
		/*$api_url = Tools::getValue(
			'account_api_url',
			Configuration::get('BPOST_ACCOUNT_API_URL')
		);*/
		// Gmaps API key
		$gmaps_api_key = Tools::getValue(
			'gmaps_api_key',
			Configuration::get('BPOST_GMAPS_API_KEY')
		);
		// Store details
		$store_details = Tools::getValue(
			'store_details',
			Configuration::get('BPOST_STORE_DETAILS')
		);
		//
		$home_24h_business = Tools::getValue(
			'home_24h_business',
			Configuration::get('BPOST_HOME_24H_BUSINESS')
		);
		//
		$delivery_options_list = Tools::getValue(
			'delivery_options_list',
			Configuration::get('BPOST_DELIVERY_OPTIONS_LIST')
		);
		//
		$display_delivery_date = Tools::getValue(
			'display_delivery_date',
			Configuration::get('BPOST_DISPLAY_DELIVERY_DATE')
		);
		$choose_delivery_date = Tools::getValue(
			'choose_delivery_date',
			Configuration::get('BPOST_CHOOSE_DELIVERY_DATE')
		);
		$num_dates_shown = Tools::getValue(
			'num_dates_shown',
			Configuration::get('BPOST_NUM_DATES_SHOWN')
		);
		if (empty($num_dates_shown))
			$num_dates_shown = 5;

		$ship_delay_days = Tools::getValue(
			'ship_delay_days',
			Configuration::get('BPOST_SHIP_DELAY_DAYS')
		);
		$cutoff_time = Tools::getValue(
			'cutoff_time',
			Configuration::get('BPOST_CUTOFF_TIME')
		);
		if (empty($cutoff_time))
			$cutoff_time = self::DEF_CUTOFF;

		$hide_date_oos = Tools::getValue(
			'hide_date_oos',
			Configuration::get('BPOST_HIDE_DATE_OOS')
		);
		//
		$display_international_delivery = Tools::getValue(
			'display_international_delivery',
			Configuration::get('BPOST_INTERNATIONAL_DELIVERY')
		);
		$intl_countries = Tools::getValue(
			'intl_countries',
			Configuration::get('BPOST_INTL_COUNTRIES')
		);
		$intl_ead = Tools::getValue(
			'intl_ead',
			Configuration::get('BPOST_INTL_EAD')
		);
		//
		$label_use_ps_labels = Tools::getValue(
			'label_use_ps_labels',
			Configuration::get('BPOST_USE_PS_LABELS')
		);
		$order_display_days = Tools::getValue(
			'order_display_days',
			Configuration::get('BPOST_ORDER_DISPLAY_DAYS')
		);
		if (empty($order_display_days))
			$order_display_days = self::DEF_ORDER_BPOST_DAYS;

		$display_order_states = Tools::getValue(
			'display_order_states',
			Configuration::get('BPOST_DISPLAY_ORDER_STATES')
		);
		if (empty($display_order_states))
			$display_order_states = (string)implode(',', $this->getDefaultOrderStatesArray());

		$treated_order_state = Tools::getValue(
			'treated_order_state',
			Configuration::get('BPOST_TREATED_ORDER_STATE')
		);
		if (empty($treated_order_state))
			$treated_order_state = self::DEF_TREATED_ORDER_STATE;

		$treat_printed_order = Tools::getValue(
			'treat_printed_order',
			Configuration::get('BPOST_TREAT_PRINTED_ORDER')
		);
		$label_pdf_format = Tools::getValue(
			'label_pdf_format',
			Configuration::get('BPOST_LABEL_PDF_FORMAT')
		);
		$auto_retour_label = Tools::getValue(
			'auto_retour_label',
			Configuration::get('BPOST_AUTO_RETOUR_LABEL')
		);
		$label_tt_integration = Tools::getValue(
			'label_tt_integration',
			Configuration::get('BPOST_LABEL_TT_INTEGRATION')
		);
		/*
		$label_tt_frequency = Tools::getValue(
			'label_tt_frequency',
			Configuration::get('BPOST_LABEL_TT_FREQUENCY')
		);

		$label_tt_update_on_open = Tools::getValue(
			'label_tt_update_on_open', 0
		);
		*/
		// Srg: 3-apr-19: debug tests
		/*
		$debug_log_enable = Tools::getValue(
			'debug_log_enable',
			Configuration::get('BPOST_DEBUG_LOG_ENABLE')
		);
		*/
		// 
		// Srg: 22-aug-18
		if (self::invalidGmapsApiKey($gmaps_api_key))
		{
			// Srg: 19-sep-18 default key changes
			// $gmaps_api_key = '';
			// $errors[] = $this->l('You must now provide your own valid Google maps API key!');
			$gmaps_api_key = self::DEF_GMAK;
			$errors[] = $this->l('Invalid Gmaps key');
		}
		// 
		if (Tools::isSubmit('submitAccountSettings'))
		{
			$upd = true;
			if ((Configuration::get('BPOST_ACCOUNT_ID') !== $id_account && is_numeric($id_account)) || empty($id_account))
				$upd = $upd && Configuration::updateValue('BPOST_ACCOUNT_ID', (string)$id_account);
			if (Configuration::get('BPOST_ACCOUNT_PASSPHRASE') !== $passphrase)
				$upd = $upd && Configuration::updateValue('BPOST_ACCOUNT_PASSPHRASE', $passphrase);
			// Srg: 28-aug-18 (no longer a variable)
			/*if (Configuration::get('BPOST_ACCOUNT_API_URL') !== $api_url)
			{
				if (empty($api_url))
				{
					$errors[] = $this->l('API URL shall not be empty !');
					// $api_url = $this->api_url;
					$api_url = self::API_URL;
				}
				$upd = $upd && Configuration::updateGlobalValue('BPOST_ACCOUNT_API_URL', $api_url);
			}*/
			// Srg: 19-sep-18 - merchant own key | default
			if (Configuration::get('BPOST_GMAPS_API_KEY') !== $gmaps_api_key)
				$upd = $upd && Configuration::updateValue('BPOST_GMAPS_API_KEY', $gmaps_api_key);
			//
			if ((Configuration::get('BPOST_STORE_DETAILS') !== $store_details) && Service::isValidJSON($store_details))
				$upd = $upd && Configuration::updateValue('BPOST_STORE_DETAILS', $store_details);
		}
		elseif (Tools::isSubmit('submitDeliveryOptions'))
		{
			$upd = true;
			// Home national mode
			if (Configuration::get('BPOST_HOME_24H_BUSINESS') !== $home_24h_business
					&& is_numeric($home_24h_business))
				$upd = $upd && Configuration::updateValue('BPOST_HOME_24H_BUSINESS', (int)$home_24h_business);

			// home international mode
			if (Configuration::get('BPOST_INTERNATIONAL_DELIVERY') !== $display_international_delivery
					&& is_numeric($display_international_delivery))
				$upd = $upd && Configuration::updateValue('BPOST_INTERNATIONAL_DELIVERY', (int)$display_international_delivery);

			if (Configuration::get('BPOST_DELIVERY_OPTIONS_LIST') !== $delivery_options_list && Service::isValidJSON($delivery_options_list))
				$upd = $upd && Configuration::updateValue('BPOST_DELIVERY_OPTIONS_LIST', $delivery_options_list);

		}
		elseif (Tools::isSubmit('submitDeliverySettings'))
		{
			$upd = true;
			// Display delivery date
			if (Configuration::get('BPOST_DISPLAY_DELIVERY_DATE') !== $display_delivery_date
					&& is_numeric($display_delivery_date))
				$upd = $upd && Configuration::updateValue('BPOST_DISPLAY_DELIVERY_DATE', (int)$display_delivery_date);

			// Choose delivery date
			if (Configuration::get('BPOST_CHOOSE_DELIVERY_DATE') !== $choose_delivery_date
					&& is_numeric($choose_delivery_date))
				$upd = $upd && Configuration::updateValue('BPOST_CHOOSE_DELIVERY_DATE', (int)$choose_delivery_date);

			// Number of dates shown
			if (Configuration::get('BPOST_NUM_DATES_SHOWN') !== $num_dates_shown
					&& is_numeric($num_dates_shown))
				$upd = $upd && Configuration::updateValue('BPOST_NUM_DATES_SHOWN', (int)$num_dates_shown);

			// Delay days before shipping
			if (Configuration::get('BPOST_SHIP_DELAY_DAYS') !== $ship_delay_days
					&& is_numeric($ship_delay_days))
				$upd = $upd && Configuration::updateValue('BPOST_SHIP_DELAY_DAYS', (int)$ship_delay_days);

			// Next day cut off time
			if (Configuration::get('BPOST_CUTOFF_TIME') !== $cutoff_time)
					// && is_numeric($cutoff_time))
				$upd = $upd && Configuration::updateValue('BPOST_CUTOFF_TIME', $cutoff_time);

			// Hide date when out of stock
			if (Configuration::get('BPOST_HIDE_DATE_OOS') !== $hide_date_oos
					&& is_numeric($hide_date_oos))
				$upd = $upd && Configuration::updateValue('BPOST_HIDE_DATE_OOS', (int)$hide_date_oos);

		}
		elseif (Tools::isSubmit('submitIntlSettings'))
		{
			$upd = true;
			if (Configuration::get('BPOST_INTL_COUNTRIES') !== $intl_countries && Service::isValidJSON($intl_countries))
				$upd = $upd && Configuration::updateValue('BPOST_INTL_COUNTRIES', $intl_countries);

			// international EAD
			if (Configuration::get('BPOST_INTL_EAD') !== $intl_ead && Service::isValidJSON($intl_ead))
				$upd = $upd && Configuration::updateValue('BPOST_INTL_EAD', $intl_ead);

		}
		elseif (Tools::isSubmit('submitLabelSettings'))
		{
			$upd = true;
			if (Configuration::get('BPOST_USE_PS_LABELS') !== $label_use_ps_labels && is_numeric($label_use_ps_labels))
				$upd = $upd && Configuration::updateValue('BPOST_USE_PS_LABELS', (int)$label_use_ps_labels);

			if ($label_use_ps_labels)
			{
				if (Configuration::get('BPOST_LABEL_PDF_FORMAT') !== $label_pdf_format)
					$upd = $upd && Configuration::updateValue('BPOST_LABEL_PDF_FORMAT', $label_pdf_format);
				/*
				if (Configuration::get('BPOST_LABEL_TT_FREQUENCY') !== $label_tt_frequency && is_numeric($label_tt_frequency))
					Configuration::updateValue('BPOST_LABEL_TT_FREQUENCY', (int)$label_tt_frequency);
				*/
				$this->installModuleTab(
					self::ADMIN_CTLR,
					'bpost',
					Tab::getIdFromClassName('AdminParentOrders')
				);
			}
			else
			{
				$auto_retour_label = false;
				$label_tt_integration = false;
				// $label_tt_update_on_open = false;

				$this->uninstallModuleTab(self::ADMIN_CTLR);
			}

			if (Configuration::get('BPOST_ORDER_DISPLAY_DAYS') !== $order_display_days && is_numeric($order_display_days))
				$upd = $upd && Configuration::updateValue('BPOST_ORDER_DISPLAY_DAYS', (int)$order_display_days);
			if (Configuration::get('BPOST_DISPLAY_ORDER_STATES') !== $display_order_states)
				$upd = $upd && Configuration::updateValue('BPOST_DISPLAY_ORDER_STATES', (string)$display_order_states);
			if (Configuration::get('BPOST_TREATED_ORDER_STATE') !== $treated_order_state && is_numeric($treated_order_state))
				$upd = $upd && Configuration::updateValue('BPOST_TREATED_ORDER_STATE', (int)$treated_order_state);
			if (Configuration::get('BPOST_TREAT_PRINTED_ORDER') !== $treat_printed_order && is_numeric($treat_printed_order))
				$upd = $upd && Configuration::updateValue('BPOST_TREAT_PRINTED_ORDER', (int)$treat_printed_order);
			if (Configuration::get('BPOST_AUTO_RETOUR_LABEL') !== $auto_retour_label && is_numeric($auto_retour_label))
				$upd = $upd && Configuration::updateValue('BPOST_AUTO_RETOUR_LABEL', (int)$auto_retour_label);
			if (Configuration::get('BPOST_LABEL_TT_INTEGRATION') !== $label_tt_integration && is_numeric($label_tt_integration))
				$upd = $upd && Configuration::updateValue('BPOST_LABEL_TT_INTEGRATION', (int)$label_tt_integration);
			// if (Configuration::get('BPOST_LABEL_TT_UPDATE_ON_OPEN') !== $label_tt_update_on_open
			// 		&& is_numeric($label_tt_update_on_open))
			// 	Configuration::updateValue('BPOST_LABEL_TT_UPDATE_ON_OPEN', (int)$label_tt_update_on_open);
			// if (Configuration::get('BPOST_DEBUG_LOG_ENABLE') !== $debug_log_enable && is_numeric($debug_log_enable))
			// 	$upd = $upd && Configuration::updateValue('BPOST_DEBUG_LOG_ENABLE', (int)$debug_log_enable);
		}

		$config_updated = isset($upd) ? $upd : false;
		$this->smarty->assign('last_set_tab', (int)$last_set_tab, true);
		//
		//
		// Srg: 28-apr-2021 (valid account) refactor
		$valid_account = !empty($id_account) && !empty($passphrase);
		$service = Service::getInstance($this->context);
		$product_config = $service->getProductConfig();
		if (isset($product_config['Error']))
		{
			$err_msg = (string)$product_config['Error'];
			if (preg_match('/invalid\s?account\s?id/i', $err_msg))
			{
				$valid_account = false;
				$err_msg = $this->l('Invalid Account ID / Passphrase');
			}

			$errors[] = $err_msg;
		}

		$iso_code = (string)Service::getSupportedLangIso($this->context->language->iso_code);
		$this->smarty->assign('iso_code', $iso_code, true);
		$this->smarty->assign('iso_lang', Tools::strtolower($iso_code), true);
		// 
		// account settings
		$this->smarty->assign('account_id_account', $id_account, true);
		$this->smarty->assign('account_passphrase', $passphrase, true);
		// 
		// gmaps api key
		$this->smarty->assign('gmaps_api_key', $gmaps_api_key, true);
		// store details
		$store_details_info = $this->getStoreDetailsInfo($store_details);
		if (isset($store_details_info['Error']))
		{
			$errors[] = $store_details_info['Error'];
			unset($store_details_info['Error']);
		}
		$this->smarty->assign('store_details_info', $store_details_info);
		//
		if ($valid_account)
		{
			// delivery settings
			$this->smarty->assign('display_delivery_date', $display_delivery_date, true);
			$this->smarty->assign('choose_delivery_date', $choose_delivery_date, true);
			$this->smarty->assign('num_dates_shown', $num_dates_shown, true);
			$this->smarty->assign('ship_delay_days', $ship_delay_days, true);
			$this->smarty->assign('cutoff_time', $cutoff_time, true);
			$this->smarty->assign('hide_date_oos', $hide_date_oos, true);
			// delivery options
			$delivery_options_list = !empty($delivery_options_list) ? Tools::jsonDecode($delivery_options_list, true) : array();
			$delivery_options = array(
				self::SHM_HOME => array(
					// 'name' => 'home',
					'title' => $this->l('Home delivery: Belgium'),
					'opts' => '470|300|350|330',
					),
				self::SHM_PPOINT => array(
					// 'name' => 'bpost',
					'title' => $this->l('Post point: Belgium'),
					'opts' => '470|350',
					),
				self::SHM_PLOCKER => array(
					// 'name' => '247',
					'title' => $this->l('Parcel locker: Belgium'),
					'opts' => '470|350',
					),
				self::SHM_INTL => array(
					// 'name' => 'intl',
					'title' => $this->l('Home delivery: International'),
					'opts' => '540',
					),
				self::SHM_PPI => array(
					// 'name' => 'ppi',
					'title' => $this->l('Post point: International'),
					'opts' => '540',
					),
				);
			foreach ($delivery_options as $dm => $options)
			{
				$selected_opts = isset($delivery_options_list[$dm]) ? $delivery_options_list[$dm] : array();
				$opts = explode('|', $options['opts']);
				$dm_opts = array();
				foreach ($opts as $key)
				{
					if (!isset($this->_all_delivery_options[$key])) continue;

					$has_opt = isset($selected_opts[$key]);
					$option = array(
						'checked' => $has_opt,
						'from' => '0.0',
						);
					if (470 === (int)$key) $option['cost'] = '0.0';
					if ($has_opt)
					{
						$option['from'] = $value = $selected_opts[$key];
						if (is_array($value))
						{
							$option['from'] = $value[0];
							$option['cost'] = $value[1];
						}
					}

					$dm_opts[$key] = $option;
				}
				$options['opts'] = $dm_opts;
				$delivery_options[$dm] = $options;
			}
			$this->smarty->assign('delivery_options', $delivery_options, true);
			$this->smarty->assign('delivery_options_info', $this->_all_delivery_options);
			// international settings
			$this->smarty->assign('display_international_delivery', $display_international_delivery, true);
			// disabling country settings
			$country_international_orders = false;
			$this->smarty->assign('country_international_orders', $country_international_orders, true);
			//
			$enabled_business24 = $product_config['24hBusiness'];
			$product_countries = $product_config['countries'];
			$this->smarty->assign('enabled_business24', $enabled_business24, true);
			// 1-off special case (account change)
			if ($home_24h_business && !$enabled_business24)
			{
				$home_24h_business = false;
				Configuration::updateValue('BPOST_HOME_24H_BUSINESS', (int)$home_24h_business);
			}
			$this->smarty->assign('home_24h_business', (int)$home_24h_business, true);
			//
			$this->smarty->assign('product_countries', $product_countries, true);
			// ead
			$product_features = Feature::getFeatures($this->context->language->id);
			$this->smarty->assign('product_features', $product_features, true);
			$list_countries = $this->getCountryList();
			$this->smarty->assign('list_countries', $list_countries, true);
			$this->smarty->assign('intl_ead', $intl_ead, true);
			// cron info
			$this->smarty->assign('module_cron_info', $this->getCronInfo(), true);
			// Label settings
			$this->smarty->assign('label_use_ps_labels', $label_use_ps_labels, true);
			// order display days
			$this->smarty->assign('order_display_days', $order_display_days, true);
			// Order states
			$order_states = $this->getOrderedStatuses();
			$this->smarty->assign('order_states', $order_states, true);
			// display order states filter
			// $display_order_states = explode(',', '2,3,4,5');
			// $display_order_states = $this->getDefaultOrderStatesArray();
			$display_order_states = explode(',', $display_order_states);
			$this->smarty->assign('display_order_states', $display_order_states, true);
			//
			$this->smarty->assign('treated_order_state', $treated_order_state, true);
			$this->smarty->assign('treat_printed_order', $treat_printed_order, true);
			$this->smarty->assign('label_pdf_format', $label_pdf_format, true);
			$this->smarty->assign('auto_retour_label', $auto_retour_label, true);
			$this->smarty->assign('label_tt_integration', $label_tt_integration, true);
			// $this->smarty->assign('label_tt_frequency', (int)$label_tt_frequency, true);
			// $this->smarty->assign('label_tt_update_on_open', $label_tt_update_on_open, true);
			// $this->smarty->assign('debug_log_enable', $debug_log_enable, true);
		} // valid_account

		$this->smarty->assign('valid_account', $valid_account, true);
		$this->smarty->assign('errors', $errors, true);
		// URLs
		// $url_test_gmaps = 'https://maps.googleapis.com/maps/api/geocode/json?address=Paris,+FR&key=';
		$url_test_gmaps = 'https://maps.googleapis.com/maps/api/geocode/json';
		$this->smarty->assign('url_test_gmaps', $url_test_gmaps, true);
		// Country information
		$this->smarty->assign('url_get_enabled_countries', $this->getLink('webservicextra',
			array(
				'ajax'					=> true,
				'get_enabled_countries'	=> true,
				'id_shop' 				=> $this->context->shop->id,
				'admin'					=> true,
				'token'					=> Tools::getAdminToken($this->name),
		)), true);

		return (bool)$config_updated && empty($errors) ;
	}

	/**
	 * @return mixed
	 */
	public function getContent()
	{
		$msg = (bool)$this->postValidation() ?
			$this->displayConfirmation($this->l('Settings updated.')) : null;
		$this->smarty->assign('version', Service::getPsVer(), true);
		$this->smarty->assign('is_v161', (bool)Service::isPrestashop161plus(), true);
		// srg: 23-apr-21: settings restructure
		$this->smarty->assign('settings_src', $this->_path.'views/js/eon.jquery.settings.min.js', true);
		//

		return $msg.$this->display(__FILE__, 'views/templates/admin/settings/settings.tpl', null, null);

	}

	protected function getStoreDetailsInfo($store_details = '')
	{
		$store_details_info = !empty($store_details) ? Tools::jsonDecode($store_details, true) :
			array(
				'name'		=> Service::getBpostring(Configuration::get('PS_SHOP_NAME')),
				'address1' 	=> Configuration::get('PS_SHOP_ADDR1'),
				// 'address2' 	=> Configuration::get('PS_SHOP_ADDR2'),
				'city' 		=> Configuration::get('PS_SHOP_CITY'),
				'postcode' 	=> Configuration::get('PS_SHOP_CODE'),
				// 'id_country'=> Configuration::get('PS_SHOP_COUNTRY_ID'),
				'email' 	=> Configuration::get('PS_SHOP_EMAIL'),
				'phone'		=> Configuration::get('PS_SHOP_PHONE'),
				);

		foreach ($store_details_info as $index => $value)
			$store_details_info[$index] = array('value' => $value);

		$form_items = array(
			'name' => array(
				'title' => $this->l('Store name'),
				'description' => $this->l('Displayed in emails and page titles'),
				'required' => true,
				'max' => 40,
				),
			'address1' => array(
				'title' => $this->l('Address'),
				'required' => true,
				'max' => 40,
				),
			// 'address2' => array(
			// 	'title' => '',
			// 	'required' => false,
			// 	'max' => 40,
			// 	),
			'city' => array(
				'title' => $this->l('City'),
				'required' => true,
				'max' => 40,
				),
			'postcode' => array(
				'title' => $this->l('Postcode'),
				'required' => true,
				'max' => 32,
				'invalid' => $this->l('INVALID_POSTCODE'),
				'pattern' => '^[0-9]{4}$',
				),
			'country' => array(
				'value' => $this->l('Belgium'),
				'title' => $this->l('Country'),
				'required' => false,
				'max' => 0,
				),
			'email' => array(
				'title' => $this->l('Email'),
				'required' => false,
				'max' => 50,
				),
			'phone' => array(
				'title' => $this->l('Phone'),
				'required' => false,
				'max' => 20,
				),
			);

		$errors = array();
		// order is important
		$store_details_info = array_merge_recursive($form_items, $store_details_info);
		// foreach ($store_details_info as $key => $detail)
		foreach ($store_details_info as $detail)
			if ($detail['required'] && empty($detail['value']))
				$errors[] = $detail['title'];

		if (!empty($errors))
			$store_details_info['Error'] = sprintf($this->l('Store details for (%s) cannot be empty.'), implode(', ', $errors));

		return $store_details_info;
	}

	/**
	 * [getCountryList revised array of countries]
	 * @return array
	 * Srg: 29-04-21
	 */
	protected function getCountryList()
	{
		$country_list = array();
		$country_list = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
		SELECT c.`iso_code`, cl.`name`
		FROM `'._DB_PREFIX_.'country` c
		LEFT JOIN `'._DB_PREFIX_.'country_lang` cl ON (c.`id_country` = cl.`id_country` AND cl.`id_lang` = '.(int)$this->context->language->id.')
		ORDER BY `name` ASC');

		return $country_list;
	}

	/**
	 * [getOrderedStatuses revised array of statuses]
	 * @return array statuses
	 * Srg: 31-03-19
	 */
	protected function getOrderedStatuses()
	{
		$ordered_statuses = array();
		$order_states = OrderState::getOrderStates((int)$this->context->language->id);

		foreach ($order_states as $state)
			$ordered_statuses[$state['id_order_state']] = array(
					'name' => $state['name'],
					'color' => $state['color'],
					'invoice' => $state['invoice'],
					'paid' => $state['paid'],
					'shipped' => $state['shipped'],
				);

		ksort($ordered_statuses);

		return $ordered_statuses;
	}

	public function getDeliveryOptions($selection = '', $inc_info = false)
	{
		if (empty($selection))
			return $this->_all_delivery_options;

		$options = array();
		$selection = explode('|', $selection);
		foreach ($selection as $key)
			if (isset($this->_all_delivery_options[$key]))
				$options[$key] = $inc_info ? $this->_all_delivery_options[$key] : $this->_all_delivery_options[$key]['title'];

		return $options;
	}

	public function getCronInfo()
	{
		return array(
			'msg' => $this->l('CRON MESSAGE'),
			'url' => Tools::getHttpHost(true, true).__PS_BASE_URI__.
				// substr($_SERVER['SCRIPT_NAME'], strlen(__PS_BASE_URI__), -strlen($current_file_name['0'])).
				'modules/'.$this->name.
				'/cron.php?token='.Tools::substr(_COOKIE_KEY_, 34, 8),
			);
	}

	public function cronTask()
	{
		Service::getInstance($this->context);

		Service::refreshBulkOrderBpostStatus();
	}

	/**
	 * [getIdCarriers get bpost shipping method -> carrier ids if they exist ]
	 * @author Serge <serge@stigmi.eu>
	 * @return mixed array of 'bpost shipping method' => 'current id_carrier' (null if missing)
	 */
	public function getIdCarriers()
	{
		if (empty($this->carriers))
			foreach (array_keys($this->shipping_methods) as $shipping_method)
				$this->carriers[$shipping_method] = ($id_carrier = (int)Configuration::get('BPOST_SHIP_METHOD_'.$shipping_method.'_ID_CARRIER'))
					? $id_carrier : null;

		return $this->carriers;
	}

	public function getShmFromCarrierID($carrier_id = 0)
	{
		$return = false;
		foreach ($this->getIdCarriers() as $shm => $id_carrier)
			if ($id_carrier == $carrier_id)
			{
				$return = $shm;
				break;
			}

		return $return;
	}

	public function getCarrierIDFromShm($shm)
	{
		return (int)Configuration::get('BPOST_SHIP_METHOD_'.(int)$shm.'_ID_CARRIER');
	}

	public function getCarrierTitles()
	{
		$titles = array();
		foreach ($this->shipping_methods as $shm => $info)
			$titles[$this->getCarrierIDFromShm($shm)] = $info['lname'];

		return $titles;
	}

	protected function getCarrierTexts()
	{
		$texts = array();
		foreach ($this->shipping_methods as $shm => $info)
		{
			$iso_code = (string)Service::getSupportedLangIso(Language::getIsoById((int)$this->context->language->id), true);
			$texts[$this->getCarrierIDFromShm($shm)] = array(
				$info['lname'],
				$info['delay'][$iso_code],
			);
		}

		return $texts;
	}

	// function syncCheckCarrierRefs($fix = false)
	public function syncCheckCarrierRefs($fix = false)
	{
		$in_sync = false;
		$sql = '
SELECT `id_carrier`, `id_reference`, `name`
FROM `'._DB_PREFIX_.'carrier`
WHERE `external_module_name` = "'.$this->name.'"
AND deleted = 0
ORDER BY `id_reference`';
		if ($carrier_refs = Db::getInstance()->executeS($sql))
		{
			$cur_carrier_ids = $this->getIdCarriers();
			$carrier_refs = array_combine(array_keys($cur_carrier_ids), $carrier_refs);
			$bad_sync = array();
			foreach ($carrier_refs as $shm => $cref)
				if ((int)$cur_carrier_ids[$shm] !== (int)$cref['id_carrier'])
					$bad_sync[$shm] = (int)$cref['id_carrier'];

			$in_sync = empty($bad_sync);
			if (!$in_sync && (bool)$fix)
			{
				$return = true;
				foreach ($bad_sync as $shm => $id_carrier)
					$return = $return && Configuration::updateGlobalValue('BPOST_SHIP_METHOD_'.$shm.'_ID_CARRIER', (int)$id_carrier);

				return $return;
			}
		}

		return $in_sync;
	}

	/**
	 * Deprecated
	 * @return string SQL order strings to display
	 */
	public function getOrderStateListSQL()
	{
		$os_exclude = array(1, 6, 10);
		$return = false;
		if (empty($this->order_states_inc) || !is_array($this->order_states_inc))
			$return = 'NOT IN('.implode(', ', $os_exclude).')';
		else
			// $return = sprintf('IN(%s, %d)', implode(', ', $this->order_states_inc), (int)Configuration::get('BPOST_ORDER_STATE_TREATED'));
			$return = 'IN('.implode(', ', $this->order_states_inc).')';

		return $return;
	}

	/**
	 * Default order states
	 * @return array default visible admin order states
	 */
	public function getDefaultOrderStatesArray()
	{
		$result = array();
		$os_exclude = array(1, 6, 7, 10);
		$order_states = OrderState::getOrderStates((int)$this->context->language->id);
		foreach ($order_states as $state)
			if (! in_array($state['id_order_state'], $os_exclude))
				$result[] = $state['id_order_state'];

		return $result;
	}

	public function getLink($controller = 'default', array $url_params = array())
	{
		return $this->context->link->getModuleLink($this->name, $controller, $url_params, true);
	}

	/**
	 * changeOrderState (Context dependant).
	 * Exception on errors *
	 * @author Serge <serge@stigmi.eu>
	 * @param  string $reference
	 * @param  string $state_name Order state Name
	 * @return bool true on success
	 */
	public function changeOrderState($reference = '', $state_name = '')
	{
		$errors = array();
		$response = true;
		$valid_state_name = true;
		$id_order_state = $set_b4_printed = $mark_treated = false;
		switch ((string)$state_name)
		{
			case 'Cancelled':
				$ps_order_states = OrderState::getOrderStates($this->context->language->id);
				foreach ($ps_order_states as $ps_order_state)
					if ('order_canceled' == $ps_order_state['template'])
					{
						// $order_state['id'] = $ps_order_state['id_order_state'];
						$id_order_state = (int)$ps_order_state['id_order_state'];
						$set_b4_printed = true;
						break;
					}

				if (!$id_order_state)
					$errors[] = $this->l('Please create a "Cancelled" order state using "order_canceled" template.');

				break;

			case 'Treated':
				$mark_treated = true;
				$id_order_state = (int)Configuration::get('BPOST_TREATED_ORDER_STATE');
				break;

			default:
				$valid_state_name = false;
				$errors[] = $this->l('Invalid order state');
				break;

		}

		if ($ps_order_id = (int)Service::getOrderIDFromReference($reference))
		{
			$order_bpost = $this->getOrderBpost($ps_order_id);
			// before or after? labels are PRINTED
			if ($valid_state_name && !($set_b4_printed ^ (bool)$order_bpost->countPrinted()))
			{
				$when = $set_b4_printed ? $this->l('before') : $this->l('after');
				$errors[] = str_replace(
				array(
					'%reference%',
					'%state%',
					'%when%'
					),
				array(
					$reference,
					Tools::strtolower($this->l($state_name)),
					$when
					),
				$this->l('Order ref. %reference% was not %state% : action is only available for orders %when% they are printed.'));
			}
		}
		else
			$errors[] = $this->l('Invalid reference');

		if (!empty($errors))
		// {
			throw new Exception(implode(', ', $errors));
			// return false;
		// }

		// >> Srg: 23-apr-17: avoid unnecessary change
		if ((bool)$mark_treated && (bool)$order_bpost->treated)
			return $response;
		// << Srg

		$order_bpost->current_state = (int)$id_order_state;
		$order_bpost->treated = (bool)$mark_treated;
		$order_bpost->save();

		$ps_order = new Order((int)$ps_order_id);

		// Create new OrderHistory
		$history = new OrderHistory();
		$history->id_order = $ps_order->id;
		$history->id_employee = (int)$this->context->employee->id;
		// 1.5+ specific $use_existing_payment
		$use_existings_payment = !$ps_order->hasInvoice();
		$history->changeIdOrderState((int)$id_order_state, $ps_order->id, $use_existings_payment);

		$carrier = new Carrier($ps_order->id_carrier, $ps_order->id_lang);
		$template_vars = array();
		if ($history->id_order_state == Configuration::get('PS_OS_SHIPPING') && $ps_order->shipping_number)
			$template_vars = array('{followup}' => str_replace('@', $ps_order->shipping_number, $carrier->url));
		// Save all changes
		$response = $response && $history->addWithemail(true, $template_vars);

		return $response;
	}

	/**
	 * public function hookUpdateCarrier($params)
	 * 
	 * see install / uninstall for listing
	 * @author Serge <serge@stigmi.eu>
	 * @param array $params
	 */
	public function hookActionCarrierUpdate($params)
	{
		if (!empty($params['id_carrier']))
		{
			if ($shipping_method = array_search((int)$params['id_carrier'], $this->getIdCarriers()))
				Configuration::updateGlobalValue('BPOST_SHIP_METHOD_'.$shipping_method.'_ID_CARRIER', (int)$params['carrier']->id);
		}
	}

	/* public function hookHeader($params) */
	public function hookDisplayHeader()
	{
		$php_self = isset($this->context->controller->php_self) ? $this->context->controller->php_self : null;
		$load_header = isset($php_self) && false !== strripos($php_self, 'order');
		if ($load_header)
		{
			$this->context->controller->addCSS($this->_path.'views/css/carrier-box.css');
			$this->context->controller->addJS($this->_path.'views/js/eon.jquery.base.min.js');
			if (Service::isPrestashop17plus())
				$this->context->controller->addJS($this->_path.'views/js/eon.jquery.checkout.min.js');
			else
				$this->context->controller->addJS($this->_path.'views/js/cb-target.js');
		}
	}

	public function hookDisplayMobileHeader($params)
	{
		// $this->hookHeader($params);
		$this->hookDisplayHeader($params);
	}

	/**
	 * public function hookBeforeCarrier($params)
	 * 
	 * @param $params
	 * @return bool
	 */
	public function hookDisplayBeforeCarrier($params)
	{
		$cart = !empty($this->context->cart) ? $this->context->cart : $params['cart'];

		$def_id_carrier = (int)$cart->id_carrier;
		$carriers = $this->getIdCarriers();
		// $iso_country = 'BE';
		if ($id_address_delivery = (int)$cart->id_address_delivery)
		{
			// >> Srg: 12-jul-19: Selected country must be enabled to have any carriers
			$no_carrier_addresses = $cart->getDeliveryAddressesWithoutCarriers();
			if (! empty($no_carrier_addresses) && in_array($id_address_delivery, $no_carrier_addresses))
				return;
			// <<
			$id_zone_delivery = Address::getZoneById($id_address_delivery);
			$our_carrier = false;
			foreach ($carriers as $id_carrier_bpost)
				if ($our_carrier = $cart->isCarrierInRange($id_carrier_bpost, $id_zone_delivery))
					break;

			if (!$our_carrier)
				return;

			// not for 1.4 (or 1.7+)
			if (isset($params['delivery_option_list']))
			{
				$carrier_list_ids = array();
				// translated name from carrier name
				$delivery_option_list = $params['delivery_option_list'];
				if (isset($delivery_option_list[$id_address_delivery]))
				{
					$carrier_list = $delivery_option_list[$id_address_delivery];
					foreach ($carrier_list as $id_carrier => $carrier_options)
					{
						$id_carrier = (int)$id_carrier;
						$carrier_list_ids[] = $id_carrier;
						$carrier = $carrier_options['carrier_list'][$id_carrier]['instance'];
						$name_parts = explode('/', $carrier->name);
						if (count($name_parts))
							$carrier->name = $this->l(trim($name_parts[0]));

					}
				}
				// Prestashop 1.5+ bug fix for when
				// switching address and cart_carrier is not in carrier list
				if (count($carrier_list_ids) &&
					!in_array($def_id_carrier, $carrier_list_ids))
					$def_id_carrier = $carrier_list_ids[0];
			}

			// >> Srg: 25-apr-17: carriers lib now correctly takes care of carrier_id == 0

			$debug_mode = false;
			if ($debug_mode) $this->smarty->assign('debug_mode', $this->context->smarty->fetch($this->getTemplate('debug-bc.tpl', 'hook')), true);

			// >> srg: 20-jun-17: address-country
			// Srg: 20-sep-18: No longer used
			// $delivery_address = new Address($id_address_delivery);
			// $iso_country = Tools::strtoupper(Country::getIsoById($delivery_address->id_country));
			// <<
		}
		else
			$this->smarty->assign('no_address', $this->l('Please sign in to see bpost carriers.'));

		$this->smarty->assign('id_carrier', $def_id_carrier, true);
		$this->smarty->assign('url_carriers_js', $this->_path.'views/js/eon.jquery.carriers.min.js', true);
		//

		if ($carriers_shm = array_flip($carriers))
			$this->smarty->assign('carriers_shm', $carriers_shm, true);

		$l_messages = array(
			'sp_configure'	=> $this->l('Please configure selected bpost shipping method.'),
			'err_theme' => $this->l('Incompatible theme. Please contact bpost for an appropriate fix.'),
			'titles' => $this->getCarrierTitles(),
			'texts' => $this->getCarrierTexts(),
		);
		$this->smarty->assign('l_messages', $l_messages, true);
		$this->smarty->assign('version', Service::getPsVer(), true);
		//
		$url_params = array('token'	=> Tools::getToken($this->name));
		$this->smarty->assign('url_carrierbox', $this->getLink('carrierbox', $url_params), true);

		if (Service::isPrestashop17plus())
			return $this->fetch('module:bpostshm/views/templates/hook/before-carrier.tpl');
		else {
			$this->smarty->assign('inc_src', $this->_path.'views/js/eon.jquery.checkout.min.js', true);
			return $this->display(__FILE__, 'views/templates/hook/before-carrier.tpl', null, null);
		}
	}

	/**
	 * public function hookPaymentTop($params)
	 * 
	 * @param array $params
	 * @return bool|string
	 */
	public function hookDisplayPaymentTop($params)
	{
		// return $this->hookProcessCarrier($params);
		return $this->hookActionCarrierProcess($params);
	}

	/**
	 * public function hookProcessCarrier($params)
	 * 
	 * @param array $params
	 * @return bool|string
	 */
	public function hookActionCarrierProcess($params)
	{
		if (Service::isPrestashop17plus())
			return;
			
		$cart = !empty($this->context->cart) ? $this->context->cart : $params['cart'];
		if (!$cart->update())
			return false;

		$return = false;
		if ($shm = $this->getShmFromCarrierID($cart->id_carrier))
		{
			$warning = '';
			$logged_in = (bool)$this->context->cookie->logged;
			$cart_bpost = $this->getCartBpost((int)$cart->id);
			if (!$logged_in)
				$warning = '<p class="warning">'.Tools::displayError('Please sign in to see payment methods.').'</p>';
			elseif (!$this->checkedTOS() && Configuration::get('PS_CONDITIONS'))
				$warning = '<p class="warning">'.Tools::displayError('Please accept the Terms of Service.').'</p>';
			// >> Srg 16-12-17:
			elseif (!$this->shmSupportedAddress((int)$shm, (int)$cart->id_address_delivery))
				$warning = '<p class="warning">'.$this->l('Unable to proceed with your chosen address').'.</p>';
			// << Srg
			elseif (!$cart_bpost->validServicePointForSHM((int)$shm))
				$warning = '<p class="warning">'.$this->l('Please configure selected bpost shipping method.').'</p>';

			if ($logged_in && empty($warning))
				return false;

			$return = array(
				'HOOK_TOP_PAYMENT' => '',
				'HOOK_PAYMENT' => $warning,
				'summary' => $cart->getSummaryDetails(),
			);

			$return['HOOK_SHOPPING_CART'] = Hook::exec('displayShoppingCartFooter', $return['summary']);
			$return['HOOK_SHOPPING_CART_EXTRA'] = Hook::exec('displayShoppingCart', $return['summary']);

			$return['carrier_data'] = $this->getCarrierList($params);

			// if (Tools::getValue('ajax', false))
			// {
			// 	$method = (string)Tools::getValue('method', '');
			// 	$good_cause = Service::isPrestashop15plus() || (in_array($method, array('updateCarrierAndGetPayments', 'updateTOSStatusAndGetPayments')));
			// 	if ($good_cause)
			// 		die(Tools::jsonEncode($return));
			// }

			if (Tools::getValue('ajax', false))
			{
				$method = (string)Tools::getValue('method', '');
				$good_cause = !in_array($method, array('getAddressBlockAndCarriersAndPayments', ));
				if ($good_cause)
					die(Tools::jsonEncode($return));
			}

			return $warning;
		}

		return $return;
	}

	protected function checkedTOS()
	{
		// return (bool)Service::isPrestashop15plus() ? $this->context->cookie->check_cgv : $this->context->cookie->checkedTOS;
		// there is no reliable version dependant way to check for this now. It's a lottery.
		return (bool)$this->context->cookie->check_cgv || $this->context->cookie->checkedTOS;
	}

	private function getCarrierList($params)
	{
		$cart = !empty($this->context->cart) ? $this->context->cart : $params['cart'];
		$id_address_delivery = (int)$cart->id_address_delivery;
		$address_delivery = new Address($id_address_delivery);

		$cms = new CMS(Configuration::get('PS_CONDITIONS_CMS_ID'), $this->context->language->id);
		$link_conditions = $this->context->link->getCMSLink($cms, $cms->link_rewrite);
		if (!strpos($link_conditions, '?'))
			$link_conditions .= '?content_only=1';
		else
			$link_conditions .= '&content_only=1';

		$carriers = $cart->simulateCarriersOutput();
		$delivery_option = $cart->getDeliveryOption(null, false, false);

		$wrapping_fees_tax_inc = $wrapping_fees = $cart->getGiftWrappingPrice();
		$old_message = Message::getMessageByCartId((int)$cart->id);

		$free_shipping = false;
		foreach ($cart->getCartRules() as $rule)
		{
			if ($rule['free_shipping'] && !$rule['carrier_restriction'])
			{
				$free_shipping = true;
				break;
			}
		}

		$this->context->smarty->assign('isVirtualCart', $cart->isVirtualCart());

		// $delivery_option_list = $id_address_delivery ? $cart->getDeliveryOptionList() : array();
		$delivery_option_list = $cart->getDeliveryOptionList();
		$vars = array(
			'free_shipping' => $free_shipping,
			'checkedTOS' => (int)$this->checkedTOS(),
			'recyclablePackAllowed' => (int)Configuration::get('PS_RECYCLABLE_PACK'),
			'giftAllowed' => (int)Configuration::get('PS_GIFT_WRAPPING'),
			'cms_id' => (int)Configuration::get('PS_CONDITIONS_CMS_ID'),
			'conditions' => (int)Configuration::get('PS_CONDITIONS'),
			'link_conditions' => $link_conditions,
			'recyclable' => (int)$cart->recyclable,
			'gift_wrapping_price' => (float)$wrapping_fees,
			'total_wrapping_cost' => Tools::convertPrice($wrapping_fees_tax_inc, $this->context->currency),
			'total_wrapping_tax_exc_cost' => Tools::convertPrice($wrapping_fees, $this->context->currency),
			'delivery_option_list' => $delivery_option_list,
			'carriers' => $carriers,
			'checked' => $cart->simulateCarrierSelectedOutput(),
			'delivery_option' => $delivery_option,
			'address_collection' => $cart->getAddressCollection(),
			'opc' => (bool)Configuration::get('PS_ORDER_PROCESS_TYPE'),
			'oldMessage' => isset($old_message['message'])? $old_message['message'] : '',
			'HOOK_BEFORECARRIER' => Hook::exec('displayBeforeCarrier', array(
				'carriers' => $carriers,
				// 'delivery_option_list' => $cart->getDeliveryOptionList(),
				'delivery_option_list' => $delivery_option_list,
				'delivery_option' => $delivery_option
			))
		);

		Cart::addExtraCarriers($vars);

		$this->context->smarty->assign($vars);
		/*
		if (0 == $id_address_delivery)
			$this->errors[] = Tools::displayError('No bpost carriers.');
		else
		*/
		if (!Address::isCountryActiveById((int)$id_address_delivery) && $id_address_delivery != 0)
			$this->errors[] = Tools::displayError('This address is not in a valid area.');
		elseif ((!Validate::isLoadedObject($address_delivery) || $address_delivery->deleted) && $id_address_delivery != 0)
			$this->errors[] = Tools::displayError('This address is invalid.');
		else
		{
			$result = array(
				'HOOK_BEFORECARRIER' => Hook::exec('displayBeforeCarrier', array(
					'carriers' => $carriers,
					// 'delivery_option_list' => $cart->getDeliveryOptionList(),
					'delivery_option_list' => $delivery_option_list,
					'delivery_option' => $cart->getDeliveryOption(null, true)
				)),
				'carrier_block' => $this->context->smarty->fetch(_PS_THEME_DIR_.'order-carrier.tpl')
			);

			Cart::addExtraCarriers($result);
			return $result;
		}
		if (count($this->errors))
			return array(
				'hasError' => true,
				'errors' => $this->errors,
				'carrier_block' => $this->context->smarty->fetch(_PS_THEME_DIR_.'order-carrier.tpl')
			);
	}

	/**
	 * public function hookNewOrder($params)
	 * 
	 * @param array $params
	 */
	public function hookActionValidateOrder($params)
	{
		$ps_order = $params['order'];
		if (!Validate::isLoadedObject($ps_order) || !$this->isBpostShmCarrier((int)$ps_order->id_carrier))
			return;

		$service = Service::getInstance($this->context);
		$service->prepareBpostOrder((int)$ps_order->id);

		if ($service_point = $this->getCartServicePointDetails((int)$ps_order->id_cart))
		{
			// Send mail
			$id_lang = (int)$ps_order->id_lang;
			$template = 'new_order';
			$subject = $this->l('bpost delivery point');
			$shop_name = Service::getBpostring(Configuration::get('PS_SHOP_NAME'));
			$customer = new Customer((int)$ps_order->id_customer);
			$customer_name = $customer->firstname.' '.$customer->lastname;
			$tpl_vars = array(
				'{customer_name}' => $customer_name,
				'{shop_name}' => $shop_name,
				'{sp_name}' => $service_point['lname'],
				'{sp_id}' => $service_point['id'],
				'{sp_office}' => $service_point['office'],
				'{sp_street}' => $service_point['street'],
				'{sp_nr}' => $service_point['nr'],
				'{sp_zip}' => $service_point['zip'],
				'{sp_city}' => $service_point['city'],
			);

			$iso_code = $this->getContextLangIso();
			$mail_dir = _PS_MODULE_DIR_.$this->name.'/mails/';
			try {
				if (file_exists($mail_dir.$iso_code.'/'.$template.'.txt') && file_exists($mail_dir.$iso_code.'/'.$template.'.html'))
					Mail::Send($id_lang, $template, $subject, $tpl_vars,
						$customer->email,
						$customer_name,
						Configuration::get('PS_SHOP_EMAIL'),
						$shop_name,
						null, null, $mail_dir);

			} catch (Exception $e) {
				Service::logError('hookNewOrder: sending mail', $e->getMessage(), $e->getCode(), 'Order', (int)$ps_order->id);

			}
		}
	}

	/**
	 * public function hookPostUpdateOrderStatus($params)
	 * 
	 * @param array $params
	 */
	public function hookActionOrderStatusPostUpdate($params)
	{
		$return = false;
		if ($order_bpost = $this->getOrderBpost((int)$params['id_order']))
		{
			$new_order_state = (int)$params['newOrderStatus']->id;
			$order_bpost->current_state = $new_order_state;
			if (!$order_bpost->treated)
				$order_bpost->treated = (bool)$new_order_state === (int)Configuration::get('BPOST_TREATED_ORDER_STATE');

			$return = $order_bpost->save();
		}

		return $return;
	}

	/* public function hookOrderDetailDisplayed($params) */
	public function hookDisplayOrderDetail($params)
	{
		$ps_order = $params['order'];

		return $this->bpostOrderDetail($ps_order);
	}

	/**
	 * Undocumented Admin function
	 * @author Serge <serge@stigmi.eu>
	 * @param int $id_cart
	 */
	public function displayInfoByCart($id_cart)
	{
		if ($id_order = Order::getOrderByCartId($id_cart))
		{
			$ps_order = new Order((int)$id_order);

			return $this->bpostOrderDetail($ps_order, true);
		}
	}

	protected function bpostOrderDetail($ps_order, $admin = false)
	{
		$tracking = $this->getOrderCarrierTracking($ps_order);
		$service_point = $this->getCartServicePointDetails((int)$ps_order->id_cart);
		if ($tracking || $service_point)
		{
			$this->context->smarty->assign('version', Service::getPsVer(), true);
			$this->context->smarty->assign('module_dir', __PS_BASE_URI__.'modules/'.$this->name);
			if ($tracking)
			{
				// Srg 17-dec-2021: servicepoints refactor
				if ($service_point)
				{
					$url_parts = explode('?', $tracking['url']);
					if (count($url_parts) > 1)
					{
						parse_str($url_parts[1], $params);
						$params['postalCode'] = $service_point['zip'];
						$tracking['url'] = (string)self::TRACKING_URL.http_build_query($params);
					}
				}
				// 
				$this->context->smarty->assign('trk', $tracking);
				if ((bool)$admin || ! (bool)Service::isPrestashop17plus())
					$this->context->smarty->assign('inc_src', $this->_path.'views/js/eon.jquery.checkout.min.js', true);
			}

			if ($service_point)
				$this->context->smarty->assign('sp', $service_point);

			return $this->context->smarty->fetch((bool)$admin ? $this->getTemplate('admin_order_details.tpl', 'admin')
				: $this->getTemplate('order_details.tpl', 'hook'));
		}
	}

	protected function getCartServicePointDetails($id_cart)
	{
		$service_point = false;
		$cart_bpost = $this->getCartBpost((int)$id_cart);
		if (Validate::isLoadedObject($cart_bpost))
			if ($sp_type = (int)$cart_bpost->sp_type)
			{
				$service = Service::getInstance();
				$service_point = $service->getServicePointDetails((int)$cart_bpost->service_point_id, $sp_type, $id_cart);
				// Srg 22-nov-2020 bpost-api bug (dropped servicepoint ??)
				if (! empty($service_point)) {
					$shm = (self::SHM_PLOCKER == $sp_type) ? $sp_type : self::SHM_PPOINT;
					$service_point['lname'] = $this->shipping_methods[$shm]['lname'];
					$iso_country = (string)$service_point['country'];
					$intl = 'BE' !== $iso_country;
					$suffix = $intl ? ' international' : '';
					$service_point['slug'] = $this->shipping_methods[$shm]['slug'].$suffix;
				}
			}

		return $service_point;
	}

	protected function getOrderCarrierTracking($ps_order)
	{
		$tracking = false;
		if (Validate::isLoadedObject($ps_order))
		{
			$ps_order_carrier = new OrderCarrier((int)$ps_order->getIdOrderCarrier());
			if (Validate::isLoadedObject($ps_order_carrier) &&
				! empty((string)$ps_order_carrier->tracking_number))
			{
				// >> Srg: 22-dec-2020 Manadatory barcode as Reference changes and refactor
				$reference = (string)$ps_order_carrier->tracking_number;
				Service::getInstance($this->context);
				$order_bpost = PsOrderBpost::getByReference($reference);
				if (Validate::isLoadedObject($order_bpost))
				{
					$tracking_url = self::TRACKING_URL;
					$iso_code = Service::getSupportedLangIso(Language::getIsoById((int)$ps_order->id_lang), true);
					$params = array(
						'lang' => $iso_code,
						'itemCode' => (string)$order_bpost->getPrimaryBarcode(),
					);
					$ps_address = new Address((int)$ps_order->id_address_delivery);
					if (Validate::isLoadedObject($ps_address))
						$params['postalCode'] = (string)$ps_address->postcode;

					$tracking_url .= http_build_query($params);
					$tracking = array(
						'id' => $reference,
						'url' => (string)$tracking_url,
					);
				}
			}
		}

		return $tracking;
	}

	public function getSpTrackingLink($reference, $iso_code)
	{
		$link = false;
		Service::getInstance($this->context);
		$order_bpost = PsOrderBpost::getByReference($reference);
		$ps_order = PsOrderBpost::getPsOrderByReference($reference);
		if (Validate::isLoadedObject($order_bpost) &&
			Validate::isLoadedObject($ps_order) &&
			($service_point = $this->getCartServicePointDetails((int)$ps_order->id_cart)) &&
			isset($service_point['zip']))
		{
			$iso_code = Service::getSupportedLangIso($iso_code);
			$params = array(
				'lang' => (string)$iso_code,
				'itemCode' => (string)$order_bpost->getPrimaryBarcode(),
				'postalCode' => (string)$service_point['zip'],
			);
			$link = self::TRACKING_URL.http_build_query($params);
		}

		return $link;
	}

	public function hookDisplayAdminListBefore()
	{
		$panel_info = (bool)Configuration::get('BPOST_DISPLAY_ADMIN_INFO');
		$debug_log_enable = (bool)Configuration::get('BPOST_DEBUG_LOG_ENABLE');
		$panel_debug = $debug_log_enable || (bool)Tools::getValue('debug');
		if (self::ADMIN_CTLR === Tools::getValue('controller') &&
			($panel_info || $panel_debug))
		{
			$smarty = $this->context->smarty;
			$smarty->assign('module_dir', __PS_BASE_URI__.'modules/'.$this->name);
			$smarty->assign('version', Service::getPsVer(), true);
			if ($panel_info)
			{
				$smarty->assign('panel_info', $panel_info, true);
				$smarty->assign('url_panel_info_off', $this->getLink('webservicextra',
					array(
						'ajax'				=> true,
						'panel_info_off'	=> true,
						'id_shop' 			=> $this->context->shop->id,
						'admin'				=> true,
						'token'				=> Tools::getAdminToken($this->name),
					)), true);
			}
			if ($panel_debug)
			{
				$smarty->assign('panel_debug', $panel_debug, true);
				$debug_cid_synced = (bool)$this->syncCheckCarrierRefs();
				$smarty->assign('debug_cid_synced', $debug_cid_synced, true);
				if (!$debug_cid_synced)
					$smarty->assign('url_cid_resync', $this->getLink('webservicextra',
						array(
							'ajax'			=> true,
							'cid_resync'	=> true,
							'id_shop' 		=> $this->context->shop->id,
							'admin'			=> true,
							'token'			=> Tools::getAdminToken($this->name),
						)), true);
				$smarty->assign('debug_log_enable', $debug_log_enable, true);
				$smarty->assign('url_debug_log_set', $this->getLink('webservicextra',
					array(
						'ajax'				=> true,
						'debug_log_set'		=> true,
						'id_shop' 			=> $this->context->shop->id,
						'admin'				=> true,
						'token'				=> Tools::getAdminToken($this->name),
					)), true);
				if ($debug_log_enable &&
					EontechBpostDebugLog::hasLog())
				{
					$service = Service::getInstance();
					$debug_log = $service->getBpostDebugLog();
					$smarty->assign('debug_log', array_reverse($debug_log, true), true);
					$smarty->assign('url_get_debug_xml', $this->getLink('webservicextra',
						array(
							'ajax'			=> true,
							'get_debug_xml'	=> true,
							'id_shop' 		=> $this->context->shop->id,
							'admin'			=> true,
							'token'			=> Tools::getAdminToken($this->name),
						)), true);
				}
			}

			return $smarty->fetch($this->getTemplate('adminordersbpost-header.tpl', 'hook'));
		}
	}

	/**
	 * public function hookBackOfficeHeader()
	 * 
	 * backOfficeHeader is shared for all BO
	 */
	public function hookDisplayBackOfficeHeader()
	{
		if (in_array(Tools::getValue('controller'), array('AdminOrders', self::ADMIN_CTLR)) ||
			Tools::getValue('module_name') == $this->name ||
			Tools::getValue('configure') == $this->name)
		{
			$this->context->smarty->assign('module_dir', __PS_BASE_URI__.'modules/'.$this->name);
			// srg: 12-apr-19: admin_ctlr refactor
			if (self::ADMIN_CTLR === Tools::getValue('controller'))
				$this->context->smarty->assign('aob_src', $this->_path.'views/js/eon.jquery.aob.min.js', true);
			//

			return $this->context->smarty->fetch($this->getTemplate('back-office-header.tpl', 'hook'));
		}
	}
/*
	// This didn't work in 1.4 (and buggy in 1.6+ due to placement of header files)
	public function hookBackOfficeHeader()
	{
		if (in_array(Tools::getValue('controller'), array('AdminOrders', self::ADMIN_CTLR)) ||
			Tools::getValue('module_name') == $this->name ||
			Tools::getValue('configure') == $this->name)
		{
			$ctlr = $this->context->controller;
			$ctlr->addCSS($this->_path.'views/css/admin-bpost.css');
			$ctlr->addCSS($this->_path.'views/css/jquery.qtip.min.css');
			$ctlr->addJS($this->_path.'views/js/eon.jquery.base.min.js');
			$ctlr->addJS($this->_path.'views/js/eon.jquery.inputs.min.js');
			$ctlr->addJS($this->_path.'views/js/jquery.qtip.min.js');
			$ctlr->addJS($this->_path.'views/js/jquery.idTabs.min.js');
		}
	}
*/
	/**
	 * @param array $cart
	 * @param $shipping_cost
	 * @param $products
	 */
/*	public function getPackageShippingCost($cart, $shipping_cost, $products)
	{
		if (9 === (int)$cart->id_carrier)
			return 19.63;
		else
		return $this->getOrderShippingCost($cart, $shipping_cost);
	}
*/
	/**
	 * Unused but required
	 * @param array $cart
	 */
	public function getOrderShippingCostExternal($cart)
	{
		return $this->getOrderShippingCost($cart, 0);
	}

	/**
	 * @param array $cart
	 * @param $shipping_cost
	 */
	public function getOrderShippingCost($cart, $shipping_cost)
	{
		$extra = 0;
		if ($cur_shm = (int)$this->getShmFromCarrierID((int)$this->id_carrier))
		{
			if (self::SHM_HOME === $cur_shm)
			{
				// watch out for intl being home as well. sheesh!
				$delivery_address = new Address((int)$cart->id_address_delivery);
				if (Validate::isLoadedObject($delivery_address))
				{
					$country = new Country((int)$delivery_address->id_country);
					if ('BE' != $country->iso_code)
						return $shipping_cost;
				}
			}

			$cart_bpost = $this->getCartBpost((int)$cart->id);
			if ($delivery = $cart_bpost->getDeliveryCode($cur_shm))
				if ($cents = $delivery['cents'])
					$extra = (float)$cents / 100;
		}

		return $shipping_cost + $extra;
	}
	/* Required for getOrderShippingCost above
	 * Do not Set, Alter or Use for anything else */
	public $id_carrier;
	/* Anywhere Ever */
}