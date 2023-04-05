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
				Een douaneverklaring CN23 is verplicht voor alle pakketzendingen buiten de EU.
			</strong>
		</p>
		<br />
		<p>
			Onder de verplichte informatie moeten er twee productkenmerken ingevuld worden:
			<br />
			<ul>
				<li>HS Tarrif Code, een code van 9 cijfers die de goederen classificeert.</li>
				<li>Het land van oorsprong van de goederen</li>
			</ul>
			<br />
			Er zijn twee opties:
			<br />
			<ul>
				<li>Gebruik standaard waarden (als alle goederen dezelfde kenmerken hebben)</li>
				<li>Vul de waarden in in je product catalogus bij de PrestaShop kenmerken</li>
			</ul>
			<br />
			{if $version < 1.6}
				{assign var="ver_features" value='15/Adding+Products+and+Product+Categories#AddingProductsandProductCategories-ConfiguringProductFeatures'}
			{elseif $version < 1.7}
				{assign var="ver_features" value='16/Managing+Product+Features'}
			{else}
				{assign var="ver_features" value='17/Managing+Product+Features'}
			{/if}
			Voor meer informatie over de PrestaShop kenmerken, klik op deze <a href="https://doc.prestashop.com/display/PS{$ver_features|escape}" target="_blank">link</a>
		</p>
	</p>
</div>
