{*
* 2021 Stigmi
*
* @author Serge <Stigmi.eu>
* @copyright 2021 Stigmi
* @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*}
{if $version >= 1.7}{include "{$bpost_tpl_dir|escape}inc17.tpl"}{/if}
<div id="service-point" class="at-shop{if $version == 1.5} v1-5{/if}">
	{* {capture "title_msg"}
		{if isset($is_valid) && $is_valid}
			{l s='Select where you want to pick up your parcel.' mod='bpostshm'}
		{else}
			{l s='Invalid postcode. Please provide a correct address.' mod='bpostshm'}
		{/if}
	{/capture} *}
	<header>
		<div class="info right">{$country_name|escape}</div>
		{* <div class="title{if !$is_valid} error{/if}">{$smarty.capture.title_msg}</div> *}
		{if isset($is_valid) && ! $is_valid}
			<div class="title error">{l s='Invalid profile address. Please correct your profile address.' mod='bpostshm'}</br></div>
		{/if}
		<div class="title">{l s='Select where you want to pick up your parcel.' mod='bpostshm'}</div>
	</header>
	<form action="{$url_get_nearest_service_points|escape}" id="search-form" method="GET" target="_self" autocomplete="off">
		{if 'BE' == $country_iso}
		<div class="form-group">
			<label for="address1">{l s='Address1' mod='bpostshm'}</label>
			<input type="text" name="address1" id="address1" value="{if isset($address1)}{$address1|escape}{/if}" size="50" />
		</div>
		{else}
			<input type="hidden" name="address1" id="address1" value="" />
		{/if}
		<div class="form-group">
			<label for="postcode">{l s='Postcode' mod='bpostshm'}</label>
			<input type="text" name="postcode" id="postcode" value="{$postcode|escape}" size="10" />
		</div>
		{if 'BE' == $country_iso}
		<div class="form-group">
			<label for="city">{l s='City' mod='bpostshm'}</label>
			<input type="text" name="city" id="city" value="{if isset($city)}{$city|escape}{/if}" size="25" />
		</div>
		{else}
			<input type="hidden" name="city" id="city" value="" />
		{/if}
		<p class="submit">
			<input type="submit" name="searchSubmit" class="button" value="{l s='Search' mod='bpostshm'}" />
			<img class="loader" src="{$module_dir|escape}views/img/ajax-loader.gif" alt="{l s='Loading...' mod='bpostshm'}" />
		</p>
	</form>
	<div class="clearfix">
		<ul id="poi" class="col-xs-4 alpha">{strip}
			<li class="hidden">
				<a title="" class="clearfix">
					{* <img src="{$module_dir|escape}views/img/bpost-poi.png" alt="bpost" /> *}
					<img class="icon" src="{$module_dir|escape}views/img/bpost-poi.png" alt="bpost" />
					<span class="details"></span>
				</a>
			</li>
		{/strip}</ul>
		<div class="col-xs-8 omega">
			<div id="map-canvas"></div>
		</div>
	</div>

	<script type="text/javascript">
		google.maps.event.addDomListener(window, 'load', function() {
			// ServicePointer.icon = '{$module_dir|escape:'javascript'}views/img/bpost-poi.png';
			ServicePointer.icon = {
				// bpost: '{$module_dir|escape:'javascript'}views/img/bpost-poi.png',
				ppoint: '{$module_dir|escape:'javascript'}views/img/poi-ppoint.png',
				plocker: '{$module_dir|escape:'javascript'}views/img/poi-plocker.png',
				// kariboo: '{$module_dir|escape:'javascript'}views/img/kariboo-poi.png'
				kariboo: '{$module_dir|escape:'javascript'}views/img/poi-kariboo.png'
			};
			ServicePointer.lang = {
				'Next step': 		"{l s='Next step' mod='bpostshm' js=1}",
				'Closed':			"{l s='Closed' mod='bpostshm' js=1}",
				'No results found':	"{l s='No results found' mod='bpostshm' js=1}",
				'Monday': 			"{l s='Monday' mod='bpostshm' js=1}",
				'Tuesday': 			"{l s='Tuesday' mod='bpostshm' js=1}",
				'Wednesday': 		"{l s='Wednesday' mod='bpostshm' js=1}",
				'Thursday': 		"{l s='Thursday' mod='bpostshm' js=1}",
				'Friday': 			"{l s='Friday' mod='bpostshm' js=1}",
				'Saturday': 		"{l s='Saturday' mod='bpostshm' js=1}",
				'Sunday': 			"{l s='Sunday' mod='bpostshm' js=1}"
			};
			ServicePointer.services = {
				get_nearest_service_points:	'{$url_get_nearest_service_points|escape:'javascript' nofilter}',
				get_service_point_hours:	'{$url_get_service_point_hours|escape:'javascript' nofilter}',
				set_service_point:			'{$url_set_service_point|escape:'javascript' nofilter}'
			};
			ServicePointer.init(
				'{$country_iso|escape}',
				{$version|floatval},
				{$servicePoints|json_encode nofilter}, 
				{$shipping_method|intval}
			);

			$(window).resize(function() {
				google.maps.event.trigger(ServicePointer.map, 'resize');
			});
			google.maps.event.trigger(ServicePointer.map, 'resize');
		});

		$(function() {
			$(document)
				.ajaxStart(function() {
					ServicePointer.is_busy = true;
					$('.loader').css('display', 'inline-block');
				})
				.ajaxComplete(function() {
					$('.loader').hide();
					ServicePointer.is_busy = false;
				});
		});
	</script>
</div>