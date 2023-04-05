<?php
/**
 * 2017 Stigmi
 *
 * bpost Shipping Manager
 *
 * Allow your customers to choose their preferrred delivery method: delivery at home or the office, at a pick-up location or in a bpack 24/7 parcel
 * machine.
 *
 * Release v1.60.0
 *
 * @author    Serge <serge@stigmi.eu>
 * @copyright 2017 Stigmi
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

if (!defined('_PS_VERSION_'))
	exit;

/*  object module ($this) available */
function upgrade_module_1_60_0($object)
{
	$upgrade_version = '1.60.0';
	$object->upgrade_detail[$upgrade_version] = array();
	$return = true;

/* update name, delay for carriers */
	if (! (bool)$object->updateCarriersTitles())
		$object->upgrade_detail[$upgrade_version][] = $object->l('Unable to update the required carriers info');

	$return = $return && empty($object->upgrade_detail[$upgrade_version]);
	$return = $return && $object->upgradeTo($upgrade_version);

	return $return;
}