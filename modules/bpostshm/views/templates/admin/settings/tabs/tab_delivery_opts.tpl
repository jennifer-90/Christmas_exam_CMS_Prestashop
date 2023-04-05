{*
* 2014-2021 Stigmi
*
* @author Serge <serge@stigmi.eu>
* @copyright 2014-2021 Stigmi
* @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*}

{* Delivery options *}
<div class="del-opt-tpl" style="display:none;">
	<span class="dex"> from </span>
	<input type="text" class="currency">
	<img src="{$module_dir|escape}views/img/icons/information.png" style="margin:2px 6px;">
</div>
<form class="form-horizontal{if $version < 1.5} v1-4{elseif $version < 1.6} v1-5{/if}" action="#" method="POST" autocomplete="off">
	<fieldset class="panel" id="fs-delopts">
		<div id="delivery-options" class="form-group">
		{* content start *}
		<input type="hidden" name="delivery_options_list" value="">
		{* 
		<ul class="delopt-tabs">
		{foreach $delivery_options as $dm => $options}
			<li class="delopt-tab-row"><a href="#dm-{$dm|intval}">{l s=$options['title'] mod='bpostshm'}</a></li> 
		{/foreach}
		</ul>
		*}
		{foreach $delivery_options as $dm => $options}
			<div id="dm-{$dm|intval}">
				<span class="control-label{if $version < 1.6}-bw{/if} col-lg-3">{l s=$options['title'] mod='bpostshm'}</span>
			{* Srg >> 5-apr-17: temporary fix, while waiting for full spec on bpostshm v1.5+ *}
			{if 1 === $dm && $enabled_business24}
				<div class="margin-form col-lg-9">
					<p class="radio">
						<label for="national_delivery_0">
							<input type="radio" name="home_24h_business" id="national_delivery_0" value="0"{if empty($home_24h_business)} checked="checked"{/if} />
							{l s='24h Pro' mod='bpostshm'}
						</label>
						<br />
						<label for="national_delivery_1">
							<input type="radio" name="home_24h_business" id="national_delivery_1" value="1"{if !empty($home_24h_business)} checked="checked"{/if} />
							{l s='24h Business' mod='bpostshm'}
						</label>
					</p>
					<p class="preference_description help-block">
						{*l s='Choose national delivery option.' mod='bpostshm'*}
						{l s='Which product should I use' mod='bpostshm'}
						<a href="http://bpost.freshdesk.com/solution/articles/4000102198-bpack-24h-pro-vs-bpack-24h-business" title="{l s='Click here' mod='bpostshm'}" target="_blank">{l s='MORE here' mod='bpostshm'}</a>
						<br />
						<br />
					</p>
				</div>
			{elseif 9 === $dm}
				<div class="margin-form col-lg-9">
					<p class="radio">
						<label for="international_delivery_0">
							<input type="radio" name="display_international_delivery" id="international_delivery_0" value="0"{if empty($display_international_delivery)} checked="checked"{/if} />
							{l s='World Express Pro' mod='bpostshm'}
						</label>
						<br />
						<label for="international_delivery_1">
							<input type="radio" name="display_international_delivery" id="international_delivery_1" value="1"{if !empty($display_international_delivery)} checked="checked"{/if} />
							{l s='World Business' mod='bpostshm'}
						</label>
					</p>
					<p class="preference_description help-block">
						{l s='Choose international delivery option.' mod='bpostshm'}
					</p>
				</div>
				<br />
			{/if}
				<div class="margin-form col-lg-9 col-lg-offset-3">
			{* Srg << *}
				<table>
				{foreach $options['opts'] as $key => $opt}
				{assign var="opt_id" value="$dm-$key"}
					<tr>
						<td class="checkbox">
							<label {if $version >= 1.6}style="margin-right:5px;"{/if}>
								<input type="checkbox" class="del-opt" data-id="{$opt_id|escape}" from="{$opt['from']|escape}"{if isset($opt['cost'])} cost="{$opt['cost']|escape}"{/if} {if $opt['checked']}checked="checked"{/if} />
								&nbsp;{$delivery_options_info[$key]['title']|escape}
							</label>
						</td>
					</tr>
				{/foreach}	
				</table>
				</div>
				<div class="margin-form col-lg-9 col-lg-offset-3">
					<p class="preference_description help-block">
					{foreach $options['opts'] as $key => $opt}
						<b>{$delivery_options_info[$key]['title']|escape}</b>: {$delivery_options_info[$key]['info']|escape}
						<br />
					{/foreach}
					</p>
				</div>
			</div>	 		
		{/foreach}
		{* content end *}
			<div class="margin-form col-lg-9 col-lg-offset-3">
				<p class="preference_description help-block">
					<br />
					{l s='Please note the following' mod='bpostshm'}&nbsp;
					<a href="http://bpost.freshdesk.com/support/solutions/articles/4000036819-configuring-delivery-options" title="{l s='Click here' mod='bpostshm'}" target="_blank">
					{* <a class="info-link" href="#desc-del-options" title="{l s='Click here' mod='bpostshm'}"> *}
					{l s='important information' mod='bpostshm'}</a>.
				</p>
				{* <p class="preference_description help-block" id="desc-del-options" style="display:none;">
					{l s='IMPORTANT: description del-options' mod='bpostshm'}
				</p> *}
			</div>
		</div>
		<div class="clear"></div>
		<div class="margin-form panel-footer">
			<button class="button btn btn-default pull-right" type="submit" name="submitDeliveryOptions">
				<i class="process-icon-save"></i>
				{l s='Save settings' mod='bpostshm'}
			</button>
		</div>
	</fieldset>
</form>	
