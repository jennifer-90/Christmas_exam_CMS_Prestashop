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

<div class="panel">
    <h3><i class="icon icon-credit-card"></i> {l s='Sumup Online Payments' mod='sumuppaymentgateway'}</h3>
    <div class="row">
        <div class="col-md-1">
            <img src="{$logoUrl|escape:'htmlall':'UTF-8'}" alt="SumUp" class="img-responsive">
        </div>
        <div class="col-md-11">
            <h2>{l s='A better way to get paid' mod='sumuppaymentgateway'}</h2>
            <p style="font-size: 14px;">{l s='The SumUp card terminal in combination with our App allows small merchants to
                accept card payments, using their smartphones or tablets, in a simple, secure and cost-effective
                way. With this module the SumUp comes to Prestashop!' mod='sumuppaymentgateway'}</p>
            <p style="font-size: 14px;">{l s='Need more info?' mod='sumuppaymentgateway'} - <a href="https://sumup.com/global/">{l s='Learn more' mod='sumuppaymentgateway'}</a></p>
            <p style="font-size: 14px;">{l s='Not a user?' mod='sumuppaymentgateway'} - <a href="https://me.sumup.com/login"> {l s='Sign Up' mod='sumuppaymentgateway'}</a></p>
        </div>
    </div>
</div>
<div class="panel">
    <h3><i class="icon icon-tags"></i> {l s='Documentation' mod='sumuppaymentgateway'}</h3>
    <p>
        &raquo; {l s='You can get a PDF documentation to configure this module' mod='sumuppaymentgateway'} :
    <ul>
        <li><a href="{$module_dir|escape:'htmlall':'UTF-8'}documentation/readme_en.pdf" target="_blank">{l s='English' mod='sumuppaymentgateway'}</a></li>
        {*<li><a href="#" target="_blank">{l s='French' mod='sumuppaymentgateway'}</a></li>*}
    </ul>
    </p>
</div>

{if !empty($errors)}
    {foreach from=$errors item=error}
        <div class="alert alert-danger">{$error|escape:'htmlall':'UTF-8'}</div>
    {/foreach}
{/if}

{if !empty($success)}
    <div class="alert alert-success">
        {$success|escape:'htmlall':'UTF-8'}
    </div>
{/if}
