{*
* 2014-2021 Stigmi
*
* @author Serge <serge@stigmi.eu>
* @copyright 2014-2021 Stigmi
* @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*}

{* International settings *}

<form class="form-horizontal{if $version < 1.5} v1-4{elseif $version < 1.6} v1-5{/if}" action="#" method="POST" autocomplete="off">
	<fieldset class="panel" id="fs-intl-set">

{* New instruction set *}
{include file="../blurb/tab_intl-$iso_lang.tpl"}
		
		<div class="form-group">
		{* content start *}
		<input type="hidden" name="intl_countries" value="">
		{* <table class="select-multiple{if empty($country_international_orders) || 1 == $country_international_orders} hidden{/if}"> *}
		<table {if $version >= 1.5}class="select-multiple" {else}style="margin-left:200px;"{/if}>
			<tbody>
				<tr>
					<th width="45%"><i>{l s='bpost@home (Belgium and International)' mod='bpostshm'}</i></th>
					<th>&nbsp;</th>
					<th width="45%">&nbsp;<i>{l s='bpack@bpost International' mod='bpostshm'}</i></th>
				</tr>
				<tr>
					<td>
						<select multiple="multiple" id="intl-list">
						{foreach $product_countries.intl as $iso_code => $_country}
							<option value="{$iso_code|escape}">{$_country|escape}</option>
						{/foreach}
						</select>
					</td>
					{* 
					<td width="50" align="center">
						<img id="add_country" src="{$module_dir|escape}views/img/icons/arrow-right.png" alt="{l s='Add' mod='bpostshm'}" />
						<br />
						<img id="remove_country" src="{$module_dir|escape}views/img/icons/arrow-left.png" alt="{l s='Remove' mod='bpostshm'}" />
					</td>
					*}
					<td>&nbsp;</td>
					<td>
						{* 
						<select name="enabled_country_list[]" multiple="multiple" id="enabled-country-list">
						{foreach $enabled_countries as $iso_code => $_country}
							<option value="{$iso_code|escape}">{$_country|escape}</option>
						{/foreach}
						</select>
						
						<div class="margin-form col-lg-9 col-lg-offset-3"{if $version < 1.5} style="padding:0;font-size:11px;"{/if}>
						*}
						
						{* 
						<div style="color: #7f7f7f;font-size: 0.85em;padding: 0 0 1em 15px;">	
							<p class="preference_description help-block">
								{l s='Please be careful NOT to activate countries in PrestaShop that are not available in your Shipping Manager.' mod='bpostshm'}
							</p>
							<p class="preference_description help-block">
								{l s='Please read more on how to configure PrestaShop zones and countries' mod='bpostshm'}
								<a href="http://bpost.freshdesk.com/support/solutions/articles/4000044096" title="{l s='here' mod='bpostshm'}" target="_blank">
								{l s=' here' mod='bpostshm'}</a>.
							</p>
						</div> 
						*}

						<select multiple="multiple" id="ppi-list">
						{foreach $product_countries.ppi as $iso_code => $_country}
							<option value="{$iso_code|escape}">{$_country|escape}</option>
						{/foreach}
						</select>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<img id="get_countries" src="{$module_dir|escape}views/img/ajax-refresh.gif" alt="{l s='Refresh' mod='bpostshm'}" />
						&nbsp;&nbsp;{* {l s='Refresh left list' mod='bpostshm'} *}{l s='Refresh list' mod='bpostshm'}
						<br><span id="tracie"></span>
					</td>
				</tr>
			</tbody>
		</table>
		</div>
		<br />

