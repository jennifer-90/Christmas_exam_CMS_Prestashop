<?php
/**
 * NOTICE OF LICENSE
 *
 * @author Mondial Relay <offrestart@mondialrelay.fr>
 * @copyright Copyright (c) Mondial Relay
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Upgrade to 3.0.5
 * @param Mondialrelay $module
 * @return bool
 * @throws PrestaShopDatabaseException
 */
function upgrade_module_3_0_5($module)
{
    $sqlDescribeColumns = "SHOW COLUMNS FROM `" . _DB_PREFIX_ . "%tableName%`";

    $describeMethodColumns = Db::getInstance()->executeS(
        str_replace("%tableName%", 'mondialrelay_carrier_method', $sqlDescribeColumns)
    );

    if (!array_search('id_reference', array_column($describeMethodColumns, 'Field'))) {
        $sql = "ALTER TABLE `" . _DB_PREFIX_ . "mondialrelay_carrier_method` ADD COLUMN id_reference int DEFAULT NULL";
        Db::getInstance()->execute($sql);
    }

    $query = new DbQuery();
    $query->select('id_mondialrelay_carrier_method, id_carrier');
    $query->from('mondialrelay_carrier_method');

    $mrCarriers = Db::getInstance()->executeS($query);

    foreach ($mrCarriers as $mrCarrier) {
        $carrier = new Carrier($mrCarrier['id_carrier']);
        if (Validate::isLoadedObject($carrier)) {
            Db::getInstance()->update(
                'mondialrelay_carrier_method',
                ['id_reference' => $carrier->id_reference],
                'id_mondialrelay_carrier_method = ' . (int)$mrCarrier['id_mondialrelay_carrier_method']
            );
        }
    }

    return true;
}
