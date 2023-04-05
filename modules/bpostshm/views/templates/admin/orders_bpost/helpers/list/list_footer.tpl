{*
* 2014 Stigmi
*
* @author Stigmi.eu <www.stigmi.eu>
* @copyright 2014 Stigmi
* @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*}

{if $version >= 1.5 && $version < 1.6}
				</table>
				{if $bulk_actions}
					<p id="bulky">
						{foreach $bulk_actions as $key => $params}
							<input type="submit" class="button" name="submitBulk{$key|escape:'htmlall':'UTF-8'}{$table|escape:'htmlall':'UTF-8'}" value="{$params.text|escape:'htmlall':'UTF-8'}" {if isset($params.confirm)}onclick="return confirm('{$params.confirm|escape:'htmlall':'UTF-8'}');"{/if} />
						{/foreach}
					</p>
				{/if}
			</td>
		</tr>
	</table>
	{if !$simple_header}
		<input type="hidden" name="token" value="{$token|escape:'htmlall':'UTF-8'}" />
		</form>
	{/if}

	{hook h='displayAdminListAfter'}
	{if isset($name_controller)}
		{capture name=hookName assign=hookName}display{$name_controller|ucfirst}ListAfter{/capture}
		{hook h=$hookName}
	{elseif isset($smarty.get.controller)}
		{capture name=hookName assign=hookName}display{$smarty.get.controller|ucfirst|htmlentities}ListAfter{/capture}
		{hook h=$hookName}
	{/if}

	{block name="after"}{/block}
{elseif $version >= 1.6}
		</table>
	</div>
	<div class="row">
		<div class="col-lg-6">
			{if $bulk_actions && $has_bulk_actions}
			<div class="btn-group bulk-actions">
				<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
					{l s='Bulk actions' mod='bpostshm'} <span class="caret"></span>
				</button>
				<ul class="dropdown-menu">
					<li>
						<a href="#" onclick="javascript:checkDelBoxes($(this).closest('form').get(0), '{$list_id|escape:'htmlall':'UTF-8'}Box[]', true);return false;">
							<i class="icon-check-sign"></i>&nbsp;{l s='Select all' mod='bpostshm'}
						</a>
					</li>
					<li>
						<a href="#" onclick="javascript:checkDelBoxes($(this).closest('form').get(0), '{$list_id|escape:'htmlall':'UTF-8'}Box[]', false);return false;">
							<i class="icon-check-empty"></i>&nbsp;{l s='Unselect all' mod='bpostshm'}
						</a>
					</li>
					<li class="divider"></li>
					{foreach $bulk_actions as $key => $params}
						<li{if $params.text == 'divider'} class="divider"{/if}>
							{if $params.text != 'divider'}
							<a href="#" onclick="{if isset($params.confirm)}if (confirm('{$params.confirm|escape:'quotes':'UTF-8'}')){/if}sendBulkAction($(this).closest('form').get(0), 'submitBulk{$key|escape:'htmlall':'UTF-8'}{$table|escape:'htmlall':'UTF-8'}');">
								{if isset($params.icon)}<i class="{$params.icon|escape:'htmlall':'UTF-8'}"></i>{/if}&nbsp;{$params.text|escape:'quotes':'UTF-8'}
							</a>
							{/if}
						</li>
					{/foreach}
				</ul>
			</div>
			{/if}
		</div>
		{if !$simple_header && $list_total > $pagination[0]}
		<div class="col-lg-6">
			{* Choose number of results per page *}
			<div class="pagination">
				{l s='Display' mod='bpostshm'}
				<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
					{$selected_pagination|escape:'htmlall':'UTF-8'}
					<i class="icon-caret-down"></i>
				</button>
				<ul class="dropdown-menu">
				{foreach $pagination AS $value}
					<li>
						<a href="javascript:void(0);" class="pagination-items-page" data-items="{$value|intval}" data-list-id="{$list_id|escape:'htmlall':'UTF-8'}">{$value|escape:'htmlall':'UTF-8'}</a>
					</li>
				{/foreach}
				</ul>
				/ {$list_total|intval} {l s='result(s)' mod='bpostshm'}
				<input type="hidden" id="{$list_id|escape:'htmlall':'UTF-8'}-pagination-items-page" name="{$list_id|escape:'htmlall':'UTF-8'}_pagination" value="{$selected_pagination|intval}" />
			</div>
			<script type="text/javascript">
				$('.pagination-items-page').on('click',function(e){
					e.preventDefault();
					$('#'+$(this).data("list-id")+'-pagination-items-page').val($(this).data("items")).closest("form").submit();
				});
			</script>
			<ul class="pagination pull-right">
				<li {if $page <= 1}class="disabled"{/if}>
					<a href="javascript:void(0);" class="pagination-link" data-page="1" data-list-id="{$list_id|escape:'htmlall':'UTF-8'}">
						<i class="icon-double-angle-left"></i>
					</a>
				</li>
				<li {if $page <= 1}class="disabled"{/if}>
					<a href="javascript:void(0);" class="pagination-link" data-page="{$page|intval - 1}" data-list-id="{$list_id|escape:'htmlall':'UTF-8'}">
						<i class="icon-angle-left"></i>
					</a>
				</li>
				{assign p 0}
				{while $p++ < $total_pages}
					{if $p < $page-2}
						<li class="disabled">
							<a href="javascript:void(0);">&hellip;</a>
						</li>
						{assign var='p' value=$page-3}
					{elseif $p > $page+2}
						<li class="disabled">
							<a href="javascript:void(0);">&hellip;</a>
						</li>
						{assign p $total_pages}
					{else}
						<li {if $p == $page}class="active"{/if}>
							<a href="javascript:void(0);" class="pagination-link" data-page="{$p|intval}" data-list-id="{$list_id|escape:'htmlall':'UTF-8'}">{$p|intval}</a>
						</li>
					{/if}
				{/while}
				<li {if $page >= $total_pages}class="disabled"{/if}>
					<a href="javascript:void(0);" class="pagination-link" data-page="{$page|intval + 1}" data-list-id="{$list_id|escape:'htmlall':'UTF-8'}">
						<i class="icon-angle-right"></i>
					</a>
				</li>
				<li {if $page >= $total_pages}class="disabled"{/if}>
					<a href="javascript:void(0);" class="pagination-link" data-page="{$total_pages|intval}" data-list-id="{$list_id|escape:'htmlall':'UTF-8'}">
						<i class="icon-double-angle-right"></i>
					</a>
				</li>
			</ul>
			<script type="text/javascript">
				$('.pagination-link').on('click',function(e){
					e.preventDefault();

					if (!$(this).parent().hasClass('disabled'))
						$('#submitFilter'+$(this).data("list-id")).val($(this).data("page")).closest("form").submit();
				});
			</script>
		</div>
		{/if}
	</div>
	{block name="footer"}
	{foreach from=$toolbar_btn item=btn key=k}
		{if $k == 'back'}
			{assign 'back_button' $btn}
			{break}
		{/if}
	{/foreach}
	{if isset($back_button)}
	<div class="panel-footer">
		<a id="desc-{$table|escape:'htmlall':'UTF-8'}-{if isset($back_button.imgclass)}{$back_button.imgclass|escape:'htmlall':'UTF-8'}{else}{$k}{/if}" class="btn btn-default" {if isset($back_button.href)}href="{$back_button.href|escape:'html':'UTF-8'}"{/if} {if isset($back_button.target) && $back_button.target}target="_blank"{/if}{if isset($back_button.js) && $back_button.js}onclick="{$back_button.js}"{/if}>
			<i class="process-icon-back {if isset($back_button.class)}{$back_button.class|escape:'htmlall':'UTF-8'}{/if}" ></i> <span {if isset($back_button.force_desc) && $back_button.force_desc == true } class="locked" {/if}>{$back_button.desc|escape:'htmlall':'UTF-8'}</span>
		</a>
	</div>
	{/if}
	{/block}
	{if !$simple_header}
			<input type="hidden" name="token" value="{$token|escape:'html':'UTF-8'}" />
		</div>
	{else}
		</div>
	{/if}
	{block name="endForm"}
	</form>
	{/block}

	{hook h='displayAdminListAfter'}
	{if isset($name_controller)}
		{capture name=hookName assign=hookName}display{$name_controller|ucfirst}ListAfter{/capture}
		{hook h=$hookName}
	{elseif isset($smarty.get.controller)}
		{capture name=hookName assign=hookName}display{$smarty.get.controller|ucfirst|htmlentities}ListAfter{/capture}
		{hook h=$hookName}
	{/if}


	{block name="after"}{/block}
{/if}

