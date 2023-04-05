{*
* 2017 Stigmi
*
* @author Serge <serge@stigmi.eu>
* @copyright 2017 Stigmi
* @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*}
<script type="text/javascript">
// <![CDATA[
var version 		= {$version|floatval},
	id_carrier 		= {$id_carrier|intval|default:0},
	url_handler 	= '{$url_carriers_js|escape:"javascript" nofilter}',
	no_address 		= {if isset($no_address)}`{$no_address|escape:'htmlall' nofilter}`{else}''{/if},
	debug_src		= {if isset($debug_mode)}`{$debug_mode|escape:'htmlall':'UTF-8' nofilter}`{else}''{/if},
	ShippingInfo 	= {
		carriers_shm: 	{$carriers_shm|json_encode nofilter},
		url_cbox: 		'{$url_carrierbox|escape:"javascript" nofilter}',
		l_messages:	 	{$l_messages|json_encode nofilter},
		opc: 			{$opc|intval|default:false},
	};
// ]]>
</script>
{if isset($inc_src)}
<script type="text/javascript" src="{$inc_src|escape:"javascript"}"></script>
{/if}