{*
* 2014-2020 Stigmi
*
* @author Serge <serge@stigmi.eu>
* @copyright 2014-2020 Stigmi
* @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*}

{if isset($sp)}
	{$ver17 = (1.7 == $version)}
	{$ver16 = (1.6 == $version)}
	{if $ver17}
	<div class="addresses">
		<div class="col-lg-6 col-md-6 col-sm-6">
		    <article id="delivery-address" class="box">
		        <h4>{$sp.slug|escape:'htmlall':'UTF-8'}</h4>
		        <address>
		        	<strong>{$sp.lname|escape:'htmlall':'UTF-8'}:&nbsp;</strong>{$sp.id|escape:'htmlall':'UTF-8'}<br>
		        	{$sp.office|escape:'htmlall':'UTF-8'}<br>
		        	{$sp.street|escape:'htmlall':'UTF-8'} {$sp.nr|escape:'htmlall':'UTF-8'}<br>
		        	{$sp.zip|escape:'htmlall':'UTF-8'}&nbsp;{$sp.city|escape:'htmlall':'UTF-8'}<br>
		        	{$sp.country|escape:'htmlall':'UTF-8'}
		        </address>
		    </article>
		</div>
		<div class="clearfix"></div>
	</div>
	{else}
		{if $ver16}
		<div class="adresses_bloc">
			<div class="row">
				<div class="col-xs-12 col-sm-6">
		{else}
		<div class="adresses_bloc clearfix">
		{/if}	
			<br>
			<ul {if $ver16}class="address item box"{else}class="address item"{/if}>
				<li {if $ver16}class="page-subheading"{else}class="address_title"{/if}>{$sp.slug|escape:'htmlall':'UTF-8'}</li>			 
				<li class="address_company">{$sp.lname|escape:'htmlall':'UTF-8'}:&nbsp;<span class="address_lastname">{$sp.id|escape:'htmlall':'UTF-8'}</span></li>
				<li><span class="address_firstname">{$sp.office|escape:'htmlall':'UTF-8'}</span></li>
				<li><span class="address_address1">{$sp.street|escape:'htmlall':'UTF-8'} {$sp.nr|escape:'htmlall':'UTF-8'}</span></li>
				<li><span class="address_postcode">{$sp.zip|escape:'htmlall':'UTF-8'}</span> <span class="address_city">{$sp.city|escape:'htmlall':'UTF-8'}</span></li>
				<li><span class="address_country">{$sp.country|escape:'htmlall':'UTF-8'}</span></li>	
			</ul>
		{if $ver16}
			</div></div></div>
		{/if}
		</div>
	{/if}
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