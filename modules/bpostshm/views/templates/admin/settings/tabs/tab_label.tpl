{*
* 2014-2021 Stigmi
*
* @author Serge <serge@stigmi.eu>
* @copyright 2014-2021 Stigmi
* @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*}

{* Label settings *}
<form class="form-horizontal{if $version < 1.5} v1-4{elseif $version < 1.6} v1-5{/if}" action="#" method="POST" autocomplete="off">
	<fieldset class="panel" id="fs-label-set">

		<div class="form-group">
			<span class="control-label{if $version < 1.6}-bw{/if} col-lg-3">{l s='Use PrestaShop to manage labels' mod='bpostshm'}</span>
			<div class="margin-form col-lg-9">
				<span class="switch prestashop-switch fixed-width-lg">
					<input type="radio" name="label_use_ps_labels" id="label_use_ps_labels_1" value="1"{if !empty($label_use_ps_labels)} checked="checked"{/if} />
					<label class="col-lg-3" for="label_use_ps_labels_1">
						{if $version < 1.6}<img src="{$module_dir|escape}views/img/icons/tick.png" alt="{l s='Yes' mod='bpostshm'}" />{else}{l s='Yes' mod='bpostshm'}{/if}
					</label>
					<input type="radio" name="label_use_ps_labels" id="label_use_ps_labels_0" value="0"{if empty($label_use_ps_labels)} checked="checked"{/if} />
					<label class="col-lg-3" for="label_use_ps_labels_0">
						{if $version < 1.6}<img src="{$module_dir|escape}views/img/icons/cross.png" alt="{l s='No' mod='bpostshm'}" />{else}{l s='No' mod='bpostshm'}{/if}
					</label>
					<a class="slide-button btn"></a>
				</span>
			</div>
			<div class="margin-form col-lg-9 col-lg-offset-3">
				<p class="preference_description help-block">
					{l s='If you enable this option, labels are generated directly within PrestaShop. It is not needed to use the bpost Shipping Manager for these tasks.' mod='bpostshm'}
					<br />
					{l s='Pop-ups must be enabled in your browser, in order to view the printed labels' mod='bpostshm'}.
					<br />
					<a href="http://bpost.freshdesk.com/support/solutions/articles/4000033755" title="{l s='Click here' mod='bpostshm'}" target="_blank">
					{* <a class="info-link" href="#desc-use-labels" title="{l s='Click here' mod='bpostshm'}">{l s='Click here' mod='bpostshm'}</a> *}
					{l s='Click here' mod='bpostshm'}</a> 
					{l s='to learn more about this option.' mod='bpostshm'}
				</p>
				<p class="preference_description help-block" id="desc-use-labels" style="display:none;">
					{l s='IMPORTANT: description use-labels' mod='bpostshm'}
				</p>
				{if isset($module_cron_info)}
				<p></p>
				<p class="preference_description help-block hammered">
					{$module_cron_info.msg|escape}
					<br />
					{$module_cron_info.url|escape}	
				</p>
				<p></p>
				{/if}
			</div>
		</div>
		{* <div class="clear"></div> *}
		<div id="label-deps">
			{* order display days *}
			{if isset($order_display_days)}
			<div class="clear"></div>
			<div class="form-group">
				{* Days to display orders *}
				<span class="control-label{if $version < 1.6}-bw{/if} col-lg-3">{l s='Days to display orders' mod='bpostshm'}</span>
				<div class="margin-form col-lg-9">
					<input type="text" name="order_display_days" id="order-display-days" value="{$order_display_days|intval}">
				</div>
				<div class="margin-form col-lg-9 col-lg-offset-3">
					<p class="preference_description help-block">
						{l s='Minimum 2, maximum 999' mod='bpostshm'}
					</p>
				</div>
			</div>
			{/if}
			{* Order states *}
			{if isset($order_states)}
			{* Filter display by order states *}
			<div class="clear"></div>
			<div class="form-group">
				<input type="hidden" name="display_order_states" value="">
				<span class="control-label{if $version < 1.6}-bw{/if} col-lg-3">{l s='Filter display by order states' mod='bpostshm'}</span>
				<div class="margin-form col-lg-9">
					{* order state list *}
					<div class="dos-listbox">
					{foreach $order_states as $id_os => $ostate}
						{if $ostate.invoice}
						<div class="dos-row">
							<label>
								<input id="chk_{$id_os|escape}" type="checkbox" name="do_states[]" value="{$id_os|escape}" />
								<span class="status-id">{$id_os|escape}</span>
								<span style="background-color: {$ostate.color|escape}" class="status-name">{$ostate.name|escape}</span>
							</label>
						</div>
						<br />
						{/if}
					{/foreach}
					</div>
				</div>
				<div class="margin-form col-lg-9 col-lg-offset-3">
					<p class="preference_description help-block"  id="dos-desc-block" data-invalid="{l s='ERR: Empty State List' mod='bpostshm'}">
						{l s='DESC: Display order states' mod='bpostshm'}. <span id="dos-msg"></span>
						{* &nbsp;
						<a href="#" title="{l s='Click here' mod='bpostshm'}" target="_blank">
						{l s='Click here' mod='bpostshm'}</a>
						{l s='to learn more about this option.' mod='bpostshm'} *}
					</p>
				</div>
			</div>
			

			{* Treated order state *}
			<div class="clear"></div>
			<div class="form-group">
				<span class="control-label{if $version < 1.6}-bw{/if} col-lg-3">{l s='Treated order state' mod='bpostshm'}</span>
				<div class="margin-form col-lg-9">
					<select name="treated_order_state" id="treated_order_state">
					{foreach $order_states as $id_os => $ostate}
						{if $ostate.invoice}
							<option value="{$id_os|escape}" {if $id_os == $treated_order_state}selected{/if}>{$ostate.name|escape}</option>
						{/if}
					{/foreach}
					</select>
				</div>
				<div class="margin-form col-lg-9 col-lg-offset-3">
					<p class="preference_description help-block">
						{l s='DESC: Treated order state' mod='bpostshm'}.
						{* &nbsp;
						<a href="#" title="{l s='Click here' mod='bpostshm'}" target="_blank">
						{l s='Click here' mod='bpostshm'}</a>
						{l s='to learn more about this option.' mod='bpostshm'} *}
					</p>
				</div>
			</div>
			{/if}
			{* Treat printed order *}
			<div class="clear"></div>
			<div class="form-group">
				<span class="control-label{if $version < 1.6}-bw{/if} col-lg-3">{l s='Mark printed order as treated' mod='bpostshm'}</span>
				<div class="margin-form col-lg-9">
					<span class="switch prestashop-switch fixed-width-lg">
						<input type="radio" name="treat_printed_order" id="treat_printed_order_1" value="1"{if !empty($treat_printed_order)} checked="checked"{/if} />
						<label class="col-lg-3" for="treat_printed_order_1">
							{if $version < 1.6}<img src="{$module_dir|escape}views/img/icons/tick.png" alt="{l s='Yes' mod='bpostshm'}" />{else}{l s='Yes' mod='bpostshm'}{/if}
						</label>
						<input type="radio" name="treat_printed_order" id="treat_printed_order_0" value="0"{if empty($treat_printed_order)} checked="checked"{/if} />
						<label class="col-lg-3" for="treat_printed_order_0">
							{if $version < 1.6}<img src="{$module_dir|escape}views/img/icons/cross.png" alt="{l s='No' mod='bpostshm'}" />{else}{l s='No' mod='bpostshm'}{/if}
						</label>
						<a class="slide-button btn"></a>
					</span>
				</div>
				<div class="margin-form col-lg-9 col-lg-offset-3">
					<p class="preference_description help-block">
						{l s='DESC: Mark printed order as treated' mod='bpostshm'}.
						{* &nbsp;
						<a href="#" title="{l s='Click here' mod='bpostshm'}" target="_blank">
						{l s='Click here' mod='bpostshm'}</a>
						{l s='to learn more about this option.' mod='bpostshm'} *}
					</p>
				</div>
			</div>
			{* Label format *}
			<div class="clear"></div>
			<div class="form-group">
				<span class="control-label{if $version < 1.6}-bw{/if} col-lg-3">{l s='Label format' mod='bpostshm'}</span>
				<div class="margin-form col-lg-9">
					<p class="radio">
						
						<label for="label_pdf_format_A4">
							<input type="radio" name="label_pdf_format" id="label_pdf_format_A4" value="A4"{if empty($label_pdf_format) || 'A4' == $label_pdf_format} checked="checked"{/if} />
							{l s='Default format A4 (PDF)' mod='bpostshm'}
						</label>
					</p>
					<p class="radio">
						
						<label for="label_pdf_format_A6">
							<input type="radio" name="label_pdf_format" id="label_pdf_format_A6" value="A6"{if !empty($label_pdf_format) && 'A6' == $label_pdf_format} checked="checked"{/if} />
							{l s='Default format A6 (PDF)' mod='bpostshm'}
						</label>
					</p>
				</div>
			</div>
			{* Auto Retour *}
			<div class="clear"></div>
			<div class="form-group">
				<span class="control-label{if $version < 1.6}-bw{/if} col-lg-3">{l s='Retour label' mod='bpostshm'}</span>
				<div class="margin-form col-lg-9">
					<span class="switch prestashop-switch fixed-width-lg">
						<input type="radio" name="auto_retour_label" id="auto_retour_label_1" value="1"{if !empty($auto_retour_label)} checked="checked"{/if} />
						<label class="col-lg-3" for="auto_retour_label_1">
							{if $version < 1.6}<img src="{$module_dir|escape}views/img/icons/tick.png" alt="{l s='Yes' mod='bpostshm'}" />{else}{l s='Yes' mod='bpostshm'}{/if}
						</label>
						<input type="radio" name="auto_retour_label" id="auto_retour_label_0" value="0"{if empty($auto_retour_label)} checked="checked"{/if} />
						<label class="col-lg-3" for="auto_retour_label_0">
							{if $version < 1.6}<img src="{$module_dir|escape}views/img/icons/cross.png" alt="{l s='No' mod='bpostshm'}" />{else}{l s='No' mod='bpostshm'}{/if}
						</label>
						<a class="slide-button btn"></a>
					</span>
				</div>
				<div class="margin-form col-lg-9 col-lg-offset-3">
					<p class="preference_description help-block">
						{l s='If you enable this option, a retour label is automatically added and printed when generating labels. If disabled, you are able to manually create retour labels.' mod='bpostshm'}
						<a href="http://bpost.freshdesk.com/support/solutions/articles/4000033756" title="{l s='Click here' mod='bpostshm'}" target="_blank">
						{* <a class="info-link" href="#desc-retour-label" title="{l s='Click here' mod='bpostshm'}"> *}
						{l s='Click here' mod='bpostshm'}</a>
						{l s='to learn more about this option.' mod='bpostshm'}
					</p>
					<p class="preference_description help-block" id="desc-retour-label" style="display:none;">
						{l s='IMPORTANT: description retour-label' mod='bpostshm'}
					</p>
				</div>
			</div>
			{* T & T integration *}
			<div class="clear"></div>
			<div class="form-group">
				<span class="control-label{if $version < 1.6}-bw{/if} col-lg-3">{l s='Track & Trace integration' mod='bpostshm'}</span>
				<div class="margin-form col-lg-9">
					<span class="switch prestashop-switch fixed-width-lg">
						<input type="radio" name="label_tt_integration" id="label_tt_integration_1" value="1"{if !empty($label_tt_integration)} checked="checked"{/if} />
						<label class="col-lg-3" for="label_tt_integration_1">
							{if $version < 1.6}<img src="{$module_dir|escape}views/img/icons/tick.png" alt="{l s='Yes' mod='bpostshm'}" />{else}{l s='Yes' mod='bpostshm'}{/if}
						</label>
						<input type="radio" name="label_tt_integration" id="label_tt_integration_0" value="0"{if empty($label_tt_integration)} checked="checked"{/if} />
						<label class="col-lg-3" for="label_tt_integration_0">
							{if $version < 1.6}<img src="{$module_dir|escape}views/img/icons/cross.png" alt="{l s='No' mod='bpostshm'}" />{else}{l s='No' mod='bpostshm'}{/if}
						</label>
						<a class="slide-button btn"></a>
					</span>
				</div>
				<div class="margin-form col-lg-9 col-lg-offset-3">
					<p class="preference_description help-block">
						{l s='If you enable this option, an email containing Track & Trace information is automatically sent to customers when generating labels.' mod='bpostshm'}
						<a href="http://bpost.freshdesk.com/support/solutions/articles/4000033757" title="{l s='Click here' mod='bpostshm'}" target="_blank">
						{* <a class="info-link" href="#desc-tt-email" title="{l s='Click here' mod='bpostshm'}"> *}
						{l s='Click here' mod='bpostshm'}</a> 
						{l s='to learn more about this option.' mod='bpostshm'}
					</p>
					<p class="preference_description help-block" id="desc-tt-email" style="display:none;">
						{l s='IMPORTANT: description tt-email' mod='bpostshm'}
					</p>
				</div>
			</div>
			{* Auto update T&T *}
			{* 
			<div class="clear"></div>
			<div class="form-group">
				<span class="control-label{if $version < 1.6}-bw{/if} col-lg-3">{l s='Other settings' mod='bpostshm'}</span>
				<div class="margin-form col-lg-9">



					{l s='Update T&T status of treated orders every' mod='bpostshm'}
					<select name="label_tt_frequency" class="fixed-width-xs">
						{for $i=1 to 4 nocache}
							<option value="{$i|escape}"{if !empty($label_tt_frequency) && $i == $label_tt_frequency} selected="selected"{/if}>{$i|escape}&nbsp;</option>
						{/for}
					</select> {l s='hour(s)' mod='bpostshm'}
					<div class="clear"></div>



					<p class="checkbox">
						<label for="label_tt_update_on_open">
							<input type="checkbox" name="label_tt_update_on_open" id="label_tt_update_on_open" style="margin-right:2px;" 
							value="1"{if !empty($label_tt_update_on_open)} checked="checked"{/if} />
							{l s='Update T&T status of treated orders automatically when opening orders.' mod='bpostshm'}
						</label>
					</p>
				</div>
			</div>
			*}
			{* Debug mode options (experimental) *}
			{* 
			{if (isset($smarty.get.debug) && $smarty.get.debug) || (isset($debug_log_enable) && $debug_log_enable)}
			<hr style="border: #ccc solid 1px" />
			<div class="clear"></div>
			<div class="form-group">
				<span class="control-label{if $version < 1.6}-bw{/if} col-lg-3">{l s='Enable web service log' mod='bpostshm'}</span>
				<div class="margin-form col-lg-9">
					<div class="chk-row">
						<label>
							<input type="checkbox" name="debug_log_enable" id="debug-requests" value="" {if !empty($debug_log_enable)} checked="checked"{/if} />
							<span>log web service requests</span>
						</label>	
					</div>
					<br />

					<span class="switch prestashop-switch fixed-width-lg">
						<input type="radio" name="debug_log_enable" id="debug_log_enable_1" value="1"{if !empty($debug_log_enable)} checked="checked"{/if} />
						<label class="col-lg-3" for="debug_log_enable_1">
							{if $version < 1.6}<img src="{$module_dir|escape}views/img/icons/tick.png" alt="{l s='Yes' mod='bpostshm'}" />{else}{l s='Yes' mod='bpostshm'}{/if}
						</label>
						<input type="radio" name="debug_log_enable" id="debug_log_enable_0" value="0"{if empty($debug_log_enable)} checked="checked"{/if} />
						<label class="col-lg-3" for="debug_log_enable_0">
							{if $version < 1.6}<img src="{$module_dir|escape}views/img/icons/cross.png" alt="{l s='No' mod='bpostshm'}" />{else}{l s='No' mod='bpostshm'}{/if}
						</label>
						<a class="slide-button btn"></a>
					</span>
				</div>
				<div class="margin-form col-lg-9 col-lg-offset-3">
					<p class="preference_description help-block">
						{l s='DESC: Log WS' mod='bpostshm'}.
						&nbsp;
						<a href="#" title="{l s='Click here' mod='bpostshm'}" target="_blank">
						{l s='Click here' mod='bpostshm'}</a>
						{l s='to learn more about this option.' mod='bpostshm'}
					</p>
				</div>
			</div>
			{/if}
			*}

		</div>
		<div class="margin-form panel-footer">
			<button class="button btn btn-default pull-right" type="submit" name="submitLabelSettings">
				<i class="process-icon-save"></i>
				{l s='Save settings' mod='bpostshm'}
			</button>
		</div>
	</fieldset>
</form>