<script type="text/javascript">
// <![CDATA[
var staob = {
		fancypop: false,
		ps_version: {$version|floatval},
		titles : {
			open: "{$str_tabs['open']|escape:'javascript':'UTF-8'}",
			treated: "{$str_tabs['treated']|escape:'javascript':'UTF-8'}"
		},
		urls: {
			reload: "{$reload_href|escape:'javascript':'UTF-8'}",
			// removeInfo: "{if isset($remove_info_link)}{$remove_info_link|escape:'javascript':'UTF-8'}{/if}",
			panelInfoOff: "{if isset($url_panel_info_off)}{$url_panel_info_off|escape:'javascript':'UTF-8'}{/if}"
		},
		print: {
			errors: {if !empty($errors) && is_array($errors)}{$errors|json_encode}{else}[]{/if},
			labels: {if !empty($labels) && is_array($labels)}{$labels|json_encode}{else}[]{/if}
		},
		debug: {
			log: {if !empty($debug_log) && is_array($debug_log)}{$debug_log|json_encode}{else}{}{/if},
			urls: {
				cidResync: "{if isset($url_cid_resync)}{$url_cid_resync|escape:'javascript':'UTF-8'}{/if}",
				logSet: "{if isset($url_debug_log_set)}{$url_debug_log_set|escape:'javascript':'UTF-8'}{/if}",
				getXml: "{if isset($url_get_debug_xml)}{$url_get_debug_xml|escape:'javascript':'UTF-8'}{/if}"
			}
		}
	};
// ]]>
</script>
<script type="text/javascript" src="{$aob_src|escape:'javascript':'UTF-8'}"></script>
