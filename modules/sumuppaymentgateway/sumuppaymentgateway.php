<?php
/**
 * 2007-2020 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(_PS_MODULE_DIR_ . DIRECTORY_SEPARATOR . 'sumuppaymentgateway' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'SumUpSplClassLoader.php');

class Sumuppaymentgateway extends PaymentModule
{
    public $isPs17 = false;
    private $app_id;
    private $app_secret;
    private $account_currency;
    private $grant_type = 'client_credentials';
    private $payment_message = 'PrestaShop Sumup payment module.';
    private $errors = [];

    public function __construct()
    {
        $this->name = 'sumuppaymentgateway';
        $this->tab = 'administration';
        $this->version = '2.1.2';
        $this->author = 'Sumup';
        $this->need_instance = 1;
        $this->module_key = 'e7a3c55b78a9eca58a2bc6d0940fc0dd';
        $this->app_id = Configuration::get('SUMUP_APP_ID');
        $this->app_secret = Configuration::get('SUMUP_CLIENT_ID');
        $this->account_currency = Configuration::get('SUMUP_ACCOUNT_CURRENCY');

        if (version_compare(_PS_VERSION_, '1.7', '>')) {
            $this->isPs17 = true;
        }

        $this->modulToken = Tools::encrypt($this->name . _COOKIE_KEY_);

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('SumUp Online Payments');
        $this->description = $this->l('SumUp Online Payments');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->initErrorMessages();
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {

        $languages = Language::getLanguages(false);
        $values = array();
        foreach ($languages as $lang) {
            $values['SUMUP_TEXT'][$lang['id_lang']] = 'Pay with SumUp';
        }

        $logoUrl = $this->getDefaultLogo();

        Configuration::updateValue('SUMUP_PAYTO_MAIL', '');
        Configuration::updateValue('SUMUP_APP_ID', '');
        Configuration::updateValue('SUMUP_CLIENT_ID', '');
        Configuration::updateValue('SUMUP_ACCOUNT_CURRENCY', 0);
        Configuration::updateValue('SUMUP_TEXT', $values['SUMUP_TEXT']);
        Configuration::updateValue('SUMUP_ZIP_CODE', false);
        Configuration::updateValue('SUMUP_POPUP', false);
        Configuration::updateValue('SUMUP_LOGO', $logoUrl);

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('payment') &&
            $this->registerHook('paymentOptions') &&
            $this->registerHook('paymentReturn') &&
            $this->registerHook('backOfficeHeader');
    }

    public function uninstall()
    {
        Configuration::deleteByName('SUMUP_PAYTO_MAIL');
        Configuration::deleteByName('SUMUP_APP_ID');
        Configuration::deleteByName('SUMUP_CLIENT_ID');
        Configuration::deleteByName('SUMUP_ACCOUNT_CURRENCY');
        Configuration::deleteByName('SUMUP_TEXT');
        Configuration::deleteByName('SUMUP_ZIP_CODE');
        Configuration::deleteByName('SUMUP_POPUP');
        Configuration::deleteByName('SUMUP_LOGO');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */

    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (Tools::isSubmit('submitSumuppaymentgatewayReset')) {
            Configuration::updateValue('SUMUP_PAYTO_MAIL', '');
            Configuration::updateValue('SUMUP_APP_ID', '');
            Configuration::updateValue('SUMUP_CLIENT_ID', '');
            Configuration::updateValue('SUMUP_ACCOUNT_CURRENCY', 0);
            Configuration::updateValue('SUMUP_ZIP_CODE', false);
            Configuration::updateValue('SUMUP_POPUP', false);
            Configuration::updateValue('SUMUP_LOGO', $this->getDefaultLogo());
            Configuration::updateValue('SUMUP_TEXT', 'Pay with SumUp');

            $this->context->smarty->assign('success', $this->l('Successfully Reseted'));
        } else if (((bool)Tools::isSubmit('submitSumuppaymentgatewayModule')) == true) {
            $logoPath = _PS_MODULE_DIR_ . $this->name . '/views/img/payment_images/' . Configuration::get('SUMUP_LOGO');

            if (isset($_FILES['SUMUP_LOGO'])
                && isset($_FILES['SUMUP_LOGO'])
                && !empty($_FILES['SUMUP_LOGO']['tmp_name'])) {
                if ($error = ImageManager::validateUpload($_FILES['SUMUP_LOGO'], 4000000)) {
                    $this->errors[] = Tools::displayError($this->l($error));
                } else {
                    if (Configuration::get('SUMUP_LOGO') != $this->getDefaultLogo()) {
                        @unlink($logoPath);
                    }
                    $file_name = time() . '.png';
                    Configuration::updateValue('SUMUP_LOGO', $file_name);
                    move_uploaded_file($_FILES['SUMUP_LOGO']['tmp_name'], _PS_MODULE_DIR_ . $this->name . '/views/img/payment_images/' . $file_name);
                }
            }
            if (isset($_FILES['SUMUP_CREDENTIALS'])
                && isset($_FILES['SUMUP_CREDENTIALS'])
                && !empty($_FILES['SUMUP_CREDENTIALS']['tmp_name'])) {
                $sumup_credentials = Tools::file_get_contents($_FILES['SUMUP_CREDENTIALS']['tmp_name']);
                $sumup_credentials = Tools::jsonDecode($sumup_credentials, true);
                if (empty($sumup_credentials['client_id']) || empty($sumup_credentials['client_secret'])) {
                    $this->errors[] = Tools::displayError($this->l('Can not parse Sumup Crendialts please check uploaded file'));
                } else {
                    Configuration::updateValue('SUMUP_APP_ID', $sumup_credentials['client_id']);
                    Configuration::updateValue('SUMUP_CLIENT_ID', $sumup_credentials['client_secret']);
                }
            }

            $this->postProcess();
        }

        $module_ok = !empty(Configuration::get('SUMUP_PAYTO_MAIL'));
        $module_ok &= !empty(Configuration::get('SUMUP_APP_ID'));
        $module_ok &= !empty(Configuration::get('SUMUP_CLIENT_ID'));
        $module_ok &= !empty(Configuration::get('SUMUP_ACCOUNT_CURRENCY'));
        $module_ok &= empty($this->errors);

        if ($module_ok && ((bool)Tools::isSubmit('submitSumuppaymentgatewayModule')) == true) {
            $this->context->smarty->assign('success', $this->l('Configurations Successfully Updated'));
        }

        if (!$module_ok) {
            $this->errors[] = $this->l('Your Module is not ready! Please finish the configuration and make sure all the required settings are right and ready to work');
        }

        $logoUrl = $this->getLogoUrl();
        Media::addJSDef(array('logoUrl' => $logoUrl));

        $this->context->smarty->assign('logoUrl', $logoUrl);
        $this->context->smarty->assign('module_dir', $this->_path);
        $this->context->smarty->assign('module_ok', $module_ok);
        $this->context->smarty->assign('errors', $this->errors);

        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        return $output . $this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitSumuppaymentgatewayModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-envelope"></i>',
                        'desc' => $this->l('Sumup Merchant Account Email'),
                        'name' => 'SUMUP_PAYTO_MAIL',
                        'label' => $this->l('Login Email'),
                        'required' => true,
                    ), array(
                        'col' => 6,
                        'required' => true,
                        'type' => 'file',
                        'desc' => $this->l('Upload your Sumup Client Credentials JSON file. You can get your Client Credentials from your SumUp account by following the guide: https://developer.sumup.com/docs/register-app/'),
                        'name' => 'SUMUP_CREDENTIALS',
                        'label' => $this->l('Sumup Client Credentials'),
                    ), array(
                        'col' => 4,
                        'type' => 'select',
                        'desc' => $this->l('Select SumUp account currency'),
                        'name' => 'SUMUP_ACCOUNT_CURRENCY',
                        'label' => $this->l('Account Currency'),
                        'required' => true,
                        'options' => array(
                            'id' => 'id',
                            'name' => 'name',
                            'query' => $this->getCurrencyOptions(),
                        ),
                    ), array(
                        'type' => 'switch',
                        'col' => 3,
                        'label' => $this->l('Show ZIP Code'),
                        'name' => 'SUMUP_ZIP_CODE',
                        'is_bool' => true,
                        'desc' => $this->l('Request ZIP code from your customers on the card payment form. This is mandatory for all merchants from the USA.'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ), array(
                        'type' => 'switch',
                        'col' => 3,
                        'label' => $this->l('Popup'),
                        'name' => 'SUMUP_POPUP',
                        'is_bool' => true,
                        'desc' => $this->l('Activate this option to view payment process in popup. If this option is disabled payment process will be continued in a new page'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ), array(
                        'col' => 4,
                        'type' => 'text',
                        'lang' => true,
                        'prefix' => '<i class="icon icon-align-justify"></i>',
                        'desc' => $this->l('Text will appear in payment selection'),
                        'name' => 'SUMUP_TEXT',
                        'label' => $this->l('Text'),
                    ), array(
                        'col' => 6,
                        'type' => 'file',
                        'desc' => $this->l('Upload new image if you want to change default Sumup logo. Recommended dimension: 65 x 65 '),
                        'name' => 'SUMUP_LOGO',
                        'label' => $this->l('Logo'),
                    ),
                ),
                'buttons' => array(
                    'newBlock' => array(
                        'title' => $this->l('Reset Settings'),
                        'class' => 'pull-left',
                        'type' => 'submit',
                        'name' => 'submitSumuppaymentgatewayReset',
                        'icon' => 'process-icon-reset'
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    public function getCurrencyOptions()
    {
        return array(
            array(
                'id' => 0,
                'name' => 'Select Currency',
            ), array(
                'id' => 'EUR',
                'name' => 'EUR',
            ), array(
                'id' => 'USD',
                'name' => 'USD',
            ), array(
                'id' => 'GBP',
                'name' => 'GBP',
            ), array(
                'id' => 'BGN',
                'name' => 'BGN',
            ), array(
                'id' => 'CHF',
                'name' => 'CHF',
            ), array(
                'id' => 'CZK',
                'name' => 'CZK',
            ), array(
                'id' => 'DKK',
                'name' => 'DKK',
            ), array(
                'id' => 'HUF',
                'name' => 'HUF',
            ), array(
                'id' => 'NOK',
                'name' => 'NOK',
            ), array(
                'id' => 'PLN',
                'name' => 'PLN',
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        $languages = Language::getLanguages(false);
        $fields = array();

        foreach ($languages as $lang) {
            $fields['SUMUP_TEXT'][$lang['id_lang']] = Tools::getValue('SUMUP_TEXT' . $lang['id_lang'], Configuration::get('SUMUP_TEXT', $lang['id_lang']));
        }

        $fields['SUMUP_PAYTO_MAIL'] = Configuration::get('SUMUP_PAYTO_MAIL');
        $fields['SUMUP_APP_ID'] = Configuration::get('SUMUP_APP_ID');
        $fields['SUMUP_CLIENT_ID'] = Configuration::get('SUMUP_CLIENT_ID');
        $fields['SUMUP_ACCOUNT_CURRENCY'] = Configuration::get('SUMUP_ACCOUNT_CURRENCY');
        $fields['SUMUP_ZIP_CODE'] = Configuration::get('SUMUP_ZIP_CODE');
        $fields['SUMUP_POPUP'] = Configuration::get('SUMUP_POPUP');

        return $fields;
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            if ($key == 'SUMUP_APP_ID' || $key == 'SUMUP_CLIENT_ID') {
                continue;
            }
            Configuration::updateValue($key, Tools::getValue($key));
        }

        $languages = Language::getLanguages(false);
        $values = array();
        foreach ($languages as $lang) {
            $values['SUMUP_TEXT'][$lang['id_lang']] = Tools::getValue('SUMUP_TEXT_' . $lang['id_lang']);
        }

        Configuration::updateValue('SUMUP_TEXT', $values['SUMUP_TEXT']);
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name || Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJquery();
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJquery();
        $this->context->controller->addJqueryPlugin('growl');
        $this->context->controller->addJS($this->_path . '/views/js/sumup_front.js');
        $this->context->controller->addCSS($this->_path . '/views/css/sumup_front.css');
    }

    public function getLogoUrl()
    {
        return Tools::getHttpHost(true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/views/img/payment_images/' . Configuration::get('SUMUP_LOGO');
    }

    /**
     * @return bool
     */
    public function getDefaultLogo()
    {
        if (version_compare(_PS_VERSION_, '1.7', '>')) {
            return 'sumup_logo17.png';
        } else {
            return 'sumup_logo.png';
        }
    }

    public function checkCurrencySupport()
    {
        $id_card = Context::getContext()->cart->id;
        $cardObj = new Cart((int)$id_card);
        $currency = new Currency((int)$cardObj->id_currency);

        if (empty($this->account_currency) || $this->account_currency != $currency->iso_code) {
            return false;
        }

        return true;
    }

    public function hookPayment($params)
    {
        if (!$this->active) {
            return;
        }

        if (!Configuration::get('SUMUP_PAYTO_MAIL')) {
            return false;
        }

        if (empty($this->app_id) || empty($this->app_secret)) {
            return false;
        }

        if (!$this->checkCurrencySupport()) {
            return false;
        }

        $logoUrl = $this->getLogoUrl();

        $sumupPaymentOptions = $this->getSumupCheckoutId();

        $this->context->smarty->assign('secure_key', Context::getContext()->customer->secure_key);
        $this->context->smarty->assign('logoUrl', $logoUrl);
        $this->context->smarty->assign('popup', Configuration::get('SUMUP_POPUP'));
        $this->context->smarty->assign('zip_code', (bool)Configuration::get('SUMUP_ZIP_CODE'));
        $this->context->smarty->assign('text', Configuration::get('SUMUP_TEXT', $this->context->language->id));
        $this->context->smarty->assign('error_msg', $sumupPaymentOptions['error_msg']);
        $this->context->smarty->assign('paymentControllerLink', $sumupPaymentOptions['paymentUrl']);
        $this->context->smarty->assign('checkoutId', $sumupPaymentOptions['checkoutId']);
        $this->context->smarty->assign('locale', $this->getLocale());
        $this->context->smarty->assign('paymentAmount', $sumupPaymentOptions['paymentAmount']);
        $this->context->smarty->assign('paymentCurrency', $sumupPaymentOptions['paymentCurrency']);

        return $this->display(__FILE__, 'views/templates/hook/payment.tpl');
    }

    private function getSumupCheckoutId()
    {
        $link = new Link();
        $payToEmail = Configuration::get('SUMUP_PAYTO_MAIL'); //get from config the payto
        $id_card = Context::getContext()->cart->id;
        $cardObj = new Cart((int)$id_card);
        $totalPrice = $cardObj->getOrderTotal(true, Cart::BOTH);
        $currency = new Currency((int)$cardObj->id_currency);
        $paymentId = 'PrestaShop-SumUp-' . uniqid() . '-' . time() . '-id_card=' . $cardObj->id;
        $error_msg = "";
        $paymentUrl = "";
        $checkoutId = 0;

        $loader = new SumUpSplClassLoader("SumUp", _PS_MODULE_DIR_ . DIRECTORY_SEPARATOR . 'sumuppaymentgateway' . DIRECTORY_SEPARATOR . 'sumup_sdk' . DIRECTORY_SEPARATOR . 'src');
        $loader->register();

        try {
            $sumup = new \SumUp\SumUp(array(
                'app_id' => $this->app_id,
                'app_secret' => $this->app_secret,
                'grant_type' => $this->grant_type,
            ));

            $accessToken = $sumup->getAccessToken();

            $accessTokenValue = $accessToken->getValue();

            $sumup = new \SumUp\SumUp(array(
                'app_id' => $this->app_id,
                'app_secret' => $this->app_secret,
                'access_token' => $accessTokenValue,
            ));

            $checkoutService = $sumup->getCheckoutService();
            $checkoutResponse = $checkoutService->create(
                $totalPrice,
                $currency->iso_code,
                $paymentId,
                $payToEmail,
                $this->payment_message
            );

            $checkoutResponseBody = $checkoutResponse->getBody();
            if (!empty($checkoutResponseBody->id)) {
                $paymentUrl = $link->getModuleLink('sumuppaymentgateway', 'payment', array('checkoutId' => $checkoutResponseBody->id, 'amount' => $totalPrice, 'currency' => $currency->iso_code));
                $checkoutId = $checkoutResponseBody->id;
            } else {
                $error_msg = "General Error";
            }
        } catch (\SumUp\Exceptions\SumUpAuthenticationException $e) {
            $error_msg = $this->getErrorMessage('auth_error') . $e->getMessage();
            $traceInfoMessage = $this->getErrorMessageFromTrace($e->getTrace());
            if (!empty($traceInfoMessage)) {
                $error_msg .= ' ' . $traceInfoMessage;
            }
        } catch (\SumUp\Exceptions\SumUpResponseException $e) {
            $error_msg = $this->getErrorMessage('responce_err') . $e->getMessage();
            $traceInfoMessage = $this->getErrorMessageFromTrace($e->getTrace());
            if (!empty($traceInfoMessage)) {
                $error_msg .= ' ' . $traceInfoMessage;
            }
        } catch (\SumUp\Exceptions\SumUpSDKException $e) {
            $error_msg = $this->getErrorMessage('smp_sdk_err') . $e->getMessage();
            $traceInfoMessage = $this->getErrorMessageFromTrace($e->getTrace());
            if (!empty($traceInfoMessage)) {
                $error_msg .= ' ' . $traceInfoMessage;
            }
        }

        return array(
            'paymentUrl' => $paymentUrl,
            'error_msg' => $error_msg,
            'checkoutId' => $checkoutId,
            'paymentAmount' => $totalPrice,
            'paymentCurrency' => $currency->iso_code,
        );
    }

    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }

        if (!Configuration::get('SUMUP_PAYTO_MAIL')) {
            return false;
        }

        if (empty($this->app_id) || empty($this->app_secret)) {
            return false;
        }

        if (!$this->checkCurrencySupport()) {
            return false;
        }

        $sumupPaymentOptions = $this->getSumupCheckoutId();
        $this->context->smarty->assign('popup', Configuration::get('SUMUP_POPUP'));
        $this->context->smarty->assign('zip_code', (bool)Configuration::get('SUMUP_ZIP_CODE'));
        $this->context->smarty->assign('error_msg', $sumupPaymentOptions['error_msg']);
        $this->context->smarty->assign('paymentControllerLink', $sumupPaymentOptions['paymentUrl']);
        $this->context->smarty->assign('checkoutId', $sumupPaymentOptions['checkoutId']);
        $this->context->smarty->assign('locale', $this->getLocale());
        $this->context->smarty->assign('paymentAmount', $sumupPaymentOptions['paymentAmount']);
        $this->context->smarty->assign('paymentCurrency', $sumupPaymentOptions['paymentCurrency']);

        $formHtml = $this->context->smarty->fetch('module:sumuppaymentgateway/views/templates/hook/payment1.7.tpl');
        $logo = $this->getLogoUrl();
        $text = Configuration::get('SUMUP_TEXT', $this->context->language->id);
        $error_msg = $sumupPaymentOptions['error_msg'];
        $paymentOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $paymentOption->setForm($formHtml);
        $paymentOption
            ->setLogo($logo)
            ->setCallToActionText($text);

        if (!empty($error_msg)) {
            $paymentOption->setAdditionalInformation($error_msg);
        }

        return array($paymentOption);
    }

    public function hookPaymentReturn($params)
    {
        if ($this->active == false) {
            return;
        }

        if ($this->isPs17) {
//            PS show confirmation view on 1.7 version
//            $order = $params['order'];
//            $total = @Tools::displayPrice($params['order']->total_paid_tax_incl, $params['order']->id_currency);
        } else {
            $total = Tools::displayPrice($params['total_to_pay'], $params['currencyObj'], false);
            $order = $params['objOrder'];
            if ($order->getCurrentOrderState()->id != Configuration::get('PS_OS_ERROR')) {
                $this->smarty->assign('status', 'ok');
            }


            $this->smarty->assign(array(
                'id_order' => $order->id,
                'reference' => $order->reference,
                'params' => $params,
                'total' => $total,
            ));

            return $this->display(__FILE__, 'views/templates/hook/confirmation.tpl');
        }
    }

    public function initErrorMessages()
    {
        $this->errorMessages = array(
            'card_not_loaded' => $this->l('Can not load cart data', $this->name),
            'auth_error' => $this->l('Authentication error: ', $this->name),
            'responce_err' => $this->l('Response error: ', $this->name),
            'smp_sdk_err' => $this->l('SumUp SDK error: ', $this->name),
            'payment_error' => $this->l('Can not process payment. Pleace check the card', $this->name),
            'order_create_err' => $this->l('Can not create order', $this->name),
            'secure_key_err' => $this->l('Invalid Secure key', $this->name),
        );
    }

    public function getErrorMessage($index)
    {
        return $this->errorMessages[$index];
    }

    private function getErrorMessageFromTrace($traceInfo)
    {
        $errorMessage = '';
        foreach ($traceInfo as $info) {
            if (!empty($info['args'])) {
                foreach ($info['args'] as $message) {
                    if (isset($message->message)) {
                        $errorMessage .= $message->message . ' ';
                    }
                }
            }
        }

        return $errorMessage;
    }

    public function getLocale()
    {
        if ($this->isPs17) {
            $locale = $this->context->language->locale;
        } else {
            $locale = $this->context->language->language_code;
            $locale = explode('-', $locale);
            $locale[1] = Tools::strtoupper($locale[1]);
            $locale = implode('-', $locale);
        }
        $suportedLocales = array(
            "bg-BG", "cs-CZ", "da-DK", "de-AT", "de-CH", "de-DE", "de-LU", "el-CY", "el-GR", "en-GB", "en-IE", "en-MT", "en-US", "es-CL", "es-ES", "et-EE", "fi-FI", "fr-BE", "fr-CH", "fr-FR", "fr-LU", "hu-HU", "it-CH", "it-IT", "lt-LT", "lv-LV", "nb-NO", "nl-BE", "nl-NL", "pt-BR", "pt-PT", "pl-PL", "sk-SK", "sl-SI", "sv-SE"
        );
        if (in_array($locale, $suportedLocales)) {
            return $locale;
        }

        return 'en-GB';
    }

    public function retrieveCheckout($checkoutId)
    {
        $loader = new SumUpSplClassLoader("SumUp", _PS_MODULE_DIR_ . DIRECTORY_SEPARATOR . 'sumuppaymentgateway' . DIRECTORY_SEPARATOR . 'sumup_sdk' . DIRECTORY_SEPARATOR . 'src');
        $loader->register();
        try {
            $sumup = new \SumUp\SumUp(array(
                'app_id' => $this->app_id,
                'app_secret' => $this->app_secret,
                'grant_type' => $this->grant_type,
            ));
        } catch (\SumUp\Exceptions\SumUpSDKException $e) {
            $error_msg = $this->getErrorMessage('smp_sdk_err') . $e->getMessage();
            $traceInfoMessage = $this->getErrorMessageFromTrace($e->getTrace());

            if (!empty($traceInfoMessage)) {
                $this->errors[] = $error_msg;
            }
        }

        return $sumup->getCheckoutService()->findById($checkoutId)->getBody();
    }
}
