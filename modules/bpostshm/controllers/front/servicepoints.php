<?php
/**
 * Generic front controller v1.60.0
 *
 * @author    Serge <serge@stigmi.eu>
 * @copyright Copyright (c), Eontech.net All rights reserved.
 * @license   BSD License
 */

require_once(dirname(__FILE__).'/bpostbase.php');

class BpostShmServicePointsModuleFrontController extends BpostShmBpostBaseModuleFrontController
{
	public function processContent()
	{
		$shipping_method = Tools::getValue('shipping_method');
		if ($delivery_date = Tools::getValue('dd'))
			$delivery_date = date('d-m-Y', strtotime($delivery_date));

		$service = new Service($this->context);
		$module = $service->module;
		$cart_bpost = PsCartBpost::getByPsCartID((int)$this->context->cart->id);
		// >> Srg: 16 jun 2017
		// Country required info
		$delivery_address = new Address($this->context->cart->id_address_delivery, $this->context->language->id);
		$iso_country = Tools::strtoupper(Country::getIsoById($delivery_address->id_country));
		$l_country = $delivery_address->country;
		// << Srg

		//a//
		if (Tools::getValue('get_nearest_service_points'))
		{
			// >> Srg: 29 aug 2021
			$search_params = array(
				'street' 	=> '',
				'nr' 		=> '',
				'zone' => '',
				'country' => $iso_country,
			);
			$postcode = Tools::getValue('postcode');
			$city = Tools::getValue('city');
			// testing
			// srg 12-sep-2021: for now only BE is tested as functional
			if ('BE' == $iso_country)
			{
				// $street = 'Schupstraat'; $nr = '15'; $postcode = '2018'; $city = 'Antwerpen';
				$address1 = Tools::getValue('address1');
				$street_nr = Service::getAddress1StreetNr($address1);
				$search_params['street'] = $street_nr['street'];
				$search_params['nr'] = $street_nr['nr'];
			}
			// << Srg
			if ($postcode)
				$search_params['zone'] .= (int)$postcode.($city ? ' ' : '');
			if ($city)
				$search_params['zone'] .= (string)$city;
			if ($delivery_date)
				$search_params['dd'] = (string)$delivery_date;

			$service_points = (BpostShm::SHM_PPOINT == $shipping_method) ?
				// $service->getNearestServicePoint($search_params) :
				$service->getNearestServicePoint($search_params, BpostShm::PPT_ALL) :
				$service->getNearestServicePoint($search_params, $shipping_method);
			$this->jsonEncode($service_points);
		}
		elseif (Tools::getValue('get_service_point_hours'))
		{
			$service_point_id = (int)Tools::getValue('service_point_id');
			$sp_type = (int)Tools::getValue('sp_type');
			$service_point_hours = $service->getServicePointHours($service_point_id, $sp_type);
			$this->jsonEncode($service_point_hours);
		}
		elseif (Tools::getValue('set_service_point'))
		{
			$service_point_id = (int)Tools::getValue('service_point_id');
			$sp_type = (int)Tools::getValue('sp_type');
			$this->jsonEncode($cart_bpost->setServicePoint($service_point_id, $sp_type));
		}
		elseif (Tools::getValue('post_upl_unregister'))
		{
			$upl_info = (string)Tools::getValue('post_upl_info');
			$stored = $upl_info === (string)$cart_bpost->upl_info;
			if (!$stored)
			{
				$cart_bpost->upl_info = $upl_info;
				$stored = $cart_bpost->save();
			}

			$this->jsonEncode($stored);
		}

		//p//
		$this->context->smarty->assign('version', Service::getPsVer(), true);
		$this->context->smarty->assign('module_dir', _MODULE_DIR_.$module->name.'/', true);
		$this->context->smarty->assign('shipping_method', $shipping_method, true);
		$this->context->smarty->assign('country_iso', $iso_country, true);
		$this->context->smarty->assign('country_name', $l_country, true);
		switch ($shipping_method)
		{
			case BpostShm::SHM_PPOINT:
				// $named_fields = $service->getNearestValidServicePoint(3, $delivery_date);
				$named_fields = $service->getNearestValidServicePoint(19, $delivery_date);
				foreach ($named_fields as $name => $field)
					$this->context->smarty->assign($name, $field, true);

				$get_nearest_link_params = array(
						'ajax'							=> true,
						'get_nearest_service_points' 	=> true,
						'shipping_method'				=> $shipping_method,
						'token'							=> Tools::getToken($module->name),
				);
				if ($delivery_date) $get_nearest_link_params['dd'] = $delivery_date;
				$this->context->smarty->assign('url_get_nearest_service_points', $this->getBpostLink('servicepoints', $get_nearest_link_params));
				$this->context->smarty->assign('url_get_service_point_hours', $this->getBpostLink('servicepoints',
					array(
						'ajax'						=> true,
						'get_service_point_hours' 	=> true,
						'shipping_method'			=> $shipping_method,
						'token'						=> Tools::getToken($module->name),
				)));
				$this->context->smarty->assign('url_set_service_point', $this->getBpostLink('servicepoints',
					array(
						'ajax'				=> true,
						'set_service_point' => true,
						'shipping_method'	=> $shipping_method,
						'token'				=> Tools::getToken($module->name),
				)));

				// >> Srg: 13 apr 2018
				// $this->setTemplate('map-servicepoint.tpl');
				$this->setBaseTemplate('map-servicepoint.tpl');
				// <<
				break;

			case BpostShm::SHM_PLOCKER:
				$step = (int)Tools::getValue('step', 1);
				switch ($step)
				{
					default:
					case 1:
						$this->context->smarty->assign('step', 1, true);

						$delivery_address = new Address($this->context->cart->id_address_delivery, $this->context->language->id);
						// UPL
						$upl_info = Tools::jsonDecode($cart_bpost->upl_info, true);
						if (!isset($upl_info))
							$upl_info = array(
								'eml' => $this->context->customer->email,
								'mob' => !empty($delivery_address->phone_mobile) ? $delivery_address->phone_mobile : '',
								'rmz' => false,
								);

						$iso_code = $this->context->language->iso_code;
						$upl_info['lng'] = in_array($iso_code, array('fr', 'nl')) ? $iso_code : 'en';
						$this->context->smarty->assign('upl_info', $upl_info, true);
						//
						$this->context->smarty->assign('url_post_upl_unregister', $this->getBpostLink('servicepoints',
							array(
								'ajax'					=> true,
								'post_upl_unregister' 	=> true,
								'shipping_method'		=> $shipping_method,
								'token'					=> Tools::getToken($module->name),
						)));

						$get_point_list_link_params = array(
								'content_only'		=> true,
								'shipping_method'	=> $shipping_method,
								'step'				=> 2,
								'token'				=> Tools::getToken($module->name),
						);
						if ($delivery_date) $get_point_list_link_params['dd'] = $delivery_date;
						$this->context->smarty->assign('url_get_point_list', $this->getBpostLink('servicepoints', $get_point_list_link_params));

						$this->setBaseTemplate('form-upl.tpl');
						break;

					case 2:
						$named_fields = $service->getNearestValidServicePoint($shipping_method, $delivery_date);
						foreach ($named_fields as $name => $field)
							$this->context->smarty->assign($name, $field, true);

						$get_nearest_link_params = array(
								'ajax'							=> true,
								'get_nearest_service_points' 	=> true,
								'shipping_method'				=> $shipping_method,
								'token'							=> Tools::getToken($module->name),
						);
						if ($delivery_date) $get_nearest_link_params['dd'] = $delivery_date;
						$this->context->smarty->assign('url_get_nearest_service_points', $this->getBpostLink('servicepoints', $get_nearest_link_params));
						$this->context->smarty->assign('url_get_service_point_hours', $this->getBpostLink('servicepoints',
							array(
								'ajax'						=> true,
								'get_service_point_hours' 	=> true,
								'shipping_method'			=> $shipping_method,
								'token'						=> Tools::getToken($module->name),
						)));
						$this->context->smarty->assign('url_set_service_point', $this->getBpostLink('servicepoints',
							array(
								'ajax'				=> true,
								'set_service_point' => true,
								'shipping_method'	=> $shipping_method,
								'token'				=> Tools::getToken($module->name),
						)));

						$this->setBaseTemplate('map-servicepoint.tpl');
						break;
				}
				break;
		}
	}

