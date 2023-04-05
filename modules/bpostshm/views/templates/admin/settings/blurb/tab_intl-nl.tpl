{*
* 2014-2017 Stigmi
*
* @author Serge <serge@stigmi.eu>
* @copyright 2014-2017 Stigmi
* @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*}

<div style="padding:10px 36px 15px 200px;">
	<p class="preference_description help-block" style="width:100%">
		{if $version >= 1.7}
		<p>
			<strong>
				Opgepast! Het telefoonnummer van de bestemmeling is verplicht voor het aanmaken van internationale afhaalpunt etiketten.
				<br />
				Zorg dat het telefoonnummer steeds is ingevuld in het checkout scherm.
			</strong>
		</p>
		{/if}
		<p>
			<strong>
				Voor de goede werking van de module is het belangrijk dat al de landen naar waar bpost pakketten moeten verstuurd worden geconfigureerd zijn. Deze configuratie wordt in de volgende stappen uitgelegd.
			</strong>
		</p>	
		<br />
		<ol>
			<li>
				<strong>{if $version < 1.6}1. {/if}Shipping Manager configuratie</strong>
				<p>
					Om etiketten te kunnen afdrukken moeten de geselecteerde landen eerst geactiveerd worden in Shipping Manager.
					<br />
					Dit gebeurt tijdens de configuratie van de prijszones, meer informatie over deze configuratie kan
						<a href="http://bpost.freshdesk.com/support/solutions/articles/4000068592--how-to-configure-country-for-international-delivery-for-the-frontend-" target="_blank">
					HIER</a>  teruggevonden worden.
					<br />
					Ter informatie: de prijs in de prijszone configuratie van Shipping Manager is enkel nodig voor de activatie in Shipping Manager en zal niet gebruikt worden in de webshop. De webshop zal enkel rekening houden met de prijzen die in vervoerders van PrestaShop werden ingesteld.
					<br />
					<br />
				</p>
			</li>
			<br />
			<li>
				<strong>{if $version < 1.6}2. {/if}PrestaShop configuratie</strong>
				<p>
					Indien de zone “Belgium” nog niet bestaat zal deze tijdens de installatie van de PrestShop module automatisch worden aangemaakt  voor de 3 bpost carriers:
					<br />
					<ul>
						<li>1 carrier voor het product Thuislevering</li>
						<li>1 carrier voor het product Afhaalpunt</li>
						<li>1 carrier voor het product Pakjesautomaat</li>
					</ul>
					<br />
					Als je de module ook wil gebruiken voor het versturen van pakketten buiten België dan zijn de volgende zaken nodig:
					<ul>
						<li>Activatie van de landen waarnaar pakketten moeten worden verstuurd</li>
						<li>Creatie van een zone per land of groep van landen met eenzelfde tarief</li>
						<li>Koppel de landen aan de overeenkomstige zone (initieel zitten al de Europese landen in de zone Europe)</li>
					</ul>
					<br />
					Eens dat dit gedaan is kunnen de transportkosten voor elke zone van elke bpost carrier configuratie gedefinieerd worden (Hier wordt de prijs geconfigureerd die de ontvanger zal betalen voor de levering van zijn pakket).
					<br />
				</p>
			</li>
			<br />
			<li>
				<strong>{if $version < 1.6}3. {/if}Validatie van de Shipping Manager configuratie in PrestaShop.</strong>
				<p>
					Door op de “Vernieuw de lijst” knop te klikken zullen de landen die in Shipping Manager geconfigureerd staan in PrestaShop verschijnen.
				</p>
			</li>
		</ol>
	</p>
</div>
