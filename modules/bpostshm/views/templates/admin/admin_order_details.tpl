{*
* 2014-2021 Stigmi
*
* @author Serge <serge@stigmi.eu>
* @copyright 2014-2021 Stigmi
* @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*}

{if isset($sp)}
	<div class="order-details">
		<h4 {if $version > 1.5}style="margin-top:10px; margin-bottom:18px"{/if}>{$sp.slug|escape:'htmlall':'UTF-8'}</h4>
		<p>
			{$sp.lname|escape:'htmlall':'UTF-8'}:&nbsp;{$sp.id|escape:'htmlall':'UTF-8'}<br>
			{$sp.office|escape:'htmlall':'UTF-8'}<br>
			{$sp.street|escape:'htmlall':'UTF-8'} {$sp.nr|escape:'htmlall':'UTF-8'}<br>
			{$sp.zip|escape:'htmlall':'UTF-8'} {$sp.city|escape:'htmlall':'UTF-8'}<br>
			{$sp.country|escape:'htmlall':'UTF-8'}
		</p>
	</div>
<br>
{/if}

{* tracking *}
{if isset($trk)}
<script type="text/javascript">
// <![CDATA[
var TrackingInfo = {$trk|json_encode nofilter};
// ]]>
</script>
	{if isset($inc_src)}
<script type="text/javascript" src="{$inc_src|escape:"javascript"}"></script>
	{/if}
{/if}