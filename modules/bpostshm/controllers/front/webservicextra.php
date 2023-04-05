<?php
/**
 * Web-Service API extra front controller v1.63.0
 *
 * @author    Serge <serge@stigmi.eu>
 * @copyright Copyright (c), Eontech.net All rights reserved.
 * @license   BSD License
 */

require_once(dirname(__FILE__).'/bpostbase.php');

class BpostShmWebServiceXtraModuleFrontController extends BpostShmBpostBaseModuleFrontController
{
	protected function processContent()
	{
		$id_shop = Tools::getValue('id_shop', false);
		if ($id_shop)
			Shop::setContext(Shop::CONTEXT_SHOP, (int)$id_shop);

		$service = new Service($this->context);
		if (Tools::getValue('get_enabled_countries'))
		{
			// >> Srg: 23-aug-17
			// $available_countries = $service->getProductCountries();
			// $this->jsonEncode($available_countries);
			$product_config = $service->getProductConfig();
			// $product_countries = $product_config['countries'];
			$this->jsonEncode($product_config);
			// << Srg:
		}
		elseif (Tools::getValue('panel_info_off'))
		{
			if (! ($response = Configuration::updateGlobalValue('BPOST_DISPLAY_ADMIN_INFO', (int)false)))
				$response['errors']['Dev'][] = 'Cannot switch off admin info panel';

			$this->jsonEncode($response);
		}
		elseif (Tools::getValue('debug_log_set'))
		{
			$checked = (bool)Tools::getValue('checked');
			if (! ($response = Configuration::updateGlobalValue('BPOST_DEBUG_LOG_ENABLE', (int)$checked)))
				$response['errors']['Dev'][] = 'Cannot set debug logging';

			$this->jsonEncode($response);
		}
		elseif (Tools::getValue('cid_resync'))
		{
			if (! ($response = $this->module->syncCheckCarrierRefs(true)))
				$response['errors']['debug'][] = "Auto resync failed.  Carrier IDs must be synced manually.";

			$this->jsonEncode($response);
		}
		elseif (Tools::getValue('get_debug_xml'))
		{
			$key = (int)Tools::getValue('key');
			$qr = (bool)Tools::getValue('qr');
			if (! ($link = $service->getDebugXmlLink($key, $qr)))
				$link['errors']['debug'][] = 'Cannot obtain XML link at this time';

			$this->jsonEncode($link);
		}
	}
}