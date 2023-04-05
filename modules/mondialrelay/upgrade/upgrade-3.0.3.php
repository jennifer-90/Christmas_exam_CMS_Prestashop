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
 * Upgrade to 3.0.3
 * @param Mondialrelay $module
 */
function upgrade_module_3_0_3($module)
{
    $sqlDescribeColumns = "SHOW COLUMNS FROM `"._DB_PREFIX_."%tableName%`";

    $describeMethodColumns = Db::getInstance()->executeS(
        str_replace("%tableName%", "mondialrelay_carrier_method", $sqlDescribeColumns)
    );

    if (!array_search('date_add', array_column($describeMethodColumns, 'Field'))) {
        $sql = "ALTER TABLE `"._DB_PREFIX_."mondialrelay_carrier_method` ADD COLUMN date_add datetime DEFAULT NULL";
        Db::getInstance()->execute($sql);
    }

    if (!array_search('date_upd', array_column($describeMethodColumns, 'Field'))) {
        $sql = "ALTER TABLE `"._DB_PREFIX_."mondialrelay_carrier_method` ADD COLUMN date_upd datetime DEFAULT NULL";
        Db::getInstance()->execute($sql);
        Db::getInstance()->execute(
            "UPDATE "._DB_PREFIX_."mondialrelay_carrier_method "
            . "SET date_add = NOW(), date_upd = NOW() "
            . "WHERE date_add = '0000-00-00 00:00:00'"
        );
    }

    $describeMethodColumns = Db::getInstance()->executeS(
        str_replace("%tableName%", "mondialrelay_selected_relay", $sqlDescribeColumns)
    );

    if (!array_search('id_address_delivery', array_column($describeMethodColumns, 'Field'))) {
        $sql = "ALTER TABLE `"._DB_PREFIX_."mondialrelay_selected_relay` ADD COLUMN id_address_delivery int(11)";
        Db::getInstance()->execute($sql);
    }

    if (!array_search('date_label_generation', array_column($describeMethodColumns, 'Field'))) {
        $sql = "ALTER TABLE `"._DB_PREFIX_."mondialrelay_selected_relay` ADD COLUMN date_label_generation datetime DEFAULT NULL";
        Db::getInstance()->execute($sql);
    }

    if (!array_search('hide_history', array_column($describeMethodColumns, 'Field'))) {
        $sql = "ALTER TABLE `"._DB_PREFIX_."mondialrelay_selected_relay` ADD COLUMN hide_history bool DEFAULT 0";
        Db::getInstance()->execute($sql);
    }

    return true;
}
