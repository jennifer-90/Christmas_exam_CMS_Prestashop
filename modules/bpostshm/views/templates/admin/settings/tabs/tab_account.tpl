{*
* 2014-2021 Stigmi
*
* @author Serge <serge@stigmi.eu>
* @copyright 2014-2021 Stigmi
* @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*}

{* Account settings *}
<form class="form-horizontal{if $version < 1.5} v1-4{elseif $version < 1.6} v1-5{/if}" action="#" method="POST" autocomplete="off">
	<fieldset class="panel" id="fs-account">

{* Instruction set *}
{include file="../blurb/tab_acc-$iso_lang.tpl"}

		<div class="form-group">
			<label class="control-label{if $version < 1.6}-bw{/if} col-lg-3" for="account_id_account">{l s='Account ID' mod='bpostshm'}</label>
			<div class="margin-form col-lg-9">
				<input type="text" name="account_id_account" id="account_id_account" value="{$account_id_account|escape}" size="50" />
			</div>
			<div class="margin-form col-lg-9 col-lg-offset-3">
				<p class="preference_description help-block">
					{l s='Your 6 digits bpost account ID used for the Shipping Manager' mod='bpostshm'}
				</p>
			</div>
		</div>
		<div class="clear"></div>
		<div class="form-group">
			<label class="control-label{if $version < 1.6}-bw{/if} col-lg-3" for="account_passphrase">{l s='Passphrase' mod='bpostshm'}</label>
			<div class="margin-form col-lg-9">
				<input type="text" name="account_passphrase" id="account_passphrase" value="{$account_passphrase|escape}" size="50" />
			</div>
			<div class="margin-form col-lg-9 col-lg-offset-3">
				<p class="preference_description help-block">
					{l s='The passphrase you entered in bpost Shipping Manager back-office application. This is not the password used to access bpost portal.' mod='bpostshm'}
				</p>
			</div>
		</div>
		<div class="clear"></div>
		{* Srg: 28-aug-2018 (no longer a setting) *}
		{* 
		<div class="form-group">
			<label class="control-label{if $version < 1.6}-bw{/if} col-lg-3" for="account_api_url">{l s='API URL' mod='bpostshm'}</label>
			<div class="margin-form col-lg-9">
				<input type="text" name="account_api_url" id="account_api_url" value="{$account_api_url|escape}" size="50" />
			</div>
			<div class="margin-form col-lg-9 col-lg-offset-3">
				<p class="preference_description help-block">
				{if $is_v161 || $version < 1.5}
					{l s='Do not modify this setting if you are not 100% sure of what you are doing' mod='bpostshm'}
				{else}	
					{l s='Do not modify this setting if you are not 100%% sure of what you are doing' mod='bpostshm'}
				{/if}
				</p>
			</div>
		</div>
		<div class="clear"></div>
		*}
		{* Gmaps API key *}
		<div class="form-group">
			<label class="control-label{if $version < 1.6}-bw{/if} col-lg-3" for="gmaps_api_key">{l s='Gmaps API key' mod='bpostshm'}</label>
			<div class="margin-form col-lg-9">
				{* <input type="text" name="gmaps_api_key" id="gmaps_api_key" value="{$gmaps_api_key|escape}" size="50" required="required" placeholder="{l s='This field cannot be empty' mod='bpostshm'}" /><sup>*</sup> *}
				<input type="text" name="gmaps_api_key" id="gmaps_api_key" value="{$gmaps_api_key|escape}" size="50" />
			</div>
			<div class="margin-form col-lg-9 col-lg-offset-3">
				<p class="preference_description help-block">
					{l s='Your personal Google maps API key' mod='bpostshm'}.
				</p>
			</div>
		</div>
		<div class="clear"></div>
	{* Store details *}
	<input type="hidden" name="store_details" value="">
	{if isset($store_details_info)}
		{foreach $store_details_info as $key => $details}
		{assign var="sd_id" value="sd-$key"}	
			<div class="form-group">
				<label class="control-label{if $version < 1.6}-bw{/if} col-lg-3" for="{$sd_id|escape}">{$details.title|escape}</label>
				<div class="margin-form col-lg-9">
				{if empty($details.max)}
					<select name="{$sd_id|escape}" id="{$sd_id|escape}">
						<option value="BE" selected>{$details.value|escape}</option>
					</select>
				{else}
				{capture "input_msg"}
					{if isset($details.invalid)}
						"{$details.invalid|escape}"
					{else}
						"{l s='This field cannot be empty' mod='bpostshm'}"
					{/if}
				{/capture}
					{* {if isset($details.invalid)}{assign var="inp_msg", value=$details.invalid}{else}{assign}{/if} *}
					{* {assign var="inp_msg", value="{if isset($details.invalid)}{$details.invalid|escape}{else}{l s='This field cannot be empty' mod='bpostshm'}{/if}"} *}
					<input type="text" name="{$sd_id|escape}" id="{$sd_id|escape}" value="{$details.value|escape}" data-sdkey="{$key|escape}" size="50" maxlength="{$details.max|intval}" {if $details.required}required="required" placeholder={$smarty.capture.input_msg} data-invalid={$smarty.capture.input_msg} {if isset($details.pattern)}data-pattern="{$details.pattern|escape:'javascript' nofilter}"{/if}{/if} />
					{if $details.required}<sup>*</sup>{/if}
				{/if}
				</div>
				{if isset($details.description)}
				<div class="margin-form col-lg-9 col-lg-offset-3">
					<p class="preference_description help-block">
						{$details.description|escape}
					</p>
				</div>
				{/if}
			</div>
			<div class="clear"></div>
		{/foreach}
	{/if}
		<div class="margin-form panel-footer">
			<button class="button btn btn-default pull-right" type="submit" name="submitAccountSettings">
				<i class="process-icon-save"></i>
				{l s='Save settings' mod='bpostshm'}
			</button>
		</div>
	</fieldset>
</form>
