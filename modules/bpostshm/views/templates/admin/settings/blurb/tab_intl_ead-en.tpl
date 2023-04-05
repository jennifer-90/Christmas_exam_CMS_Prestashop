{*
* 2014-2021 Stigmi
*
* @author Serge <serge@stigmi.eu>
* @copyright 2014-2021 Stigmi
* @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*}

<div style="padding:10px 36px 15px 200px;">
	<p class="preference_description help-block" style="width:100%">
		<p>
			<strong>
				A CN23 Customs Declaration is needed for all shipments outside Europe (EU).
			</strong>
		</p>
		<br />
		<p>
			Among the mandatory information are two product features that need to be filled:
			<br />
			<ul>
				<li>The HS tariff code, a 9 digits code describing the goods</li>
				<li>The country of origin of the goods</li>
			</ul>
			<br />
			You have two options:
			<br />
			<ul>
				<li>Use default values (if all the goods sold share similar features)</li>
				<li>Fill in these values in your product catalog with PrestaShop features</li>
			</ul>
			<br />
			{if $version < 1.6}
				{assign var="ver_features" value='15/Adding+Products+and+Product+Categories#AddingProductsandProductCategories-ConfiguringProductFeatures'}
			{elseif $version < 1.7}
				{assign var="ver_features" value='16/Managing+Product+Features'}
			{else}
				{assign var="ver_features" value='17/Managing+Product+Features'}
			{/if}
			To get more information on the PrestaShop features, click on this <a href="https://doc.prestashop.com/display/PS{$ver_features|escape}" target="_blank">link</a>
		</p>
	</p>
</div>
