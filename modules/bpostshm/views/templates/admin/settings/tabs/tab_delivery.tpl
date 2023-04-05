{*
* 2014-2021 Stigmi
*
* @author Serge <serge@stigmi.eu>
* @copyright 2014-2021 Stigmi
* @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*}

{* Delivery settings *}
<form class="form-horizontal{if $version < 1.5} v1-4{elseif $version < 1.6} v1-5{/if}" action="#" method="POST" autocomplete="off">
	<fieldset class="panel" id="fs-delivery-set">
	
		<div class="form-group">
			<span class="control-label{if $version < 1.6}-bw{/if} col-lg-3">{l s='Display delivery date' mod='bpostshm'}</span>
			<div class="margin-form col-lg-9">
				<span class="switch prestashop-switch fixed-width-lg">
					<input type="radio" name="display_delivery_date" id="display_delivery_date_1" value="1"{if !empty($display_delivery_date)} checked="checked"{/if} />
					<label for="display_delivery_date_1">
						{if $version < 1.6}<img src="{$module_dir|escape}views/img/icons/tick.png" alt="{l s='Yes' mod='bpostshm'}" />{else}{l s='Yes' mod='bpostshm'}{/if}
					</label>
					<input type="radio" name="display_delivery_date" id="display_delivery_date_0" value="0"{if empty($display_delivery_date)} checked="checked"{/if} />
					<label for="display_delivery_date_0">
						{if $version < 1.6}<img src="{$module_dir|escape}views/img/icons/cross.png" alt="{l s='No' mod='bpostshm'}" />{else}{l s='No' mod='bpostshm'}{/if}
					</label>
					<a class="slide-button btn"></a>
				</span>
			</div>
			<div class="margin-form col-lg-9 col-lg-offset-3">
				<p class="preference_description help-block">
					{l s='Option to display the expected delivery date to the client (Belgium only).' mod='bpostshm'}
				</p>
			</div>
		</div>
		<div class="clear"></div>

		<div id="del-set-deps">
		{if isset($choose_delivery_date)}
			<div class="form-group">
				<span class="control-label{if $version < 1.6}-bw{/if} col-lg-3">{l s='Choose delivery date' mod='bpostshm'}</span>
				<div class="margin-form col-lg-9">
					<span class="switch prestashop-switch fixed-width-lg">
						<input type="radio" name="choose_delivery_date" id="choose_delivery_date_1" value="1"{if !empty($choose_delivery_date)} checked="checked"{/if} />
						<label for="choose_delivery_date_1">
							{if $version < 1.6}<img src="{$module_dir|escape}views/img/icons/tick.png" alt="{l s='Yes' mod='bpostshm'}" />{else}{l s='Yes' mod='bpostshm'}{/if}
						</label>
						<input type="radio" name="choose_delivery_date" id="choose_delivery_date_0" value="0"{if empty($choose_delivery_date)} checked="checked"{/if} />
						<label for="choose_delivery_date_0">
							{if $version < 1.6}<img src="{$module_dir|escape}views/img/icons/cross.png" alt="{l s='No' mod='bpostshm'}" />{else}{l s='No' mod='bpostshm'}{/if}
						</label>
						<a class="slide-button btn"></a>
					</span>
				</div>
				<div class="margin-form col-lg-9 col-lg-offset-3">
					<p class="preference_description help-block">
						{l s='Allow customers to choose their delivery date.' mod='bpostshm'}
					</p>
				</div>
			</div>
			<div class="clear"></div>
			<div id="choose-date-deps">
			{if isset($num_dates_shown)}
				<div class="form-group">
					<span class="control-label{if $version < 1.6}-bw{/if} col-lg-3">{l s='Number of delivery dates shown' mod='bpostshm'}</span>
					<div class="margin-form col-lg-9">
						<input type="text" name="num_dates_shown" id="num-dates" value="{$num_dates_shown|intval}">
					</div>
					<div class="margin-form col-lg-9 col-lg-offset-3">
						<p class="preference_description help-block">
							{l s='Minimum 2, maximum 7' mod='bpostshm'}
						</p>
					</div>
				</div>
				<div class="clear"></div>
			{/if}	
			</div>
		{/if}

		{if isset($ship_delay_days)}
			<div class="form-group">
				<span class="control-label{if $version < 1.6}-bw{/if} col-lg-3">{l s='Days between order and shipment' mod='bpostshm'}</span>
				<div class="margin-form col-lg-9">
					<input type="text" name="ship_delay_days" id="ship-delay" value="{$ship_delay_days|intval}">
				</div>
				<div class="margin-form col-lg-9 col-lg-offset-3">
					<p class="preference_description help-block">
						{l s='Default is 0 (next day delivery), maximum 8' mod='bpostshm'}
					</p>
				</div>
			</div>
			<div class="clear"></div>
		{/if}
		{if isset($cutoff_time)}
			<div class="form-group">
				<span class="control-label{if $version < 1.6}-bw{/if} col-lg-3">{l s='Next day delivery allowed until' mod='bpostshm'}</span>
				<div class="margin-form col-lg-9">
					<input type="text" name="cutoff_time" id="cutoff-time" value="{$cutoff_time|escape}">&nbsp;h
				</div>
			</div>
			<div class="clear"></div>
		{/if}
		{if isset($hide_date_oos)}
			<div class="form-group">
				<span class="control-label{if $version < 1.6}-bw{/if} col-lg-3">{l s='Hide delivery date when out of stock' mod='bpostshm'}</span>
				<div class="margin-form col-lg-9">
					<span class="switch prestashop-switch fixed-width-lg">
						<input type="radio" name="hide_date_oos" id="hide_date_oos_1" value="1"{if !empty($hide_date_oos)} checked="checked"{/if} />
						<label for="hide_date_oos_1">
							{if $version < 1.6}<img src="{$module_dir|escape}views/img/icons/tick.png" alt="{l s='Yes' mod='bpostshm'}" />{else}{l s='Yes' mod='bpostshm'}{/if}
						</label>
						<input type="radio" name="hide_date_oos" id="hide_date_oos_0" value="0"{if empty($hide_date_oos)} checked="checked"{/if} />
						<label for="hide_date_oos_0">
							{if $version < 1.6}<img src="{$module_dir|escape}views/img/icons/cross.png" alt="{l s='No' mod='bpostshm'}" />{else}{l s='No' mod='bpostshm'}{/if}
						</label>
						<a class="slide-button btn"></a>
					</span>
				</div>
				<div class="margin-form col-lg-9 col-lg-offset-3">
					<p class="preference_description help-block">
						{l s='Do not display delivery date when at least one product in the cart, is out of stock.' mod='bpostshm'}
					</p>
				</div>
			</div>
			<div class="clear"></div>
		{/if}
		</div>
		<div class="margin-form panel-footer">
			<button class="button btn btn-default pull-right" type="submit" name="submitDeliverySettings">
				<i class="process-icon-save"></i>
				{l s='Save settings' mod='bpostshm'}
			</button>
		</div>
	</fieldset>
</form>
