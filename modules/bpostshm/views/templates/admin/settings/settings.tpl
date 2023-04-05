{*
* 2014 Stigmi
*
* @author Stigmi.eu <www.stigmi.eu>
* @copyright 2014 Stigmi
* @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*}

<div id="bpost-settings">
	<h2><img src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/logo-carrier.jpg" alt="bpost" /> {l s='bpost Shipping manager' mod='bpostshm'}</h2>
	<br />
	{if !empty($errors)}
		{if $version >= 1.6}
			{if (!isset($disableDefaultErrorOutPut) || $disableDefaultErrorOutPut == false)}
				<div class="bootstrap">
					<div class="alert alert-danger">
						<button type="button" class="close" data-dismiss="alert">&times;</button>
						{if count($errors) > 1}
							{l s='%d errors' sprintf=$errors|count mod='bpostshm'}
							<br/>
						{/if}
						<ol>
							{foreach $errors as $error}
								<li>{$error|escape}</li>
							{/foreach}
						</ol>
					</div>
				</div>
			{/if}
		{else}
			<div class="error">
				<ul>{strip}
						{foreach $errors as $error}
							<li>{$error|escape}</li>
						{/foreach}
					{/strip}</ul>
			</div>
		{/if}
		<br />
	{/if}
	{if $version < 1.6}<legend class="tab-wrapper">{/if}
	<ul class="bpost-tabs">
		{* 
		<li>
			<a href="#fs-description">
				{l s='Description' mod='bpostshm'}
			</a>
		</li>
		*}
		<li>
			<a href="#fs-account">
				{l s='Account settings' mod='bpostshm'}
			</a>
		</li>
		{if (isset($valid_account) && $valid_account)}
		<li>
			<a href="#fs-delopts">
				{l s='Delivery options' mod='bpostshm'}
			</a>
		</li>
		<li>
			<a href="#fs-delivery-set">
				{l s='Delivery settings' mod='bpostshm'}
			</a>
		</li>
		<li>
			<a href="#fs-intl-set">
				{l s='International settings' mod='bpostshm'}
			</a>
		</li>
		<li>
			<a href="#fs-label-set">
				{l s='Label settings' mod='bpostshm'}
			</a>
		</li>
		{/if}
	</ul>
	{if $version < 1.6}</legend>{/if}
	{* 
	<fieldset class="panel" id="fs-description">
		<div class="panel-body">
			<p>{l s='bpost Shipping Manager is a service offered by bpost, allowing your customer to chose their preferred delivery method when ordering in your webshop.' mod='bpostshm'}</p>
			<p>{l s='The following delivery methods are currently supported:' mod='bpostshm'}</p>
			<ul>{strip}
				<li>{l s='Delivery at home or at the office' mod='bpostshm'}</li>
				<li>{l s='Delivery in a pick-up point or postal office' mod='bpostshm'}</li>
				<li>{l s='Delivery in a parcel locker' mod='bpostshm'}</li>
			{/strip}</ul>
			<p>{l s='When activated and correctly installed, this module also allows you to completely integrate the bpost administration into your webshop. This means that orders are automatically added to the bpost portal. Furthermore, if enabled, it is possible to generate your labels and tracking codes directly from the Prestashop order admin page.' mod='bpostshm'}
				<br />{l s='No more hassle and 100% transparent!' mod='bpostshm'}
			</p>
			<p>
				<a href="{l s='http://bpost.freshdesk.com/support/solutions/folders/208531' mod='bpostshm'}" title="{l s='Documentation' mod='bpostshm'}" target="_blank">
					<img src="{$module_dir|escape}views/img/icons/information.png" alt="{l s='Documentation' mod='bpostshm'}" />{l s='Documentation' mod='bpostshm'}
				</a>
			</p>
		</div>
	</fieldset>
	*}

	{* Account settings *}
	{include file="./tabs/tab_account.tpl"}
	
	{if (isset($valid_account) && $valid_account)}
		{* Delivery options *}
		{include file="./tabs/tab_delivery_opts.tpl"}

		{* Delivery settings *}
		{if isset($display_delivery_date)}
			{include file="./tabs/tab_delivery.tpl"}
		{/if}
		
		{* Label settings *}
		{if isset($label_use_ps_labels)}
			{include file="./tabs/tab_label.tpl"}
		{/if}
		
		{* International settings *}
		{if empty($errors)}
			{include file="./tabs/tab_intl.tpl"}
		{/if}
	{/if}
</div>
<script type="text/javascript">
// <![CDATA[
var sas = {
		lastTab: {$last_set_tab|intval},
		contentText: {
			tipFrom: "{l s='Minimum purchase total required in order to trigger the option excluding taxes & shipping costs' mod='bpostshm' js=1}",
			tipCost: "{l s='added shipping costs' mod='bpostshm' js=1}",
			errors: {
				retrieveList: "{l s='Unable to retrieve the list. Please try again later.' mod='bpostshm' js=1}",
				emptyField: "{l s='This field cannot be empty' mod='bpostshm' js=1}",
				gmapsBadKey: "{l s='Invalid Gmaps key' mod='bpostshm' js=1}",
			}
		},
		urls: {
			enabledCountries: "{$url_get_enabled_countries|escape:'javascript'}",
			testGmaps: "{$url_test_gmaps|escape:'javascript'}",
		},
	{if (isset($valid_account) && $valid_account)}		
		home24hBusiness: {$home_24h_business|intval},
		intlEnabled: {$product_countries|json_encode nofilter},
		def_cutoff: '{$cutoff_time|escape}',
		delopt_args: {
			tf: "{l s='as from' mod='bpostshm' js=1}",
			tc: "{l s='with additional cost of' mod='bpostshm' js=1}",
			disables: { "1-350": ["1-300"] },
			inputStyle: 'width: {if $version > 1.5}75{else}65{/if}px; padding-left: 4px; margin-left: 4px;',
		},
		defOrderStates: {$display_order_states|json_encode nofilter},
		daysDisplayedLimits: {
			min: 2,
			max: 999,
			def: {$order_display_days|intval},
		},
	{/if}		
};
// ]]>
</script>
<script type="text/javascript" src="{$settings_src|escape:'javascript'}"></script>
