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
				Attention, le numéro de téléphone du destinataire est obligatoire pour la création d'étiquettes en point relais internationale.
				<br />
				Veuillez vérifier que ce champs est bien obligatoire dans le checkout.
			</strong>
		</p>
		{/if}
		<p>
			<strong>
				Pour une utilisation correcte du module bpost, vous devez au préalable configurer les pays vers lequels vous livrerez grâce à bpost.
			</strong>
		</p>	
		<br />
		<ol>
			<li>
				<strong>{if $version < 1.6}1. {/if}Configuration dans le Shipping Manager</strong>
				<p>
					Pour pouvoir imprimer des étiquettes, les pays concernés doivent préalablement avoir été activés dans le Shipping Manager.
					<br />
					Les pays dans le Shipping Manager sont activés en configurant une zone de prix par tranche de poids.
					<br />
					Vous trouverez plus d'information sur la configuration des livraisons à domicile internationale avec le Shipping Manager
						<a href="http://bpost.freshdesk.com/support/solutions/articles/4000068592--how-to-configure-country-for-international-delivery-for-the-frontend-" target="_blank">
					ICI</a>.
					<br />
					A noter que les tarifs indiqués dans la zone de prix du Shipping Manager ne servent que pour l'activation et ne seront pas repris dans votre boutique (seuls les tarifs définis dans les transporteurs PrestaShop seront utilisés).
					<br />
					<br />
				</p>
			</li>
			<br />
			<li>
				<strong>{if $version < 1.6}2. {/if}Configuration dans PrestaShop</strong>
				<p>
					Lors de l'installation du module, une zone Prestashop "Belgium" a été créée si elle n'existait pas déjà, ainsi que 3 transporteurs bpost.
					<br />
					<ul>
						<li>1 transporteur pour les livraisons à domicile - national et international</li>
						<li>1 transporteur pour les livraisons en points relais - national et international</li>
						<li>1 transporteur pour les livraisons en distributeur de paquet - uniquement national</li>
					</ul>
					<br />
					Si vous souhaitez proposer le module pour d'autre pays que la Belgique, vous devez :
					<ul>
						<li>activer les pays concernés (si ce n'est pas déjà fait)</li>
						<li>créer une zone pour chaque pays ou groupe de pays ayant un tarif spécifique</li>
						<li>modifier les pays concernés et les rattacher aux zones nouvellement créées (par défaut les pays européens sont tous dans une zone Europe)</li>
					</ul>
					<br />
					A ce stade, vous pourrez définir les tarifs par zone à appliquer dans la configuration des 3 transporteurs bpost sous Prestashop (il s'agit ici du tarif que le destinataire paiera pour sa livraison)
					<br />
				</p>
			</li>
			<br />
			<li>
				<strong>{if $version < 1.6}3. {/if}Vérification de la configuration Shipping Manager</strong>
				<p>
					En cliquant sur le bouton ci-dessous "Rafraichir liste", vous trouverez l'ensemble des pays actuellement disponibles dans votre configuration Shipping Manager.
				</p>
			</li>
		</ol>
	</p>
</div>
