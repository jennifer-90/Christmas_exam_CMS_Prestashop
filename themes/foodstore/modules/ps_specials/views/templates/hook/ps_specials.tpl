{*
* 2007-2022 ETS-Soft
*
* NOTICE OF LICENSE
*
* This file is not open source! Each license that you purchased is only available for 1 wesite only.
* If you want to use this file on more websites (or projects), you need to purchase additional licenses. 
* You are not allowed to redistribute, resell, lease, license, sub-license or offer our resources to any third party.
* 
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs, please contact us for extra customization service at an affordable price
*
*  @author ETS-Soft <etssoft.jsc@gmail.com>
*  @copyright  2007-2022 ETS-Soft
*  @license    Valid for 1 website (or project) for each purchase of license
*  International Registered Trademark & Property of ETS-Soft
*}
<div class="featured-products clearfix home-block-section col-xs-12 col-md-4">
  <h3 class="h1 products-section-title text-uppercase title-home">
    <span>{l s='Specials' d='Shop.Theme.Catalog'}</span>
  </h3>
  <div class="product_group3 owl-carousel">
  {assign var='is' value=0}
    {foreach from=$products item="product"}
       {if $is%3 == 0}    
            <div class="group_blog_item">
        {/if}
        {assign var='is' value=$is+1}
          {include file="catalog/_partials/miniatures/product.tpl" product=$product}
        {if $is%3 == 0}
                            </div>
            {/if}
        {/foreach}
        {if $is%3 != 0}
                </div>
        {/if}
    
  </div>
  {*<a class="all-product-link pull-xs-left pull-md-right h4" href="{$allSpecialProductsLink|escape:'html':'UTF-8'}">
    {l s='All sale products' d='Shop.Theme.Catalog'}<i class="material-icons">&#xE315;</i>
  </a>*}
</div>
{hook h='ybcCustom3'}
