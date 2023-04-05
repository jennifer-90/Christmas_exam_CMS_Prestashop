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
				Une déclaration douanière CN23 est nécessaire pour tous les envois hors Europe (UE).
			</strong>
		</p>
		<br />
		<p>
			Parmi les informations obligatoires figurent deux caractéristiques produits à renseigner :
			<br />
			<ul>
				<li>Le HS code, code international à 9 chiffres caractérisant la marchandise</li>
				<li>Le pays d’origine de la marchandise</li>
			</ul>
			<br />
			Deux possibilités s'offrent à vous :
			<br />
			<ul>
				<li>utiliser des valeurs par défaut (si tous vos produits vendus sont du même type)</li>
				<li>renseigner ces valeurs dans vos fiches produit grâce aux caractéristiques PrestaShop</li>
			</ul>
			<br />
			{if $version < 1.6}
				{assign var="ver_features" value='15/Adding+Products+and+Product+Categories#AddingProductsandProductCategories-ConfiguringProductFeatures'}
			{elseif $version < 1.7}
				{assign var="ver_features" value='16/Managing+Product+Features'}
			{else}
				{assign var="ver_features" value='17/Gerer+les+caracteristiques+de+vos+produits'}
			{/if}
			Pour plus de détails sur les caractéristiques PrestaShop, voir ce <a href="https://doc.prestashop.com/display/PS{$ver_features|escape}" target="_blank">lien</a>
		</p>
	</p>
</div>
