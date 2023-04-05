/**
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
 *
 * Don't forget to prefix your containers with your own identifier
 * to avoid any conflicts with others containers.
 */

function mountSumupCard(checkoutId, redirectUrl, locale, currency, amount, zip_code) {
    SumUpCard.mount({
        checkoutId: checkoutId,
        locale: locale,
        currency: currency,
        amount: amount,
        showZipCode: Boolean(zip_code),
        onResponse: function (type, body) {
            if (type == 'success') {
                $('.sumup_loading').show();
                confirmOrder(body, redirectUrl);
            } else if (type == 'error') {
                var message = body.message;
                if (typeof message == 'undefined') {
                    message = ""
                }
                $.growl.error({
                    title: body.error_code,
                    message: message
                });
                $('.sumup_loading').hide();
            }
        },
    });
}

function confirmOrder(responce, redirectUrl) {
    var form = '<input type="hidden" name="status" value=\'' + responce.status + '\'>';
    form += '<input type="hidden" name="amount" value=\'' + responce.amount + '\'>';
    form += '<input type="hidden" name="transaction_code" value=\'' + responce.transaction_code + '\'>';
    form += '<input type="hidden" name="transaction_id" value=\'' + responce.transaction_id + '\'>';
    form += '<input type="hidden" name="id" value=\'' + responce.id + '\'>';
    form += '<input type="hidden" name="checkout_reference" value=\'' + responce.checkout_reference + '\'>';
    form += '<input type="hidden" name="submitValidateOrder" value="1">';
    $('<form action="' + redirectUrl + '" method="POST">' + form + '</form>').appendTo('body').submit();
}

function toggleSumupModal() {
    $('.sumup-module-wrap').toggleClass("show-sumup-modal");
}

$(document).on("click",".sumup-module-wrap", function (e){
    toggleSumupModal();
});