{* CN23 instruction set *}
{include file="../blurb/tab_intl_ead-$iso_lang.tpl"}

		<div class="form-group">
			<input type="hidden" name="intl_ead" value="{$intl_ead|escape}">
			<span class="control-label{if $version < 1.6}-bw{/if} col-lg-3">{l s='Shipments outside EU' mod='bpostshm'}</span>
			<div class="margin-form col-lg-9">
				<span class="switch prestashop-switch fixed-width-lg">
					<input type="radio" name="ead_enabled" id="ead_enabled_1" value="1"{if !empty($intl_ead)} checked="checked"{/if} />
					<label for="ead_enabled_1">
						{if $version < 1.6}<img src="{$module_dir|escape}views/img/icons/tick.png" alt="{l s='Yes' mod='bpostshm'}" />{else}{l s='Yes' mod='bpostshm'}{/if}
					</label>
					<input type="radio" name="ead_enabled" id="ead_enabled_0" value="0"{if empty($intl_ead)} checked="checked"{/if} />
					<label for="ead_enabled_0">
						{if $version < 1.6}<img src="{$module_dir|escape}views/img/icons/cross.png" alt="{l s='No' mod='bpostshm'}" />{else}{l s='No' mod='bpostshm'}{/if}
					</label>
					<a class="slide-button btn"></a>
				</span>
			</div>
			<div class="margin-form col-lg-9 col-lg-offset-3">
				{* <p class="preference_description help-block">
					{l s='Option to display the expected delivery date to the client (Belgium only).' mod='bpostshm'}
				</p> *}
			</div>
		</div>
		<div class="clear"></div>

		<div id="ead-deps">
			{* HS Code *}
			<div class="form-group">
				<span class="control-label{if $version < 1.6}-bw{/if} col-lg-3">{l s='HS Code' mod='bpostshm'}</span>
				<div class="margin-form col-lg-9">
				<table {if $version > 1.5}class="tbl-ead"{/if}>
					<tr>
						<td>
							<p class="radio">
								<label for="hscode_opt_feature_1">
									<input type="radio" name="hscode_opt_feature" id="hscode_opt_feature_1" value="1" />
									{l s='Use the following product feature' mod='bpostshm'}
								</label>
							</p>
						</td>
						<td>
							<select name="hscode_select_feature" id="hscode_select_feature">
							{foreach $product_features as $feature}
								<option value="{$feature.id_feature|escape}" {if 1 == $feature.id_feature}selected{/if}>{$feature.name|escape}</option>
							{/foreach}
							</select>
						</td>
					</tr>
					<tr>
						<td>
							<p class="radio">
								<label for="hscode_opt_feature_0">
									<input type="radio" name="hscode_opt_feature" id="hscode_opt_feature_0" value="0" />
									{l s='Apply to all products' mod='bpostshm'}
								</label>
							</p>
						</td>
						<td>
							<input type="text" name="hscode_common" id="hscode_common_code" value="9999">
						</td>
					</tr>
				</table>
				</div>
			</div>
			<div class="clear"></div>
			{* HS Code *}
			<div class="form-group">
				<span class="control-label{if $version < 1.6}-bw{/if} col-lg-3">{l s='Country of Origin' mod='bpostshm'}</span>
				<div class="margin-form col-lg-9">
				<table {if $version > 1.5}class="tbl-ead"{/if}>
					<tr>
						<td>
							<p class="radio">
								<label for="origin_opt_feature_1">
									<input type="radio" name="origin_opt_feature" id="origin_opt_feature_1" value="1" />
									{l s='Use the following product feature' mod='bpostshm'}
								</label>
							</p>
						</td>
						<td>
							<select name="origin_select_feature" id="origin_select_feature">
							{foreach $product_features as $feature}
								<option value="{$feature.id_feature|escape}" {if 1 == $feature.id_feature}selected{/if}>{$feature.name|escape}</option>
							{/foreach}
							</select>
						</td>
					</tr>
					<tr>
						<td>
							<p class="radio">
								<label for="origin_opt_feature_0">
									<input type="radio" name="origin_opt_feature" id="origin_opt_feature_0" value="0" />
									{l s='Apply to all products' mod='bpostshm'}
								</label>
							</p>
						</td>
						<td>
							<select name="origin_select_iso" id="origin_common_code">
							{foreach $list_countries as $country}
								<option value="{$country.iso_code|escape}">{$country.name|escape}</option>
							{/foreach}
							</select>
						</td>
					</tr>
				</table>
				</div>
			</div>
		</div>
		
		<br />
		<div class="margin-form panel-footer">
			{* <button class="button btn btn-default pull-right" type="submit" name="submitCountrySettings"> *}
			<button class="button btn btn-default pull-right" type="submit" name="submitIntlSettings">
				<i class="process-icon-save"></i>
				{l s='Save settings' mod='bpostshm'}
			</button>
		</div>
	</fieldset>
</form>
<br />
