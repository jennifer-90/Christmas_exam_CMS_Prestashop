{*
* 2014-2021 Stigmi
*
* @author Serge <serge@stigmi.eu>
* @copyright 2014-2021 Stigmi
* @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*}

<div class="panel-body">
	<p>bpost Shipping Manager is a service offered by bpost, allowing your customer to chose their preferred delivery method when ordering in your webshop.</p>
	<p>The following delivery methods are currently supported:</p>
	<ul>{strip}
		<li>Delivery at home or at the office</li>
		<li>Delivery in a pick-up point or postal office</li>
		<li>Delivery in a parcel locker</li>
	{/strip}</ul>
	<p>
		When activated and correctly installed, this module also allows you to completely integrate the bpost administration into your webshop. This means that orders are automatically added to the bpost portal. Furthermore, if enabled, it is possible to generate your labels and tracking codes directly from the Prestashop order admin page.
		<br />
		No more hassle and 100% transparent!
	</p>
	<p><span class="label label-danger red">Warning</span>:  Delivery option settings label management WARNING.</p>
	<p>
		<a href="http://bpost.freshdesk.com/support/solutions/folders/208531" title="Documentation" target="_blank">
			<img src="{$module_dir|escape}views/img/icons/information.png" alt="Documentation" />Documentation
		</a>
	</p>
</div>
<br>
<div class="form-group">
	{if $version < 1.6}
	<div class="control-label{if $version < 1.6}-bw{/if} col-lg-3">
		<span class="label label-danger red">Important</span>
	</div>
	{/if}
	<div class="margin-form col-lg-9{if $version >= 1.6} col-lg-offset-3{/if}">
		{if $version >= 1.6}<p><span class="label label-danger red">Important</span></p>{/if}
		<p>
			You need a user account from bpost to use this module. Call 02/201 11 11.
			<br />
			<a href="https://www.bpost.be/portal/goLogin?cookieAdded=yes&oss_language={$iso_code|escape}" title="Click here" target="_blank">Click here</a>
			to connect to your bpost account.
		</p>
	</div>
</div>
<div class="clear"></div>
