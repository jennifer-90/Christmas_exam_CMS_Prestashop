<?php
/**
 * 2014 Stigmi
 *
 * @author    Stigmi <www.stigmi.eu>
 * @copyright 2014 Stigmi
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

if (!defined('_PS_VERSION_'))
	exit();

require_once(_PS_MODULE_DIR_.'bpostshm/bpostshm.php');
require_once(_PS_MODULE_DIR_.'bpostshm/classes/Service.php');

class AdminOrdersBpostController extends ModuleAdminController
{
	public $statuses = array(
		'OPEN',
		'PENDING',
		'CANCELLED',
		/* 'COMPLETED', */
		'ON-HOLD',
		'PRINTED',
		'ANNOUNCED',
		'IN_TRANSIT',
		'AWAITING_PICKUP',
		'DELIVERED',
		'BACK_TO_SENDER',
	);
	protected $identifier = 'reference';

	// private $tracking_url = 'http://track.bpost.be/etr/light/performSearch.do';
	// private $tracking_params = array(
	// 	// 'searchByCustomerReference' => true,
	// 	'oss_language' => '',
	// 	'customerReference' => '',
	// );
	private $tracking_params = array(
		'lang' => '',
		'itemCode' => '',
		'postalCode' => '',
	);

	protected static $_SUPPORTED_LANGS = array('de', 'fr', 'nl', 'en');
	protected $l_cache = array();
	protected $iso_code;

	public function __construct()
	{
		$this->table = 'order_bpost';
		$this->className = 'PsOrderBpost';
		$this->lang = false;
		$this->explicitSelect = true;
		$this->deleted = false;
		$this->list_no_link = true;
		$this->context = Context::getContext();

		// Srg: 7-jun-2020) unified iso function.  map lang iso to bpost supported.
		// $iso_code = $this->context->language->iso_code;
		// $this->iso_code = in_array($iso_code, self::$_SUPPORTED_LANGS) ? $iso_code : 'en';
		// $this->tracking_params['oss_language'] = $this->iso_code;
		$this->iso_code = Service::getSupportedLangIso($this->context->language->iso_code, true);
		$this->tracking_params['lang'] = $this->iso_code;
		// 
		// $this->affectAdminTranslation($iso_code);
		$this->affectAllAdminTranslation();
		// the most unlikely performance boost!
		// $this->l_cache = array();

		$this->bootstrap = true;
		$this->show_filters = true;
		$this->module = new BpostShm();
		// service needs to be shop context dependant.
		$service = Service::getInstance($this->context);
		$this->service = SHOP::isFeatureActive() ? false : $service;

		// cached current_row while building list
		// always false after display for any action
		$this->current_row = false;
		
		$this->actions = array(
			'addLabel',
			'createRetour',
			'printLabels',
			'refreshStatus',
			'markTreated',
			'sendTTEmail',
			'view',
			'cancel',
		);

		$this->bulk_actions = array(
			'markTreated' => array('text' => $this->l('Mark treated'), 'confirm' => $this->l('Mark order as treated?')),
			'printLabels' => array('text' => $this->l('Print labels')),
			'sendTTEmail' => array('text' => $this->l('Send T&T e-mail'), 'confirm' => $this->l('Send Track & Trace e-mail to recipient?')),
		);

		// Srg: 20-dec-2020 barcode_ref
		$barcode_ref = '(SELECT `barcode` FROM '._DB_PREFIX_.'order_bpost_label obl WHERE a.`id_order_bpost` = obl.`id_order_bpost` AND obl.`barcode` IS NOT NULL AND obl.`is_retour` = 0 LIMIT 1) as barcode_ref,';
		$this->_select = '
		a.`reference` as print,
		a.`reference` as t_t,
		a.`shm`,
		COALESCE(a.`status`, "PENDING") as status_bpost,
		CASE WHEN 0 = a.`dt_drop` THEN NULL ELSE STR_TO_DATE(a.`dt_drop`, "%Y%m%d %T") END as drop_date,
		COUNT(obl.`id_order_bpost_label`) as count,
		SUM(obl.`barcode` IS NOT NULL) AS count_printed,
		SUM(obl.`is_retour` = 0) AS count_normal,
		SUM(obl.`is_retour` = 1) AS count_retours,
		SUM(obl.`has_retour` = 1) AS count_auto_retours,
		'.$barcode_ref.'
		oc.`tracking_number`,
		l.`iso_code`,
		adrs.`postcode`
		';

		$this->_join = '
		LEFT JOIN `'._DB_PREFIX_.'order_bpost_label` obl ON (a.`id_order_bpost` = obl.`id_order_bpost`)
		LEFT JOIN `'._DB_PREFIX_.'orders` o ON (o.`id_order` = SUBSTRING(a.`reference`, 8))
		LEFT JOIN `'._DB_PREFIX_.'order_carrier` oc ON (oc.`id_order` = o.`id_order`)
		LEFT JOIN `'._DB_PREFIX_.'carrier` c ON (c.`id_carrier` = oc.`id_carrier`)
		LEFT JOIN `'._DB_PREFIX_.'lang` l ON (l.`id_lang` = o.`id_lang`)
		LEFT JOIN `'._DB_PREFIX_.'address` adrs ON (adrs.`id_address` = o.`id_address_delivery`)
		';

		// Srg: 29-mar-19 order display days
		$order_display_days = (int)Configuration::get('BPOST_ORDER_DISPLAY_DAYS');
		$display_order_states = (string)Configuration::get('BPOST_DISPLAY_ORDER_STATES');
		// UPGRADE Routine
		if (empty($order_display_days) || empty($display_order_states))
		{
			$order_display_days = (int)BpostShm::DEF_ORDER_BPOST_DAYS;
			$display_order_states = '2';
			// Srg 20-Sep-19: incorrect legacy member changed throughout file (_errors -> errors)
			// $this->_errors[] = Tools::displayError('Please update Label settings, to proceed');
			$this->errors[] = Tools::displayError('bpostDev: Please update Label settings, to proceed');
			//
		}
		/*
		$this->_where = '
		AND DATEDIFF(NOW(), a.date_add) <= '.$order_display_days.'
		AND (a.current_state '.$this->module->getOrderStateListSQL().' OR a.treated = 1)
		';
		 */
		// 

		$this->_where = '
		AND DATEDIFF(NOW(), a.date_add) <= '.$order_display_days.'
		AND (a.current_state IN ('.$display_order_states.') OR a.treated = 1)
		';

		$id_bpost_carriers = array_values($this->module->getIdCarriers());
		if ($references = Db::getInstance()->executeS('
			SELECT id_reference FROM `'._DB_PREFIX_.'carrier` WHERE id_carrier IN ('.implode(', ', array_map('intval', $id_bpost_carriers)).')'))
		{
			foreach ($references as $reference)
				$id_bpost_carriers[] = (int)$reference['id_reference'];
		}
		$this->_where .= '
		AND (
		oc.id_carrier IN ("'.implode('", "', $id_bpost_carriers).'")
		OR c.id_reference IN ("'.implode('", "', $id_bpost_carriers).'")
		)';

		$this->_group = 'GROUP BY(a.`reference`)';
		if (!Tools::getValue($this->table.'Orderby'))
			$this->_orderBy = 'o.id_order';

		if (!Tools::getValue($this->table.'Orderway'))
			$this->_orderWay = 'DESC';

		$this->external_sort_filter = Tools::getValue($this->table.'Orderby') || Tools::getValue($this->table.'Orderway');
		$this->inc_drop_date = (bool)Configuration::get('BPOST_DISPLAY_DELIVERY_DATE');

		$this->fields_list = array(
		'print' => array(
			'title' => '',
			'align' => 'center',
			'callback' => 'getPrintIcon',
			'search' => false,
			'orderby' => false,
		),
		't_t' => array(
			'title' => '',
			'align' => 'center',
			// Srg 19-dec-21
			// 'callback' => 'getTTIcon',
			'callback' => 'getTTIconX',
			// 
			'search' => false,
			'orderby' => false,
		),
		'reference' => array(
			'title' => $this->l('Reference'),
			'align' => 'left',
			'filter_key' => 'a!reference',
		),
		'delivery_method' => array(
			'title' => $this->l('Delivery method'),
			'search' => false,
			'callback' => 'getDeliveryMethod',
		),
		'recipient' => array(
			'title' => $this->l('Recipient'),
			'filter_key' => 'a!recipient',
		),
		'status_bpost' => array(
			'title' => $this->l('Status'),
			'callback' => 'getCurrentStatus',
		),
		'date_add' => array(
			'title' => $this->l('Creation date'),
			'align' => 'right',
			'type' => 'datetime',
			'filter_key' => 'a!date_add'
		),
		'drop_date' => array(
			'title' => $this->l('Drop date'),
			'align' => 'right',
			// 1.5 doesnot use callback if type is date. must go manual
			'type' => 'date',
			'havingFilter' => true,
		),
		'count' => array(
			'title' => $this->l('Labels'),
			'align' => 'center',
			'callback' => 'getLabelsCount',
			'search' => false,
			'orderby' => false,
		),
		'treated' => array(
			'title' => 'T',
			// 'title' => $this->l('Treated'),
			'search' => false,
			'orderby' => false,
			'class' => 'treated_col'
		),
		);
		if (!$this->inc_drop_date)
			unset($this->fields_list['drop_date']);

		$this->shopLinkType = 'shop';
		$this->shopShareDatas = Shop::SHARE_ORDER;

		parent::__construct();
	}

	public function initToolbar()
	{
		parent::initToolbar();

		if (isset($this->toolbar_btn['new']))
			$this->toolbar_btn['new'] = false;
	}

	public function addFiltersToBreadcrumbs()
	{
		$brain_works = true;
		// Silence is golden
		return $brain_works ? '' : parent::addFiltersToBreadcrumbs();
	}

	public function initContent()
	{
		if (!$this->viewAccess())
		{
			$this->errors[] = Tools::displayError('You do not have permission to view this.');
			return;
		}

		$this->getLanguages();
		$this->initToolbar();
		if (method_exists($this, 'initTabModuleList'))  // method not in earlier PS 1.5 < .6.2
			$this->initTabModuleList();

		if ($this->display == 'view')
		{
			// Some controllers use the view action without an object
			if ($this->className)
				$this->loadObject(true);
			$this->content .= $this->renderView();
		}
		else
			parent::initContent();

		$this->addJqueryPlugin(array('idTabs'));
		$this->context->smarty->assign('content', $this->content);
	}

	public function initProcess()
	{
		parent::initProcess();

		$reference = (string)Tools::getValue('reference');
		if (empty($this->errors) && !empty($reference))
		{
			$response = array();
			$errors = array();
			$service = $this->getContextualService($reference);

			try {
				if (Tools::getIsset('addLabel'.$this->table))
				{
					// >> Srg 16-07-17: PUGO proceed ??
					if (Tools::getIsset('prevent'))
						$errors[$reference][] = $this->l('Cannot create Intl PUGO Return / auto-return label');
					elseif (!$response = Service::addLabel($reference))
					// if (!$response = Service::addLabel($reference))
					// << Srg
						$errors[$reference][] = 'Unable to add Label to order ['.$reference.'] Please check logs for errors.';

				}
				elseif (Tools::getIsset('createRetour'.$this->table))
				{
					// SRG: 14-8-16 -
					$label_count = (Tools::getIsset('count')) ? (int)Tools::getValue('count') : 1;
					if (!$response = Service::addLabels($reference, $label_count, true))
					// if (!$response = Service::addLabel($reference, true))
					//
						$errors[$reference][] = 'Unable to add Retour Label to order ['.$reference.'] Please check logs for errors.';

				}
				elseif (Tools::getIsset('printLabels'.$this->table))
				{
					$links = Service::bulkPrintLabels(array($reference));
					if (isset($links['error']))
					{
						$errors = $links['error'];
						unset($links['error']);
					}

					if (isset($links['tt']))
					{
						$result = $this->sendBulkTTEmails($links['tt']);
						unset($links['tt']);
						if (isset($result['error']))
							$errors = array_merge_recursive($errors, $result['error']);
					}

					if (!empty($links))
						$response['links'] = $links;

					/*if (Configuration::get('BPOST_LABEL_TT_INTEGRATION') && !empty($links))
						$this->sendTTEmail($reference);*/

				}
				elseif (Tools::getIsset('refreshStatus'.$this->table))
				{
					if (!$response = Service::refreshBpostStatus($service->bpost, $reference))
						$errors[$reference][] = 'Unable to refresh status for order ['.$reference.'] Please check logs for errors.';

				}
				elseif (Tools::getIsset('markTreated'.$this->table))
				// {
					$service->module->changeOrderState($reference, 'Treated');
				// }
				elseif (Tools::getIsset('sendTTEmail'.$this->table))
				{
					if (!$response = $this->sendTTEmail($reference))
						$errors[$reference][] = $this->errors;

				}
				elseif (Tools::getIsset('cancel'.$this->table))
				// {
					$service->module->changeOrderState($reference, 'Cancelled');
				// }
				elseif (Tools::getIsset('sptLink'.$this->table))
				{
					$iso_code = (Tools::getIsset('iso')) ? (string)Tools::getValue('iso') : 'en';
					if ($link = $service->module->getSpTrackingLink($reference, $iso_code))
						$response['links'][] = $link;
				}

			} catch (Exception $e) {
				$errors[$reference][] = $e->getMessage();

			}

			if (!empty($errors))
				$response['errors'] = $errors;

			$this->jsonEncode($response);
		}
	}

	/**
	 * override PS controllers broken translation
	 * @author Serge <serge@stigmi.eu>
	 * @param  string $string       string to translate
	 * @param  string $iso_code 	required lang
	 * @return string               translated string if found or $string
	 */
	protected function l($string, $iso_code = false, $class = BpostShm::ADMIN_CTLR, $addslashes = false, $htmlentities = true)
	{
		global $_BPOST_LANGSADM;

		if (!$iso_code || !(bool)preg_match('/^([a-z]{2})$/', $iso_code))
			$iso_code = $this->iso_code;
		// $class = BpostShm::ADMIN_CTLR;
		$key = $class.md5(str_replace('\'', '\\\'', $string));
		if (!isset($this->l_cache[$iso_code][$key]))
			$this->l_cache[$iso_code][$key] = isset($_BPOST_LANGSADM[$iso_code][$key]) ?
				$_BPOST_LANGSADM[$iso_code][$key] : $string;

		return $this->l_cache[$iso_code][$key];
	}

	/*protected function l($string, $class = BpostShm::ADMIN_CTLR, $addslashes = false, $htmlentities = true)
	{
		global $_LANGADM;

		$htmlentities = false; // always
		$key = $class.md5(str_replace('\'', '\\\'', $string));
		if (!isset($this->l_cache[$key]))
			$this->l_cache[$key] = isset($_LANGADM[$key]) ?
				$_LANGADM[$key]:
				Translate::getAdminTranslation($string, $class, $addslashes, $htmlentities);

		return $this->l_cache[$key];
	}*/

	/**
	 * insert this controllers translation strings into
	 * globally retrieved AdminTab translations
	 * @author Serge <serge@stigmi.eu>
	 * @param  string $iso_code
	 * @return None
	 */
	private function affectAllAdminTranslation()
	{
		global $_BPOST_LANGSADM;

		$class_name = BpostShm::ADMIN_CTLR;
		$module = isset($this->module) ? $this->module : 'bpostshm';
		$needle = Tools::strtolower($class_name).'_';
		$lang_file = _PS_MODULE_DIR_.$module.'/translations/%s.php';
		foreach (self::$_SUPPORTED_LANGS as $iso_code)
		{
			$file = sprintf($lang_file, $iso_code);
			if (file_exists($file))
			{
				$_MODULE = array();
				include $file;
				foreach ($_MODULE as $key => $value)
					if (strpos($key, $needle))
						$_BPOST_LANGSADM[$iso_code][str_replace($needle, $class_name, strip_tags($key))] = $value;

			}
		}
	}

	/*private function affectAdminTranslation($iso_code = 'en')
	{
		global $_LANGADM;

		if (!(bool)preg_match('/^([a-z]{2})$/', $iso_code))
			return;

		// $class_name = get_class($this);
		$class_name = BpostShm::ADMIN_CTLR;
		$module = isset($this->module) ? $this->module : 'bpostshm';
		$needle = Tools::strtolower($class_name).'_';
		$lang_file = _PS_MODULE_DIR_.$module.'/translations/'.$iso_code.'.php';
		if (file_exists($lang_file))
		{
			$_MODULE = array();
			require $lang_file;
			foreach ($_MODULE as $key => $value)
				if (strpos($key, $needle))
					$_LANGADM[str_replace($needle, $class_name, strip_tags($key))] = $value;

		}
	}*/

	/**
	 * retrieve service with correct shop context
	 * @author Serge <serge@stigmi.eu>
	 * @param  string $reference
	 * @return None
	 */
	private function getContextualService($reference)
	{
		// service needs the correct row shop context when multistore
		$service = $this->service;
		if (false === $service)
		{
			$this->setRowContext($reference);
			$service = new Service($this->context);
		}

		return $service;
	}

	/**
	 * @param mixed $content
	 */
	private function jsonEncode($content)
	{
		header('Content-Type: application/json');
		die(Tools::jsonEncode($content));
	}

	/**
	 * tracking overhaul: implement correct PS tracking to aid
	 * customer experience, suppress spam duplicates, ugly FO messages
	 * @author Serge <serge@stigmi.eu>
	 * @param string $reference
	 * @return bool
	 */
	private function sendTTEmail($reference = '')
	{
		if (empty($reference))
			return false;

		$order_bpost = PsOrderBpost::getByReference($reference);
		// >> Srg: 21-dec-2020 mandatory barcode as ref
		$barcode_ref = (string)$order_bpost->getPrimaryBarcode();
		if (empty($barcode_ref))
		{
			$this->errors[] = (string)$reference.': '.$this->l('Unable to send tracking email until after the order is printed.');

			return false;
		}

		$ps_order = new Order((int)Service::getOrderIDFromReference($reference));
		if (Validate::isLoadedObject($ps_order))
		{
			$ps_order_carrier = new OrderCarrier((int)$ps_order->getIdOrderCarrier());
			if (!Validate::isLoadedObject($ps_order_carrier))
			{
				$this->errors[] = Tools::displayError('bpostDev: fetched OrderCarrier is invalid.');
				return false;
			}
		}

		if (empty((string)$ps_order_carrier->tracking_number))
		{
			$iso_code = Service::getSupportedLangIso(Language::getIsoById((int)$ps_order->id_lang), true);
			if (Service::isServicePoint((int)$order_bpost->shm))
				$tracking_url = $this->module->getSpTrackingLink($reference, $iso_code);

			else
			{
				$tracking_url = BpostShm::TRACKING_URL;
				$params = $this->tracking_params;
				$params['lang'] = $iso_code;
				// >> Srg: 20-dec-2020 (barcode now mandatory! as reference)
				$params['itemCode'] = $barcode_ref;
				$ps_address = new Address((int)$ps_order->id_address_delivery);
				if (Validate::isLoadedObject($ps_address))
					$params['postalCode'] = (string)$ps_address->postcode;

				$tracking_url .= http_build_query($params);
			}

			$message = sprintf($this->l('Your order %s can now be tracked here :', $iso_code), $ps_order->reference);
			$message .= sprintf(' <a href="%s">%s</a>', $tracking_url, $tracking_url);

			$customer = new Customer($ps_order->id_customer);
			if (!Validate::isLoadedObject($customer))
				$this->errors[] = Tools::displayError('bpostDev: The customer is invalid.');
			else
			{
				try {	
					if (Mail::TYPE_TEXT != Configuration::get('PS_MAIL_TYPE', null, null, $ps_order->id_shop))
						$message = Tools::nl2br($message);

					$tpl = 'order_merchant_comment';
					$subject = $this->l('New message regarding your order', $iso_code);
					$vars_tpl = array(
						'{lastname}' => $customer->lastname,
						'{firstname}' => $customer->firstname,
						'{id_order}' => $ps_order->id,
						'{order_name}' => $ps_order->getUniqReference(),
						'{message}' => $message,
					);

					Mail::Send((int)$ps_order->id_lang, $tpl, $subject, $vars_tpl,
						$customer->email,
						$customer->firstname.' '.$customer->lastname,
						null, null, null, null, _PS_MAIL_DIR_, true, (int)$ps_order->id_shop
					);

					$ps_order_carrier->tracking_number = (string)$reference;
					$ps_order_carrier->save();

				} catch (Exception $e) {
					$this->errors[] = $e->getMessage();

				}
			}
		}

		return (bool)empty($this->errors);
	}

	/**
	 * Function used to render the list to display for this controller
	 */
	public function renderList()
	{
		if (!($this->fields_list && is_array($this->fields_list)))
			return false;
		$this->getList($this->context->language->id);

		$helper = new HelperList();
		$helper->module = new BpostShm();

		// Empty list is ok
		if (!is_array($this->_list))
		{
			$this->displayWarning($this->l('Bad SQL query', 'Helper').'<br />'.htmlspecialchars($this->_list_error));
			return false;
		}
		elseif (empty($this->_list))
			$this->bulk_actions = array();

		$list_vars = array(
			'str_tabs' =>
				array(
					'open' => $this->l('Open'),
					'treated' => $this->l('Treated'),
					),
			'reload_href' =>
				self::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminOrdersBpost'),
			);

		// Srg: 18-Apr-19 *obsolete*
		// if ((bool)Configuration::get('BPOST_DISPLAY_ADMIN_INFO'))
		// 	$list_vars['remove_info_link'] = self::$currentIndex.'&removeInfo'.$this->table.'&token='.Tools::getAdminTokenLite('AdminOrdersBpost');

		$this->tpl_list_vars = array_merge($this->tpl_list_vars, $list_vars);

		$this->setHelperDisplay($helper);
		$helper->tpl_vars = $this->tpl_list_vars;
		$helper->tpl_delete_link_vars = $this->tpl_delete_link_vars;

		// For compatibility reasons, we have to check standard actions in class attributes
		foreach ($this->actions_available as $action)
			if (!in_array($action, $this->actions) && isset($this->$action) && $this->$action)
				$this->actions[] = $action;
		$helper->is_cms = $this->is_cms;
		$list = $helper->generateList($this->_list, $this->fields_list);

		return $list;
	}

	public function getList($id_lang, $order_by = null, $order_way = null, $start = 0, $limit = null, $id_lang_shop = false)
	{
		// if (!empty($this->_filter))
		//  	$this->_filter = (string)str_replace('`status_bpost`', 'a.`status`', $this->_filter);
		if (!empty($this->_filter))
		{
			$srch_stat = preg_match('`status_bpost`', $this->_filter);
			if (!empty($srch_stat))
			{
				$str_filter = (string)str_replace('`status_bpost`', 'a.`status`', $this->_filter);
				// if (preg_match('/\%(pend[i]?[n]?[g]?)\%/i', $str_filter, $matches))
				if (preg_match('/\%(\bpe(n(d(i(n(g)?)?)?)?)?\b)\%/is', $str_filter, $matches))
				{
					$srch_str = sprintf("a.`status` LIKE '%s'", (string)$matches[0]);
					$str_filter = (string)str_replace($srch_str, sprintf('(%s OR a.`status` IS NULL)', $srch_str), $str_filter);
				}
				$this->_filter = (string)$str_filter;
			}
		}
		//

		parent::getList($id_lang, $order_by, $order_way, $start, $limit, $id_lang_shop);

		if (!Tools::getValue($this->list_id.'_pagination'))
			$this->context->cookie->{$this->list_id.'_pagination'} = 50;

		// Serge changes: 27 Aug 2015
		// Default Order By handling sucks!
		if ($this->inc_drop_date && !$this->external_sort_filter)
		{
			$dt_today = (int)date('Ymd');
			// $this->_listsql = preg_replace('/^\s*(ORDER BY)(.+)$/m', '\1 CASE WHEN 0 = a.`dt_drop` THEN 1 ELSE 0 END, a.`dt_drop` ASC,\2', $this->_listsql);
			$this->_listsql = preg_replace('/^\s*(ORDER BY)(.+)$/m',
				'\1 CASE WHEN (a.`dt_drop` > 0 AND a.`dt_drop` <= '.$dt_today.') THEN 0 ELSE 1 END, a.`dt_drop` ASC,\2', $this->_listsql);

			$this->_listTotal = 0;
			if (!($this->_list = Db::getInstance()->executeS($this->_listsql)))
				$this->_list_error = Db::getInstance()->getMsgError();
			else
				$this->_listTotal = Db::getInstance()->getValue('SELECT FOUND_ROWS() AS `'._DB_PREFIX_.$this->table.'`');
		}
	}

	public function processbulkmarktreated()
	{
		if (empty($this->boxes) || !is_array($this->boxes))
			return false;

		$errors = array();
		$shop_untreated_orders = PsOrderBpost::fetchOrdersbyRefs($this->boxes, true);
		if (! empty($shop_untreated_orders))
		{
			$multistore = (bool)Service::isMultistore();
			if ($multistore) $cur_context = Shop::getContext();
			foreach ($shop_untreated_orders as $id_shop => $ref_orders)
			{
				Shop::setContext(Shop::CONTEXT_SHOP, (int)$id_shop);

				$svc = new Service(Context::getContext());
				foreach ($ref_orders as $order_ref)
				{
					// if ('CANCELLED' === (string)$order_ref['status'])
					// 	continue;
					$reference = (string)$order_ref['reference'];
					try {
						$svc->module->changeOrderState($reference, 'Treated');

					} catch (Exception $e) {
						$errors[$reference][] = $e->getMessage();

					}
				}
			}
			if ($multistore) Shop::setContext($cur_context);
		}

		if (!empty($errors))
			$this->context->smarty->assign('errors', $errors);

		return empty($errors);
	}

	public function processbulkprintlabels()
	{
		if (empty($this->boxes) || !is_array($this->boxes))
			return false;

		$multistore = (bool)Service::isMultistore();
		if ($multistore) $cur_context = Shop::getContext();
		$labels = Service::bulkPrintLabels($this->boxes);
		if ($multistore) Shop::setContext($cur_context);

		$errors = array();
		if (isset($labels['tt']))
		{
			$response = $this->sendBulkTTEmails($labels['tt']);
			unset($labels['tt']);
			if (isset($response['error']))
				$errors = $response;
		}

		if (isset($labels['error']))
		{
			$errors = array_merge_recursive($labels['error'], $errors);
			unset($labels['error']);
		}

		if (! empty($errors))
			$this->context->smarty->assign('errors', $errors);

		if (! empty($labels))
			$this->context->smarty->assign('labels', $labels);

		return true;
	}

	public function processbulksendttemail()
	{
		$response = $this->sendBulkTTEmails($this->boxes);
		if (isset($response['error']))
		{
			$this->context->smarty->assign('errors', $response['error']);
			$response = false;
		}

		return $response;
	}

	protected function sendBulkTTEmails($refs)
	{
		if (empty($refs) || ! is_array($refs))
			return false;

		$response = true;
		foreach ($refs as $ref)
			$response &= $response && $this->sendTTEmail($ref);

		if (! $response)
			$response = array('error' => $this->errors);

		return $response;
	}

	/**
	 * @param string $delivery_method as stored
	 * @return string
	 */
	public function getDeliveryMethod($delivery_method = '')
	{
		if (empty($delivery_method))
			return;

		// format: slug[:option list]*
		// @bpost or @home:300|330
		$dm_options = explode(':', $delivery_method);
		$tpl_vars = array(
			'dm' => $dm_options[0],
			);
		if (isset($dm_options[1]))
		{
			$service = Service::getInstance($this->context);
			$dm_options = $service->getDeliveryOptions($dm_options[1]);
			$tpl_vars['options'] = $dm_options;
		}

		$tpl = $this->createTemplate('order_bpost_delivery_method.tpl');
		$tpl->assign($tpl_vars);
		return $tpl->fetch();
	}

	/**
	 * @param string $status as stored
	 * @return string
	 */
	public function getCurrentStatus($status = '')
	{
		$fields_list = $this->current_row;
		if (empty($fields_list))
			return;

		$cls_late = $print_count = '';
		if (($count_printed = (int)$fields_list['count_printed']) &&
			'PRINTED' == $status)
			$print_count = $count_printed.' / '.(int)$fields_list['count'];
		//
		if (Validate::isDate($drop_date = $fields_list['drop_date']))
		{
			$drop_time = strtotime($drop_date);
			// $display_date = $drop_date;
			$dt_drop = date('Ymd', $drop_time);
			$dt_today = date('Ymd');
			$cls_late = $dt_drop < $dt_today ? 'urgent' : ($dt_drop == $dt_today ? 'late' : '');
		}

		$tpl_vars = array(
			'status' => $status,
			'cls_late' => $cls_late,
			'print_count' => $print_count,
		);

		$tpl = $this->createTemplate('order_bpost_status.tpl');
		$tpl->assign($tpl_vars);
		return $tpl->fetch();
	}

	/**
	 * @param string $count
	 * @return string
	 */
/*	public function getLabelsCount($count = '')
	{
		$fields_list = $this->current_row;
		if (empty($count) || empty($fields_list))
			return;

		$count_retours = (int)$fields_list['count_retours'];
		// $count_normal = $count - $count_retours;
		$count_normal = (int)$fields_list['count_normal'];

		$reduced_size = $count_normal ? 'font-size:10px;' : '';
		$plus = $count_normal ? ' +' : '';
		$disp_retours = '<span style="'.$reduced_size.'color:silver;">'.$plus.$count_retours.'R</span>';

		$current_count = $count_normal ?
			$count_normal.($count_retours ? $disp_retours : '') :
			$disp_retours;

		return $current_count;
	}
*/
	public function getLabelsCount($count = '')
	{
		$fields_list = $this->current_row;
		if (empty($count) || empty($fields_list))
			return;

		$count_retours = (int)$fields_list['count_retours'];
		$count_normal = (int)$fields_list['count_normal'];
	
		$disp_normal = Service::isNonEu($fields_list['shm']) ? '<span style="color:orange;">'.$count_normal.'</span>' : $count_normal;
		$disp_retours = '<span style="font-size:10px;color:silver;"> +'.$count_retours.'R</span>';

		return $disp_normal.($count_retours ? $disp_retours : '');
	}

	/**
	 * @param string $reference
	 * @return string
	 */
	public function getPrintIcon($reference = '')
	{
		if (empty($reference))
			return;

		return '<img class="print" src="'._MODULE_DIR_.'bpostshm/views/img/icons/print.png"
			 data-labels="'.Tools::safeOutput(self::$currentIndex.'&reference='.$reference.'&printLabels'.$this->table.'&token='.$this->token).'"/>';
	}

	/**
	 * @param string $reference
	 * @return string
	 * Srg 19-12-21: smart link
	 */
	public function getTTIconX($reference = '')
	{
		$fields_list = $this->current_row;
		if (empty($reference) || empty($fields_list) || empty($fields_list['count_printed']))
			return;

		// Srg 20-dec-21: link is available to merchant only
		// $iso_code = Service::getSupportedLangIso($fields_list['iso_code'], true);
		$iso_code = Service::getSupportedLangIso($this->iso_code, true);
		//
		$shm = (int)$fields_list['shm'];
		if (Service::isServicePoint($shm))
			return '<img class="sp_tt" src="'._MODULE_DIR_.'bpostshm/views/img/icons/track_and_trace.png" title="'.$this->l('View Track & Trace status').'"
			 data-sptlink="'.Tools::safeOutput(self::$currentIndex.'&reference='.$reference.'&iso='.$iso_code.'&sptLink'.$this->table.'&token='.$this->token).'"/>';
		//
		$tracking_url = BpostShm::TRACKING_URL;
		$params = $this->tracking_params;
		$params['lang'] = $iso_code;
		$params['itemCode'] = $fields_list['barcode_ref'];
		$params['postalCode'] = $fields_list['postcode'];

		$tracking_url .= http_build_query($params);

		return '<a href="'.$tracking_url.'" target="_blank" title="'.$this->l('View Track & Trace status').'">
			<img class="t_t" src="'._MODULE_DIR_.'bpostshm/views/img/icons/track_and_trace.png" /></a>';
	}

	/**
	 * @param string $reference
	 * @return string
	 */
	public function getTTIcon($reference = '')
	{
		$fields_list = $this->current_row;
		if (empty($reference) || empty($fields_list) || empty($fields_list['count_printed'])) //(!$fields_list['count_printed']))
			return;

		// $tracking_url = $this->tracking_url;
		$tracking_url = BpostShm::TRACKING_URL;
		$params = $this->tracking_params;
		// >> Srg: 26-apr-2017: order language
		// modded 7-jun-2020
		// $params['oss_language'] = $fields_list['iso_code'];
		// $params['customerReference'] = $reference;
		$params['lang'] = Service::getSupportedLangIso($fields_list['iso_code'], true);
		// >> Srg: 20-dec-2020
		$params['itemCode'] = $fields_list['barcode_ref'];
		// $params['itemCode'] = $reference;
		// >> Srg: 4-nov-2020
		$params['postalCode'] = $fields_list['postcode'];
		// << Srg

		// $tracking_url .= '?'.http_build_query($params);
		$tracking_url .= http_build_query($params);

		return '<a href="'.$tracking_url.'" target="_blank" title="'.$this->l('View Track & Trace status').'">
			<img class="t_t" src="'._MODULE_DIR_.'bpostshm/views/img/icons/track_and_trace.png" /></a>';
	}

	/**
	 * [setCurrentRow]
	 * @param  string $reference 
	 * currentRow cached in member var current_row
	 * usefull while building the list since not all
	 * callbacks are called wth $reference.
	 */
	protected function setCurrentRow($reference = '')
	{
		// needs to be placed in the 1st method called
		// currently that's displayAddLabelLink in 1.5+
		// as the 1st action item added
		$current_row = array();
		foreach ($this->_list as $row)
			if ($reference == $row['reference'])
			{
				$current_row = $row;
				// Srg: 10-may-2021 max_labels
				// $ps_order = new Order((int)Service::getOrderIDFromReference($reference));
				// $max_labels = EontechProductBoxService::getOrderMaxBoxes($ps_order);
				$ops = EontechOrderParcelService::fromOrderID((int)Service::getOrderIDFromReference($reference));
				$max_labels = $ops->getMaxLabels();
				$current_row['max_labels'] = (int)$max_labels;
				// << Srg max_labels
				break;
			}

		if (!empty($current_row))
			$this->current_row = $current_row;
		// now we have it
	}

	protected function setRowContext($reference)
	{
		if (! Service::isMultistore())
			return;

		$order_bpost = PsOrderBpost::getByReference($reference);
		Shop::setContext(Shop::CONTEXT_SHOP, (int)$order_bpost->id_shop);
	}

	/**
	 * @param null|string $token
	 * @param string $reference
	 * @return mixed
	 */
	public function displayAddLabelLink($token = null, $reference = '')
	{
		if (empty($reference))
			return;

		// This is the 1st method called so store currentRow & set rowContext
		$this->setCurrentRow($reference);
		if (false === $this->service)
			$this->setRowContext($reference);

		$fields_list = $this->current_row;
		// SRG >>: 3-may-21
		// if (Service::isNonEu($fields_list['shm']))
		$num_labels = (int)$fields_list['count_normal'];
		$max_labels = (int)$fields_list['max_labels'];
		// No more labels if printed (or reached max)
		if (! empty($fields_list['count_printed']) || $num_labels >= $max_labels)
			return;
		//
		$tpl_vars = array(
			'action' => $this->l('Add label'),
			'href' => Tools::safeOutput(self::$currentIndex.'&reference='.$reference.'&addLabel'.$this->table
				.'&token='.($token != null ? $token : $this->token)),
		);

		// SRG >>: 30-09-17
		/*// SRG >>: 15-7-17 - prevent PUGO labels if Auto-retour !!
		if (BpostShm::SHM_PPI === (int)$fields_list['shm'] &&
			(bool)(Configuration::get('BPOST_AUTO_RETOUR_LABEL') || $fields_list['count_auto_retours']))
			// $tpl_vars['disabled'] = $this->l('Auto-Retour is not available for PUGO');
			$tpl_vars['href'] .= '&prevent=1';*/
		// SRG <<

		if ('CANCELLED' == $fields_list['status_bpost'])
			$tpl_vars['disabled'] = $this->l('Order is Cancelled at bpost SHM');

		$tpl = $this->createTemplate('helpers/list/list_action_option.tpl');
		$tpl->assign($tpl_vars);
		return $tpl->fetch();
	}

	/**
	 * @param null|string $token
	 * @param string $reference
	 * @return mixed
	 */
	public function displayCreateRetourLink($token = null, $reference = '')
	{
		// Do not display if retours are automatically generated
		if (empty($reference) || (bool)Configuration::get('BPOST_AUTO_RETOUR_LABEL'))
			return;

		$fields_list = $this->current_row;
		// SRG >>: 3-may-21
		$num_labels = (int)$fields_list['count_normal'];
		$num_retours = (int)$fields_list['count_retours'];
		$max_labels = (int)$fields_list['max_labels'];
		// remove if printed / outside EU (or reached max)
		if (! empty($fields_list['count_printed']) || Service::isNonEu($fields_list['shm']) || $num_retours >= $max_labels)
			return;
		//
		$tpl_vars = array(
			'action' => $this->l('Create retour'),
			'href' => Tools::safeOutput(self::$currentIndex.'&reference='.$reference.'&createRetour'.$this->table
				.'&token='.($token != null ? $token : $this->token)),
		);
		// SRG: 14-8-16 - enabled only up to normal label count
		// $count_diff = (int)$fields_list['count_normal'] - (int)$fields_list['count_retours'];
		$count_diff = $num_labels - $num_retours;
		// SRG >>: 30-09-17
		$returnable = true;
		/*if ($count_diff > 0)
			$tpl_vars['href'] .= '&count='.$count_diff;
		else
			$tpl_vars['disabled'] = $this->l('Retour count cannot exeed normal labels');*/
		//
		// SRG: 15-7-17 - PUGO has no retour !!
		// $returnable = BpostShm::SHM_PPI !== (int)$fields_list['shm'];
		if ($returnable && $count_diff > 0)
			$tpl_vars['href'] .= '&count='.$count_diff;
		else
		{
			$msg_disabled = $returnable ? 'Retour count cannot exeed normal labels' : 'Retour is not available for PUGO';
			$tpl_vars['disabled'] = $this->l($msg_disabled);
		}
		//
		if ('CANCELLED' == $fields_list['status_bpost'])
			$tpl_vars['disabled'] = $this->l('Order is Cancelled at bpost SHM');

		$tpl = $this->createTemplate('helpers/list/list_action_option.tpl');
		$tpl->assign($tpl_vars);
		return $tpl->fetch();
	}

	/**
	 * @param null|string $token
	 * @param string $reference
	 * @return mixed
	 */
	public function displayPrintLabelsLink($token = null, $reference = '')
	{
		if (empty($reference))
			return;

		$fields_list = $this->current_row;
		$tpl_vars = array(
			'action' => $this->l('Print labels'),
			'href' => Tools::safeOutput(self::$currentIndex.'&reference='.$reference.'&printLabels'.$this->table
				.'&token='.($token != null ? $token : $this->token))
		);

		if ('CANCELLED' == $fields_list['status_bpost'])
			$tpl_vars['disabled'] = $this->l('Order is Cancelled at bpost SHM');

		$tpl = $this->createTemplate('helpers/list/list_action_option.tpl');
		$tpl->assign($tpl_vars);
		return $tpl->fetch();
	}

	/**
	 * @param null|string $token
	 * @param string $reference
	 * @return mixed
	 */
	public function displayRefreshStatusLink($token = null, $reference = '')
	{
		$fields_list = $this->current_row;
		if (empty($reference) || empty($fields_list))
			return;

		$tpl_vars = array(
			'action' => $this->l('Refresh status'),
			'href' => Tools::safeOutput(self::$currentIndex.'&reference='.$reference.'&refreshStatus'.$this->table
				.'&token='.($token != null ? $token : $this->token)),
		);

		// disable if labels are not PRINTED
		if (empty($fields_list['count_printed']))
			$tpl_vars['disabled'] = $this->l('Actions are only available for orders that are printed.');
		if ('CANCELLED' == $fields_list['status_bpost'])
			$tpl_vars['disabled'] = $this->l('Order is Cancelled at bpost SHM');

		$tpl = $this->createTemplate('helpers/list/list_action_option.tpl');
		$tpl->assign($tpl_vars);
		return $tpl->fetch();
	}

	/**
	 * @param null|string $token
	 * @param string $reference
	 * @return mixed
	 */
	public function displayMarkTreatedLink($token = null, $reference = '')
	{
		$fields_list = $this->current_row;
		if (empty($reference) || empty($fields_list))
			return;

		$tpl_vars = array(
			'action' => $this->l('Mark treated'),
			'href' => Tools::safeOutput(self::$currentIndex.'&reference='.$reference.'&markTreated'.$this->table
				.'&token='.($token != null ? $token : $this->token)),
		);

		// disable if labels are not PRINTED
		if (empty($fields_list['count_printed']))
			$tpl_vars['disabled'] = $this->l('Actions are only available for orders that are printed.');
		// elseif ($this->bpost_treated_state == (int)$fields_list['current_state'])
		elseif ((bool)$fields_list['treated'])
			$tpl_vars['disabled'] = $this->l('Order is already treated.');

		$tpl = $this->createTemplate('helpers/list/list_action_option.tpl');
		$tpl->assign($tpl_vars);
		return $tpl->fetch();
	}

	/**
	 * @param null|string $token
	 * @param string $reference
	 * @return mixed
	 */
	public function displaySendTTEmailLink($token = null, $reference = '')
	{
		/*
		// Srg: 11-apr-19
		// Do not display if T&T mails are automatically sent
		if (empty($reference) || (bool)Configuration::get('BPOST_LABEL_TT_INTEGRATION'))
			return;
		*/

		$fields_list = $this->current_row;		
		// Do not display if T&T mails are sent or automatically sent
		if (empty($reference) || !empty((string)$fields_list['tracking_number']) || (bool)Configuration::get('BPOST_LABEL_TT_INTEGRATION'))
			return;

		$tpl_vars = array(
			'action' => $this->l('Send Track & Trace e-mail'),
			'href' => Tools::safeOutput(self::$currentIndex.'&reference='.$reference.'&sendTTEmail'.$this->table
				.'&token='.($token != null ? $token : $this->token)),
		);

		// Srg: 4-apr-19: new disabled state
		// disable if ...
		// T&T email already sent
		if (! empty((string)$fields_list['tracking_number']))
			$tpl_vars['disabled'] = $this->l('TT mail already sent');
		// labels are not yet PRINTED
		elseif (empty($fields_list['count_printed']))
			$tpl_vars['disabled'] = $this->l('Actions are only available for orders that are printed.');
		// order was cancelled
		elseif ('CANCELLED' == $fields_list['status_bpost'])
			$tpl_vars['disabled'] = $this->l('Order is Cancelled at bpost SHM');
		/*
		// T&T email already sent
		else
		{
			$ps_order = new Order((int)Service::getOrderIDFromReference($reference));
			if (! empty((string)$ps_order->shipping_number))
				$tpl_vars['disabled'] = $this->l('TT mail already sent');
		}
		*/
		$tpl = $this->createTemplate('helpers/list/list_action_option.tpl');
		$tpl->assign($tpl_vars);
		return $tpl->fetch();
	}

	/**
	 * @param null|string $token
	 * @param string $reference
	 * @return mixed
	 */
	public function displayViewLink($token = null, $reference = '')
	{
		if (empty($reference))
			return;

		$tpl_vars = array(
			'action' => $this->l('Open order'),
			'target' => '_blank',
		);

		$ps_order = new Order((int)Service::getOrderIDFromReference($reference));
		$token = Tools::getAdminTokenLite('AdminOrders');
		$tpl_vars['href'] = 'index.php?tab=AdminOrders&vieworder&id_order='.(int)$ps_order->id.'&token='.$token;

		$tpl = $this->createTemplate('helpers/list/list_action_option.tpl');
		$tpl->assign($tpl_vars);
		return $tpl->fetch();
	}

	/**
	 * @param null|string $token
	 * @param string $reference
	 * @return mixed
	 */
	public function displayCancelLink($token = null, $reference = '')
	{
		$fields_list = $this->current_row;
		if (empty($reference))
			return;

		$tpl_vars = array(
			'action' => $this->l('Cancel order'),
			'href' => Tools::safeOutput(self::$currentIndex.'&reference='.$reference.'&cancel'.$this->table
				.'&token='.($token != null ? $token : $this->token)),
		);

		// disable if labels have already been PRINTED
		if ((bool)$fields_list['count_printed'])
			$tpl_vars['disabled'] = $this->l('Only open orders can be cancelled.');

		$tpl = $this->createTemplate('helpers/list/list_action_option.tpl');
		$tpl->assign($tpl_vars);
		return $tpl->fetch();
	}
}
