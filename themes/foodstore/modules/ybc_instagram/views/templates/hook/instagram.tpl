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
{if ($YBC_INSTAGRAM_HOOK == 'footer')}
    <div class="{if $is_17}links wrapper{else} footer-block col-xs-12 col-sm-4{/if} ybc_instagram ybc_instagram_hook_{$YBC_INSTAGRAM_HOOK|escape:'html':'UTF-8'} {if $YBC_INSTAGRAM_ENABLE_ZOOM_HOVER}zooming_enabled{/if} {if $is_17}is_17{else}is_16{/if}">

        {if $is_17}
        <div class="ybc_instagram_header title hidden-sm-down">
            {/if}
            <h4 class="h3">{l s='Instagram' mod='ybc_instagram'}</h4>
            {if $is_17}
        </div>
        <div class="ybc_instagram_header title hidden-md-up" data-target="#footer_instagram" data-toggle="collapse">
            <h4 class="h3">{l s='Instagram' mod='ybc_instagram'}</h4>
            <span class="pull-xs-right">
                  <span class="navbar-toggler collapse-icons">
                    <i class="material-icons add">&#xE313;</i>
                    <i class="material-icons remove">&#xE316;</i>
                  </span>
                </span>
        </div>
        {/if}

        {if $IMGs}
            <div id="footer_instagram" class="{if $is_17}collapse{else}toggle-footer{/if}">
                {if $YBC_INSTAGRAM_DISPLAY_FOLLOW && $YBC_INSTAGRAM_URL && $YBC_INSTAGRAM_DISPLAY_NAME}
                    <a class="btn btn-primary" href="{$YBC_INSTAGRAM_URL|escape:'html':'UTF-8'}"
                       target="_blank">{l s='Follow us on Instagram' mod='ybc_instagram'}</a>
                {/if}
                {if $YBC_INSTAGRAM_DISPLAY_USERNAME && $YBC_INSTAGRAM_URL && $YBC_INSTAGRAM_DISPLAY_NAME}
                    <a class="ybc_instagram_user" target="_blank"
                       href="{$YBC_INSTAGRAM_URL|escape:'html':'UTF-8'}">@{$YBC_INSTAGRAM_DISPLAY_NAME|escape:'html':'UTF-8'}</a>
                {/if}
                <ul class="instagram_list_img">
                    {assign var='ik' value=0}
                    {foreach from=$IMGs item='img'}
                        {assign var='ik' value=$ik+1}
                        {if $ik <= $YBC_INSTAGRAM_IMG_NUMBER}
                            <li class="instagram_item_img">
                                <div class="instagram_item_content">
                                    <a {if $YBC_INSTAGRAM_ENABLE_FANCY}class="ybc_instagram_fancy"
                                       href="{$img.standard_resolution|escape:'html':'UTF-8'}" rel="gallery"
                                       {else}href="{$img.link|escape:'html':'UTF-8'}" target="_blank"{/if}>
                                        <img {if $img.caption|escape:'html':'UTF-8'}alt="{$img.caption|escape:'html':'UTF-8'}"{/if}
                                             src="{$img.thumbnail|escape:'html':'UTF-8'}" alt=""/>
                                    </a>
                                    {if $YBC_INSTAGRAM_DISPLAY_LIKE_COUNT || $YBC_INSTAGRAM_DISPLAY_COMMENT_COUNT}
                                        <div class="ybc_instagram_info">
                                            {if $YBC_INSTAGRAM_DISPLAY_LIKE_COUNT}
                                                <span class="ybc_instagram_likes"
                                                      title="{l s='Likes' mod='ybc_instagram'}">{$img.likes|intval}</span>
                                            {/if}
                                            {if $YBC_INSTAGRAM_DISPLAY_COMMENT_COUNT}
                                                <span class="ybc_instagram_comments"
                                                      title="{l s='Comments' mod='ybc_instagram'}">{$img.comments|intval}</span>
                                            {/if}
                                        </div>
                                    {/if}
                                </div>
                            </li>
                        {/if}
                    {/foreach}
                </ul>
                {if $YBC_INSTAGRAM_ENABLE_LOAD_MORE && $next_url}
                    <a href="{$YBC_INSTAGRAM_URL|escape:'html':'UTF-8'}"
                       class="ybc_instagram_load_more">{l s='More images' mod='ybc_instagram'}</a>
                {/if}
            </div>
        {/if}
    </div>
{else}
    <div class="ybc_instagram ybc_instagram_hook_{$YBC_INSTAGRAM_HOOK|escape:'html':'UTF-8'} {if $YBC_INSTAGRAM_ENABLE_ZOOM_HOVER}zooming_enabled{/if} {if $is_17}is_17{else}{if ($YBC_INSTAGRAM_HOOK == 'left' || $YBC_INSTAGRAM_HOOK == 'right')}block{/if}{/if}">
        {if $is_17}
            <div class="ybc_instagram_header">
                {*<h4 class="h3{if ($YBC_INSTAGRAM_HOOK == 'left' || $YBC_INSTAGRAM_HOOK == 'right')} title_block{/if}">{l s='Instagram' mod='ybc_instagram'}</h4>*}
                {*if $YBC_INSTAGRAM_DISPLAY_FOLLOW && $YBC_INSTAGRAM_URL && $YBC_INSTAGRAM_DISPLAY_NAME}
                    <a class="btn btn-primary" href="{$YBC_INSTAGRAM_URL|escape:'html':'UTF-8'}"
                       target="_blank">{l s='Follow us on Instagram' mod='ybc_instagram'}</a>
                {/if*}
            </div>
            <div class="wraper_title_section">
                <h4 class="h1 products-section-title text-uppercase home_title_section">
                    {if $YBC_INSTAGRAM_DISPLAY_FOLLOW && $YBC_INSTAGRAM_URL && $YBC_INSTAGRAM_DISPLAY_NAME}
                            <a class="h1 products-section-title text-uppercase home_title_section" href="{$YBC_INSTAGRAM_URL|escape:'html':'UTF-8'}"
                               target="_blank">{l s='Follow us on Instagram' mod='ybc_instagram'}</a>
                        {/if}
                </h4>
                <span class="sub_title_section">
                    {if $YBC_INSTAGRAM_DISPLAY_USERNAME && $YBC_INSTAGRAM_URL && $YBC_INSTAGRAM_DISPLAY_NAME}
                        <a class="ybc_instagram_user" target="_blank"
                           href="{$YBC_INSTAGRAM_URL|escape:'html':'UTF-8'}">@{$YBC_INSTAGRAM_DISPLAY_NAME|escape:'html':'UTF-8'}</a>
                    {/if}
                </span>
                <div class="line_sub"></div>
            </div>
        {else}
            {if ($YBC_INSTAGRAM_HOOK == 'left' || $YBC_INSTAGRAM_HOOK == 'right')}
                <h4 class="title_block">{l s='Instagram' mod='ybc_instagram'}</h4>
            {else}
                <div class="ybc_instagram_header">
                    <h4 class="h3{if ($YBC_INSTAGRAM_HOOK == 'left' || $YBC_INSTAGRAM_HOOK == 'right')} title_block{/if}">{l s='Instagram' mod='ybc_instagram'}</h4>
                    {if $YBC_INSTAGRAM_DISPLAY_FOLLOW && $YBC_INSTAGRAM_URL && $YBC_INSTAGRAM_DISPLAY_NAME}
                        <a class="btn btn-primary" href="{$YBC_INSTAGRAM_URL|escape:'html':'UTF-8'}"
                           target="_blank">{l s='Follow us on Instagram' mod='ybc_instagram'}</a>
                    {/if}
                </div>
                {if $YBC_INSTAGRAM_DISPLAY_USERNAME && $YBC_INSTAGRAM_URL && $YBC_INSTAGRAM_DISPLAY_NAME}
                    <a class="ybc_instagram_user" target="_blank"
                       href="{$YBC_INSTAGRAM_URL|escape:'html':'UTF-8'}">@{$YBC_INSTAGRAM_DISPLAY_NAME|escape:'html':'UTF-8'}</a>
                {/if}
            {/if}
        {/if}


        {if $IMGs}
            {if !$is_17}
                {if ($YBC_INSTAGRAM_HOOK == 'left' || $YBC_INSTAGRAM_HOOK == 'right')}
                    <div class="block_content">
                    {if $YBC_INSTAGRAM_DISPLAY_FOLLOW && $YBC_INSTAGRAM_URL && $YBC_INSTAGRAM_DISPLAY_NAME}
                        <a class="btn btn-primary instagram_follow_button"
                           href="{$YBC_INSTAGRAM_URL|escape:'html':'UTF-8'}"
                           target="_blank">{l s='Follow us on Instagram' mod='ybc_instagram'}</a>
                    {/if}
                    {if $YBC_INSTAGRAM_DISPLAY_USERNAME && $YBC_INSTAGRAM_URL && $YBC_INSTAGRAM_DISPLAY_NAME}
                        <a class="ybc_instagram_user" target="_blank" href="{$YBC_INSTAGRAM_URL|escape:'html':'UTF-8'}">@{$YBC_INSTAGRAM_DISPLAY_NAME|escape:'html':'UTF-8'}</a>
                    {/if}
                {/if}
            {/if}
            <ul class="instagram_list_img{if ($YBC_INSTAGRAM_HOOK == 'left' || $YBC_INSTAGRAM_HOOK == 'right')} block_content products-block{/if}">
                {assign var='ik' value=0}
                {foreach from=$IMGs item='img'}
                    {assign var='ik' value=$ik+1}
                    {if $ik <= $YBC_INSTAGRAM_IMG_NUMBER}
                        <li class="instagram_item_img {if isset($tc_config.YBC_TC_FLOAT_CSS3) && $tc_config.YBC_TC_FLOAT_CSS3 == 1} wow zoomIn{/if}">
                            <div class="instagram_item_content">
                                <a {if $YBC_INSTAGRAM_ENABLE_FANCY}class="ybc_instagram_fancy"
                                   href="{$img.standard_resolution|escape:'html':'UTF-8'}" {*rel="gallery"*}
                                   {else}href="{$img.link|escape:'html':'UTF-8'}" target="_blank"{/if}>
                                    <img {if $img.caption|escape:'html':'UTF-8'}alt="{$img.caption|escape:'html':'UTF-8'}"{/if}
                                         src="{$img.thumbnail|escape:'html':'UTF-8'}" alt=""/>
                                </a>
                                {if $YBC_INSTAGRAM_DISPLAY_LIKE_COUNT || $YBC_INSTAGRAM_DISPLAY_COMMENT_COUNT}
                                    <div class="ybc_instagram_info">
                                        {if $YBC_INSTAGRAM_DISPLAY_LIKE_COUNT}
                                            <span class="ybc_instagram_likes"
                                                  title="{l s='Likes' mod='ybc_instagram'}">{$img.likes|intval}</span>
                                        {/if}
                                        {if $YBC_INSTAGRAM_DISPLAY_COMMENT_COUNT}
                                            <span class="ybc_instagram_comments"
                                                  title="{l s='Comments' mod='ybc_instagram'}">{$img.comments|intval}</span>
                                        {/if}
                                    </div>
                                {/if}
                            </div>
                        </li>
                    {/if}
                {/foreach}
            </ul>
            {if $YBC_INSTAGRAM_ENABLE_LOAD_MORE && $next_url}
                <a href="{$YBC_INSTAGRAM_URL|escape:'html':'UTF-8'}"
                   class="ybc_instagram_load_more">{l s='More images' mod='ybc_instagram'}</a>
            {/if}

            {if !$is_17}
                {if ($YBC_INSTAGRAM_HOOK == 'left' || $YBC_INSTAGRAM_HOOK == 'right')}
                    </div>
                {/if}
            {/if}
        {/if}
    </div>
{/if}
<script type="text/javascript">
    var instagram_next_url = '{$next_url|escape:'html':'UTF-8'}';
    var instagram_ajax_url = '{$ajax_url|escape:'html':'UTF-8'}';
    var instagram_auto_play = {$YBC_INSTAGRAM_AUTOPLAY_FANCY|intval};
    var instagram_no_more_img_text = '{l s='No more images available' mod='ybc_instagram'}';
</script>