{*
* 2014-2021 Stigmi
*
* @author Serge <serge@stigmi.eu>
* @copyright 2014-2021 Stigmi
* @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*}

<div class="panel-body">
	<p>bpost Shipping Manager is een door bpost aangeboden service, die uw klant toelaat zijn of haar geprefereerde verzendingsmethode te kiezen tijdens een bestelling in uw webshop.</p>
	<p>Volgende verzendingsmethodes zijn momenteel toegelaten:</p>
	<ul>{strip}
		<li>Levering thuis of op kantoor</li>
		<li>Levering in een afhaalpunt of postkantoor</li>
		<li>Levering in een pakjesautomaat</li>
	{/strip}</ul>
	<p>
		Eens correct geïnstalleerd en geactiveerd, laat deze module tevens toe de volledige bpost administratie in uw webshop te integreren. Dit wil zeggen dat bestellingen automatisch aan de bpost portal toegevoegd worden. Daarenboven is het, mits activatie, mogelijk om etiketten en tracking codes rechtstreeks vanuit de Prestashop bestellings-admin pagina te genereren.
		<br />
		Geen gedoe meer en 100% transparant!
	</p>
	<p>
		<span class="label label-danger red">Opgelet</span>:  Als u PrestaShop NIET gebruikt voor het beheren van uw labels EN u laat uw klant zelf de leveringsdatum kiezen (zaterdaglevering of vrije keuze van een weekdag), dan zal de vereiste dag van aanlevering in het bpost netwerk NIET geafficheerd worden in Shipping Manager.
	</p>
	<p>
		<a href="http://bpost.freshdesk.com/support/solutions/folders/208531" title="Documentatie" target="_blank">
			<img src="{$module_dir|escape}views/img/icons/information.png" alt="Documentatie" />Documentatie
		</a>
	</p>
</div>
<br>
<div class="form-group">
	{if $version < 1.6}
	<div class="control-label{if $version < 1.6}-bw{/if} col-lg-3">
		<span class="label label-danger red">Belangrijk</span>
	</div>
	{/if}
	<div class="margin-form col-lg-9{if $version >= 1.6} col-lg-offset-3{/if}">
		{if $version >= 1.6}<p><span class="label label-danger red">Belangrijk</span></p>{/if}
		<p>
			U heeft een bpost gebruikersaccount nodig om deze module te gebruiken. Gelieve 02/201 11 11 te bellen.
			<br />
			<a href="https://www.bpost.be/portal/goLogin?cookieAdded=yes&oss_language={$iso_code|escape}" title="Klik hier" target="_blank">Klik hier</a>
			om aan te sluiten op uw bpost account.
		</p>
	</div>
</div>
<div class="clear"></div>