	public function setMedia()
	{
		parent::setMedia();

		$asset_base = 'modules/'.$this->module->name.'/views/';
		$base_uri = __PS_BASE_URI__.$asset_base;
		// Srg: 24-jul-18 - Google maps API key changes
		// temporary fallback measure.  old key will fully deprecate next version.
		$gmaps_api_key = (string)Configuration::get('BPOST_GMAPS_API_KEY');
		// Srg: 22-aug-18
		// if (empty($gmaps_api_key) || 39 !== (int)Tools::strlen($gmaps_api_key))
		if (bpostshm::invalidGmapsApiKey($gmaps_api_key))
			// Srg: 19-sep-18 - Correction: default key stays put for now!
			$gmaps_api_key = BpostShm::DEF_GMAK;
		// $gmaps_url = 'https://maps.googleapis.com/maps/api/js?v=3&key='.static::GMAPS_API_KEY.'&language='.$this->context->language->iso_code;
		$gmaps_url = 'https://maps.googleapis.com/maps/api/js?v=3&key='.$gmaps_api_key.'&language='.$this->context->language->iso_code;

		if (Service::isPrestashop17plus())
		{
			$this->context->smarty->assign('bpost_tpl_dir', sprintf('%s/%stemplates/front/', _PS_ROOT_DIR_, $asset_base), true);
			$this->context->smarty->assign('gmaps_url', $gmaps_url, true);
			return;
		}
		
		$this->addCSS($base_uri.'css/servicepoint.css');
		$this->addCSS($base_uri.'css/jquery.qtip.min.css');
		//
		$this->addJS($base_uri.'js/eon.jquery.base.min.js');
		$this->addJS($base_uri.'js/eon.jquery.servicepointer.min.js');
		$this->addJS($base_uri.'js/jquery.qtip.min.js');
		//
		$this->addJS($gmaps_url);	
	
		
	}
}