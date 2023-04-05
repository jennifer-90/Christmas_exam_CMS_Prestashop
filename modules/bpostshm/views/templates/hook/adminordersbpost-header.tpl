{*
* 2014-2019 Stigmi
*
* @author Serge <serge@stigmi.eu>
* @copyright 2014-2019 Stigmi
* @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*}

{if isset($panel_debug)}
<div id="panel-debug">
	<div id="aob-debug" class="{if $version < 1.6}toolbarBox toolbarHead infoPanel{else}panel kpi-container{/if} admin-panel-settings">
		{* --- *}
	<div class="heading"><img src="{$module_dir|escape:'htmlall':'UTF-8'}/views/img/logo-carrier.jpg" alt="bpost" /><span>{l s='Debug mode' mod='bpostshm'}</span></div>
	{* <div id="bpost-settings"> *}	
		<form class="form-horizontal{if $version < 1.6} v1-5{/if}" action="#" method="POST" autocomplete="off">
			<fieldset class="panel" id="fs-label-set">
	
		{if isset($debug_cid_synced)}
		<div class="clear"></div>
		<div class="form-group">
			<span class="control-label{if $version < 1.6}-bw{/if} col-lg-3">{l s='Carrier ID Sanity check' mod='bpostshm'}</span>
			<div class="margin-form{if $version > 1.5}-v1-6{/if} col-lg-3">
				<span class="ctrl-info">
				{if empty($debug_cid_synced)}
					<img src="{$module_dir|escape}/views/img/icons/cross.png" />&nbsp;
					<a id="cid-resync" href="#">Resync Carrier IDs</a>
				{else}
					<img src="{$module_dir|escape}/views/img/icons/tick.png" />&nbsp; in sync
				{/if}
				</span>
			</div>
		</div>
		{/if}
		<div class="clear"></div>
		<div class="form-group">
			<span class="control-label{if $version < 1.6}-bw{/if} col-lg-3">{l s='Enable web service log' mod='bpostshm'}</span>
			<div class="margin-form{if $version > 1.5}-v1-6{/if} col-lg-3">
				<span class="ctrl-info">
					<label{if $version < 1.6} class="v15"{/if}>
						<input type="checkbox" name="debug_log_enable" id="debug-log-enable" value="" {if !empty($debug_log_enable)} checked="checked"{/if} />
					</label>
				</span>
				{* <div class="chk-row">
					<label>
						<input type="checkbox" name="debug_log_enable" id="debug-log-enable" value="" {if !empty($debug_log_enable)} checked="checked"{/if} />
						<span>log web service requestsXX</span>
					</label>	
				</div>
				<br /> *}

				{* <span class="switch prestashop-switch fixed-width-lg">
					<input type="radio" name="debug_log_enable" id="debug_log_enable_1" value="1"{if !empty($debug_log_enable)} checked="checked"{/if} />
					<label class="col-lg-3" for="debug_log_enable_1">
						{if $version < 1.6}<img src="{$module_dir|escape}/views/img/icons/tick.png" alt="{l s='Yes' mod='bpostshm'}" />{else}{l s='Yes' mod='bpostshm'}{/if}
					</label>
					<input type="radio" name="debug_log_enable" id="debug_log_enable_0" value="0"{if empty($debug_log_enable)} checked="checked"{/if} />
					<label class="col-lg-3" for="debug_log_enable_0">
						{if $version < 1.6}<img src="{$module_dir|escape}/views/img/icons/cross.png" alt="{l s='No' mod='bpostshm'}" />{else}{l s='No' mod='bpostshm'}{/if}
					</label>
					<a class="slide-button btn"></a>
				</span> *}
			</div>


			{* <div class="margin-form{if $version > 1.5}-v1-6{/if} col-lg-9 col-lg-offset-3">
				<p class="preference_description help-block">
					{l s='DESC: Log WS' mod='bpostshm'}.
				</p>
			</div> *}
		</div>
			</fieldset>
		</form>
		
		{if isset($debug_log)}
		{* <div class="margin-form{if $version > 1.5}-v1-6{/if} col-lg-3">
			<span class="log-action"><a href="#" id="aob-get-log" title="{l s='Click here' mod='bpostshm'}">View</a> log</span>
		</div> *}
		<div class="row{if $version < 1.6} row1-5{/if}">
			<div class="col-sm-4 col-md-4 col-lg-3 sidebar">
				<h4>{$smarty.now|date_format:"%e %b %y"}</h4>
				<div class="dbg-list">
				{foreach from=$debug_log key=idx item=entry}
					<li id="dli-{$idx|intval}"><a href="#">{$entry.time|date_format:"%H:%M:%S"}</a><span{if isset($entry.ecode)} class="dbg-error"{/if}>{$entry.name|escape}</span></li>
				{/foreach}	
				</div>
			</div>
			<div class="col-sm-8 col-md-7 col-lg-8 main{if $version < 1.6} main1-5{/if}">
				<div class="dbg-info">
					<h4 id="iReq"></h4>
					<ul id="uReq">
						<li><span class="title">URL:</span><span id="iUrl"></span></li>
						<li><span class="title">Header:</span><span id="iHeader"></span></li>
						<li><span class="title" id="iQxml"></span></li>
					</ul>
					<h4 id="iRes"></h4>
					<ul>
						<li><span class="title" id="iRxml"></span><span id="iMsg"></span></li>
					</ul>
				</div>
			</div>
		</div>
		{/if}
	{* </div> *}
		{* --- *}
	</div>
</div>
{/if}
{if isset($panel_info)}
<div id="panel-info" class="{if $version < 1.6}toolbarBox toolbarHead infoPanel{else}panel kpi-container{/if}">
	<p>{l s='PARA-1' mod='bpostshm'}</p>
	<p>{l s='PARA-2' mod='bpostshm'}</p>
	<ul>{strip}
		<li>{l s='LISTITEM-1' mod='bpostshm'}</li>
		<li>{l s='LISTITEM-2' mod='bpostshm'}</li>
		<li>{l s='LISTITEM-3' mod='bpostshm'}</li>
	{/strip}</ul>
	<p class="admin-lo">
		<a href="#" id="panel-info-off" title="{l s='Click here' mod='bpostshm'}">
		{l s='Click here' mod='bpostshm'}</a>
		{l s='if you no longer wish to see this message' mod='bpostshm'}.
	</p>
</div>
{/if}