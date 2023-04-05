<?php
/**
* 2007-2017 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
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
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2017 PrestaShop SA
*  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  @version  Release: $Revision$
*  International Registered Trademark & Property of PrestaShop SA
*/
if (!defined('_PS_VERSION_'))
	exit;
    
if(!class_exists('ptProduct') && version_compare(_PS_VERSION_, '1.7.0', '<='))
    require_once dirname(__FILE__).'/classes/ptProduct.php';

class Ets_purchasetogetherEtscartModuleFrontController extends ModuleFrontController
{
    public $_errors = array();
    public $productAddeds = array();
    public $is17;
    public $is16;
    /**
     * @see FrontController::init()
    */
    public function init()
    {
        parent::init();
        header('X-Robots-Tag: noindex, nofollow', true);
        $this->is17 = version_compare(_PS_VERSION_, '1.7.0', '>=');
        $this->is16 = version_compare(_PS_VERSION_, '1.6.0', '>=');
    }
    /**
    * @see FrontController::initContent()
    */
    public function initContent()
    {
        parent::initContent();
        
        if(Tools::getIsset('item') && $this->isTokenValid() && version_compare(_PS_VERSION_, '1.7.0', '<=')){
            $idCombination = Tools::getValue('ida',0);
            $id_product = Tools::getValue('idp',0);
            if(!$id_product && !$idCombination){
                $this->ajaxDie(Tools::jsonEncode(array(
                    'hasError' => true,
                    'errors' => array($this->module->l('Product is null','etscart')),
                )));
            }else{
                $this->ajaxDie(Tools::jsonEncode(array(
                    'hasError' => false,
                    'product' => ptProduct::getProductAttribute($id_product, $idCombination, $this->context->shop->id),
                )));
            }
        }
        if ($this->context->cookie->exists() && !$this->errors && !($this->context->customer->isLogged() && !$this->isTokenValid()) ) {
            if (Tools::getIsset('add')) {
                $this->processChangeProductInCart();
            }
        } elseif (!$this->isTokenValid()) {
            if (Tools::getValue('ajax')) {
                $this->ajaxDie(Tools::jsonEncode(array(
                    'hasError' => true,
                    'errors' => array($this->module->l('Impossible to add the product to the cart. Please refresh page.','etscart')),
                )));
            } else {
                Tools::redirect('index.php');
            }
        }
    }
    
