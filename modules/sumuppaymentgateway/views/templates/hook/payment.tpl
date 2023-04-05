{*
* 2007-2021 PrestaShop
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
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2022 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

<div class="row">
    <div class="col-xs-12 col-md-12">
        <p class="payment_module" id="sumup_payment_button">
            <a style="padding: 15px;" href="{$paymentControllerLink|escape:'htmlall':'UTF-8'}" title="{l s=$text mod='sumuppaymentgateway'}">
                <img style="width: 65px; margin-right: 15px;" src="{$logoUrl|escape:'htmlall':'UTF-8'}" alt="{l s=$text mod='sumuppaymentgateway'}"/>
                {l s=$text|escape:'htmlall':'UTF-8' mod='sumuppaymentgateway'}
                {if !empty($error_msg)} <span>(Error: {l s=$error_msg|escape:'htmlall':'UTF-8' mod='sumuppaymentgateway'})</span>{/if}
            </a>

        </p>
    </div>
</div>

{if $popup eq 1}

    <div class="sumup-module-wrap">
        <div class="sumup-content">
            <span class="close-sumup-content">×</span>
            <div style="padding-top: 20px;">
                <div id="sumup-card"></div>
            </div>
        </div>
    </div>
    <div class="loading sumup_loading" style="display: none">
        <div class="loading-wheel"></div>
    </div>

    <script src="https://gateway.sumup.com/gateway/ecom/card/v2/sdk.js"></script>
    <script>
        $(document).ready(function (e) {
            var checkoutId = "{$checkoutId|escape:'javascript':'UTF-8'}";
            var locale = "{$locale|escape:'javascript':'UTF-8'}";
            var zip_code = "{$zip_code|escape:'javascript':'UTF-8'}";
            var paymentCurrency = "{$paymentCurrency|escape:'javascript':'UTF-8'}";
            var paymentAmount = "{$paymentAmount|escape:'javascript':'UTF-8'}";
            var paymentControllerLink  = "{$paymentControllerLink|escape:'javascript':'UTF-8'}";

            mountSumupCard(checkoutId, paymentControllerLink, locale, paymentCurrency, paymentAmount, zip_code);

            $('#sumup_payment_button').on('click', function (e) {
                e.preventDefault();
                toggleSumupModal();
            });
        });
    </script>
{/if}
