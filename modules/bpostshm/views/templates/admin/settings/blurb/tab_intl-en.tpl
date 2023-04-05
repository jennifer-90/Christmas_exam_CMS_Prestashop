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
				Please note that the receiver's phonenumber is mandatory to create international pick up point label.
				<br />
				Please make sure this field is mandatory on the checkout.
			</strong>
			(by default it's optional as from Prestashop 1.7)
		</p>
		{/if}
		<p>
			<strong>
				To get the module correctly working, you need to first configure all the countries in which you intend to ship with bpost.
			</strong>
		</p>	
		<br />
		<ol>
			<li>
				<strong>{if $version < 1.6}1. {/if}Shipping Manager configuration</strong>
				<p>
					To print labels, the selected countries have to be activated in the Shipping Manager.
					<br />
					Countries in the Shipping Manager are activated by configuring a price zone.
					<br />
					You will find more information about the configuration of the Shipping Manager on
						<a href="http://bpost.freshdesk.com/support/solutions/articles/4000068592--how-to-configure-country-for-international-delivery-for-the-frontend-" target="_blank">
					HERE</a>.
					<br />
					Note that the costs filled in the price zone of the Shipping Manager are only necessary for the activation and that they won't be used in your webshop (only PrestaShop shipping costs will be used).
					<br />
					<br />
				</p>
			</li>
			<br />
			<li>
				<strong>{if $version < 1.6}2. {/if}PrestaShop configuration</strong>
				<p>
					During the installation of the module, a "Belgium" zone has been created if it didn't already exist together with 3 bpost carriers:
					<br />
					<ul>
						<li>1 carrier for home deliveries - national and international</li>
						<li>1 carrier for pickup deliveries - national and international</li>
						<li>1 carrier for parcel locker deliveries - only national</li>
					</ul>
					<br />
					If you wish to use the module for countries other than Belgium, you need to:
					<ul>
						<li>activate the selected countries (if not already done)</li>
						<li>create a zone for each country or group of countries with a specific cost</li>
						<li>modify the selected countries and link them to the newly created zones (by default, all European countries are in a zone Europe)</li>
					</ul>
					<br />
					That done, you'll be able to define the shipping cost for each zone in the 3 bpost carriers configuration (the price the receiver will pay for his delivery).
					<br />
				</p>
			</li>
			<br />
			<li>
				<strong>{if $version < 1.6}3. {/if}Validation of the Shipping Manager configuration</strong>
				<p>
					By clicking the "Refresh list" button, you will find the list of countries currently available in your Shipping Manager configuration.
				</p>
			</li>
		</ol>
	</p>
</div>
