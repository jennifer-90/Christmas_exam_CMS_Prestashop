{*
* 2014-2021 Stigmi
*
* @author Serge <serge@stigmi.eu>
* @copyright 2014-2021 Stigmi
* @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*}

<div class="panel-body">
	<p>bpost Shipping Manager est un service offert par bpost et qui autorise votre clientèle à choisir sa méthode de livraison préférée lors d'une commande sur votre boutique.</p>
	<p>Les méthodes de livraison suivantes sont supportées :</p>
	<ul>{strip}
		<li>Livraison à domicile ou au bureau</li>
		<li>Livraison en point de retrait ou bureau de poste</li>
		<li>Livraison en distributeur de paquets</li>
	{/strip}</ul>
	<p>
		Une fois activé et correctement configuré, ce module permet une intégration complète de l'outil d'administration bpost dans votre boutique et l'ajout automatique de vos commandes avec le portail bpost. Il est de plus possible de générer vos étiquettes et codes de suivi directement depuis l'administration PrestaShop.
		<br />
		Zéro tracas, transparence totale !
	</p>
	<p><span class="label label-danger red">Attention</span>:  Si vous n'utilisez PAS PrestaShop pour gérer les étiquettes ET si vous laissez votre client choisir une date de livraison (le samedi ou choix libre en semaine), le jour de dépôt (drop date) dans le réseau bpost ne serra PAS affiché dans le Shipping Manager.
	</p>
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
			Un compte bpost est requis pour utiliser ce module. Appelez le 02/201 11 11.
			<br />
			<a href="https://www.bpost.be/portal/goLogin?cookieAdded=yes&oss_language={$iso_code|escape}" title="Cliquer ici" target="_blank">Cliquer ici</a>
			pour vous connecter à votre compte bpost.
		</p>
	</div>
</div>
<div class="clear"></div>