    /**
     * Quá trình này thêm nhiều sản phẩm vào giỏ hàng
     */
    protected function processChangeProductInCart()
    {
        $this->ets_purchasetogether = new Ets_purchasetogether();
        $configs = $this->ets_purchasetogether->getConfigs();
        $productIds = Tools::getValue('productIds')?Tools::getValue('productIds'): '';
        $products =  (array)Tools::jsonDecode($productIds);
        $ajax = Tools::getValue('ajax')? true : false;
        
        /** if version is 17*/
        if($this->is17 && $configs['ETS_PT_REQUIRE_CURRENT_PRODUCT']){
            $id_product = (int)Tools::getValue('id_product')?Tools::getValue('id_product'): 0;
            $id_product_attribute = (int)Product::getIdProductAttributesByIdAttributes($id_product, Tools::getValue('group'));
            $product_curr = new stdClass();
            if($id_product && $id_product_attribute && ($qty = Tools::getValue('qty')) && $qty){
                $product_curr->id_product = $id_product;
                $product_curr->id_product_attribute = $id_product_attribute;
                $product_curr->qty = $qty;
                $product_curr->currProduct = 1;
            }
            array_push($products, $product_curr);
        }
        
        /** each product add to cart*/
        if(is_array($products) && $products)
        {
            foreach($products as $prod)
            {
                /** Create key name for product attribute*/
                $key = $prod->id_product.'_'.$prod->id_product_attribute;
                $this->productAddeds[$key]['errors'] = array();
                
                $this->productAddeds[$key]['id_product'] = $prod->id_product;
                $this->productAddeds[$key]['id_product_attribute'] = $prod->id_product_attribute;
                
                /** kiem tra so luong va id sp*/
                if (!$prod->id_product) {
                    $this->productAddeds[$key]['errors'][] = $this->module->l('Product not found', 'etscart');
                    continue;
                }
                /** kiem tra sp co san sang ko*/
                $product = new Product((int)$prod->id_product);
                if (!$product->id || !$product->active || !$product->checkAccess($this->context->cart->id_customer)) {
                    $this->productAddeds[$key]['errors'][] = $this->module->l('This product is no longer available.', 'etscart');
                    continue;
                }
                
                $qty_to_check = $prod->qty;
                $cart_products = $this->context->cart->getProducts();
                
                /** Xac dinh so luong hien co cua san pham va xac dnh la tang hay giam*/
                if (is_array($cart_products)) {   
                    foreach ($cart_products as $cart_product) {
                        if ((!isset($prod->id_product_attribute) || $cart_product['id_product_attribute'] == $prod->id_product_attribute ) &&
                            (isset($prod->id_product) && $cart_product['id_product'] == $prod->id_product)) {
                            $qty_to_check = $cart_product['cart_quantity'];
                            $qty_to_check += $prod->qty;
                            break;
                        }
                    }
                }
                                
                /** Kiem tra so luong san pham co san*/
                if($configs['ETS_PT_EXCLUDE_OUT_OF_STOCK']){
                    if ($prod->id_product_attribute) {
                        if (!Product::isAvailableWhenOutOfStock($product->out_of_stock) && !Attribute::checkAttributeQty($prod->id_product_attribute, $qty_to_check)) {
                            $this->productAddeds[$key]['errors'][] =  $this->module->l('There isn\'t enough product in stock.', 'etscart');                            
                        }
                    } elseif ($product->hasAttributes()) {
                        $minimumQuantity = ($product->out_of_stock == 2) ? !Configuration::get('PS_ORDER_OUT_OF_STOCK') : !$product->out_of_stock;
                        $prod->id_product_attribute = Product::getDefaultAttribute($product->id, $minimumQuantity);
                        // @todo do something better than a redirect admin !!
                        if (!$prod->id_product_attribute) {
                            Tools::redirectAdmin($this->context->link->getProductLink($product));
                        } elseif (!Product::isAvailableWhenOutOfStock($product->out_of_stock) && !Attribute::checkAttributeQty($prod->id_product_attribute, $qty_to_check)) {
                            $this->productAddeds[$key]['errors'][] = $this->module->l('There isn\'t enough product in stock.', 'etscart');                            
                        }
                    } elseif (!$product->checkQty($qty_to_check)) {
                        $this->productAddeds[$key]['errors'][] = $this->module->l('There isn\'t enough product in stock.', 'etscart');                        
                    }
                }
                
                /** Nếu không có lỗi thì thêm sản phẩm vào giỏ hàng */
                if (!count($this->productAddeds[$key]['errors'])) 
                {
                    /** Thêm giỏ hàng nếu không tìm thấy giỏ hàng*/
                    if (!$this->context->cart->id) {
                        if (Context::getContext()->cookie->id_guest) {
                            $guest = new Guest(Context::getContext()->cookie->id_guest);
                            $this->context->cart->mobile_theme = $guest->mobile_theme;
                        }
                        $this->context->cart->add();
                        if ($this->context->cart->id) {
                            $this->context->cookie->id_cart = (int)$this->context->cart->id;
                        }
                    }
                    /** Nếu không có lỗi thì cập nhật sản phẩm vào giỏ hàng*/
                    if (!count($this->productAddeds[$key]['errors'])) {
                        $cart_rules = $this->context->cart->getCartRules();
                        $available_cart_rules = CartRule::getCustomerCartRules($this->context->language->id, (isset($this->context->customer->id) ? $this->context->customer->id : 0), true, true, true, $this->context->cart, false, true);
                        $update_quantity = $this->context->cart->updateQty($prod->qty, $prod->id_product, $prod->id_product_attribute, false, 'up', 0);
                        if ($update_quantity < 0) {
                            $minimal_quantity = ($prod->id_product_attribute) ? Attribute::getAttributeMinimalQty($prod->id_product_attribute) : $product->minimal_quantity;
                            $this->productAddeds[$key]['errors'][] = sprintf($this->module->l('You must add %d minimum quantity', 'etscart'), $minimal_quantity);
                        } elseif (!$update_quantity) {
                            $this->productAddeds[$key]['errors'][] = $this->module->l('You already have the maximum quantity available for this product.', 'etscart');
                        } 
                    }                    
                }  
            }
        }else{
            $this->errors[] = $this->module->l('No products were added to the cart.', 'etscart');
        }        
        $removed = CartRule::autoRemoveFromCart();
        CartRule::autoAddToCart();
        if($this->is17){ 
            $this->ets_purchasetogether->displayAjaxUpdate($this->productAddeds);
        }
    }
    /**
     * Display ajax content (this function is called instead of classic display, in ajax mode)
     */
    public function displayAjax()
    {
        if($this->is17)
            return; 
        if ($this->errors) {
            $this->ajaxDie(Tools::jsonEncode(array('hasError' => true,'errors' => $this->errors)));
        }        
        // write cookie if can't on destruct
        $this->context->cookie->write();

        if (Tools::getIsset('summary')) {
            $result = array();
            if (Configuration::get('PS_ORDER_PROCESS_TYPE') == 1) {
                $groups = (Validate::isLoadedObject($this->context->customer)) ? $this->context->customer->getGroups() : array(1);
                if ($this->context->cart->id_address_delivery) {
                    $deliveryAddress = new Address($this->context->cart->id_address_delivery);
                }
                $id_country = (isset($deliveryAddress) && $deliveryAddress->id) ? (int)$deliveryAddress->id_country : (int)Tools::getCountry();
                Cart::addExtraCarriers($result);
            }
            $result['summary'] = $this->context->cart->getSummaryDetails(null, true);
            $result['customizedDatas'] = Product::getAllCustomizedDatas($this->context->cart->id, null, true);
            $result['HOOK_SHOPPING_CART'] = Hook::exec('displayShoppingCartFooter', $result['summary']);
            $result['HOOK_SHOPPING_CART_EXTRA'] = Hook::exec('displayShoppingCart', $result['summary']);

            foreach ($result['summary']['products'] as $key => &$product) {
                $product['quantity_without_customization'] = $product['quantity'];
                if ($result['customizedDatas'] && isset($result['customizedDatas'][(int)$product['id_product']][(int)$product['id_product_attribute']])) {
                    foreach ($result['customizedDatas'][(int)$product['id_product']][(int)$product['id_product_attribute']] as $addresses) {
                        foreach ($addresses as $customization) {
                            $product['quantity_without_customization'] -= (int)$customization['quantity'];
                        }
                    }
                }
            }
            if ($result['customizedDatas']) {
                Product::addCustomizationPrice($result['summary']['products'], $result['customizedDatas']);
            }

            $json = '';
            Hook::exec('actionCartListOverride', array('summary' => $result, 'json' => &$json));
            $this->ajaxDie(Tools::jsonEncode(array_merge($result, (array)Tools::jsonDecode($json, true))));
        }
        // @todo create a hook
        elseif (file_exists(_PS_MODULE_DIR_.'/ets_purchasetogether/ets_purchasetogether-ajax.php')) {
            require_once(_PS_MODULE_DIR_.'/ets_purchasetogether/ets_purchasetogether-ajax.php');
        }
    }
}