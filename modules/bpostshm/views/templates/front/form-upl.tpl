{*
* 2017 Stigmi
*
* @author Serge <Stigmi.eu>
* @copyright 2017 Stigmi
* @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*}
{if $version >= 1.7}{include "{$bpost_tpl_dir|escape}inc17.tpl"}{/if}
{if !empty($step)}
<div id="service-point" class="at-247{if $version >= 1.5 && $version < 1.6} v1-5{/if}">	
{if 1 == $step|intval}
	<div class="clearfix">
		<h1 class="col-xs-12"{if $version < 1.5} style="line-height: 4.6em;"{/if}>
			<span class="step">1</span>
			{l s='Parcel locker selection process' mod='bpostshm'}
			<img class="loader" src="{$module_dir|escape}views/img/ajax-loader.gif" alt="{l s='Loading...' mod='bpostshm'}" />
		</h1>
		<p class="col-xs-12" style="padding: 15px 0 15px 15px;">
			<a href="{l s='parcel locker info link' mod='bpostshm'}" target="_blank">{l s='Click here' mod='bpostshm'}</a>
			{l s='for more information on the parcel locker delivery method' mod='bpostshm'}.
		</p>
	</div>
	{if isset($upl_info)}
	<div id="unregister" class="clearfix">		
		<form class="col-xs-6 center" action="{$url_post_upl_unregister|escape nofilter}" id="upl-unregister" method="POST" autocomplete="off" novalidate="novalidate">
			<div class="row clearfix">
				<label for="email">{l s='E-mail' mod='bpostshm'}</label>
				<input type="text" name="eml" id="eml" value="" required="required" />
				<sup>*</sup>
			</div>
			<div class="row clearfix">
				<label for="mobile-number">
					{l s='Mobile phone' mod='bpostshm'}
					<img class="info" src="{$module_dir|escape}views/img/icons/information.png" data-tip="mobile">
				</label>
				<input type="text" name="mob" id="mob" value="" />
			</div>
			<div class="row clearfix">
				<label for="rmz">
					{l s='Reduced mobility zone' mod='bpostshm'}
					<img class="info" src="{$module_dir|escape}views/img/icons/information.png" data-tip="mobi-zone">
				</label>
				<input type="checkbox" name="rmz" id="rmz" value="1" {if $upl_info['rmz']}checked="checked"{/if} />
			</div>
			<div class="row last">
				<br /><br />
				<input type="submit" class="button" value="{l s='Proceed' mod='bpostshm'}" />
			</div>
			<div class="row clearfix legend">
				<label>
					* {l s='required' mod='bpostshm'}
				</label>
			</div>
		</form>
	</div>
	{/if}
<script type="text/javascript">
// <![CDATA[
var soupl = {
		version: {$version|floatval},
		upl_info: {$upl_info|json_encode nofilter},
		url_get_list: "{$url_get_point_list|escape:'javascript' nofilter}",
		msg: {
			tip_mobile: "{l s='Mobile number info' mod='bpostshm' js=1}",
			tip_rmz: "{l s='Reduced mobility zone info' mod='bpostshm' js=1}",
			err_field: "{l s='Incorrect format' mod='bpostshm' js=1}",
			err_reg: "{l s='Registration failed.' mod='bpostshm'}",
		},
	};
// ]]>
</script>
<script type="text/javascript" src="{$module_dir|escape:'javascript':'UTF-8'}views/js/eon.jquery.upl.min.js"></script>
{/if}
</div>
{/if}
