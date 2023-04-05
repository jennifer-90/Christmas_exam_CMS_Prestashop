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

{extends file='page.tpl'}
{block name='page_content'}
    <script
            src="https://code.jquery.com/jquery-3.3.1.min.js"
            integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
            crossorigin="anonymous"></script>
    <script src="https://gateway.sumup.com/gateway/ecom/card/v2/sdk.js"></script>

    <div id="sumup-card"></div>
    <div class="loading sumup_loading" style="display: none">
        <div class="loading-wheel"></div>
    </div>

    <script>
        var checkoutId = "{$checkoutId|escape:'javascript':'UTF-8'}";
        var locale = "{$locale|escape:'javascript':'UTF-8'}";
        var zip_code = "{$zip_code|escape:'javascript':'UTF-8'}";
        var paymentControllerLink = "{$paymentControllerLink|escape:'javascript':'UTF-8'}";
        var paymentCurrency = "{$paymentCurrency|escape:'javascript':'UTF-8'}";
        var paymentAmount = "{$paymentAmount|escape:'javascript':'UTF-8'}";
        $(document).ready(function () {
            mountSumupCard(checkoutId, paymentControllerLink, locale, paymentCurrency, paymentAmount, zip_code);
        })
    </script>
{/block}