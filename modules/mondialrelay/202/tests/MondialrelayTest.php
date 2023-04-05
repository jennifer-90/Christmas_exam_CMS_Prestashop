<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from SARL 202 ecommence
 * Use, copy, modification or distribution of this source file without written
 * license agreement from the SARL 202 ecommence is strictly forbidden.
 * In order to obtain a license, please contact us: tech@202-ecommerce.com
 * ...........................................................................
 * INFORMATION SUR LA LICENCE D'UTILISATION
 *
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe 202 ecommence
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la SARL 202 ecommence est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter 202-ecommerce <tech@202-ecommerce.com>
 * ...........................................................................
 *
 * @author    202-ecommerce <tech@202-ecommerce.com>
 * @copyright Copyright (c) 202-ecommerce
 * @license   Commercial license
 */

namespace Tests\Unit\modules\mondialrelay;

use PrestaShop\PrestaShop\Core\Addon\Module\ModuleManagerBuilder;
use PHPUnit\Framework\TestCase;
use Tests\Unit\ContextMocker;

use MondialrelayClasslib\Actions\ActionsHandler;

class MondialrelayTest extends TestCase
{
    public static $moduleManager;

    public static function setUpBeforeClass()
    {
        $contextMocker = new ContextMocker();
        $contextMocker->mockContext();
        
        \ContextCore::getContext()->employee = new \Employee(1);
        $moduleManagerBuilder = ModuleManagerBuilder::getInstance();
        self::$moduleManager = $moduleManagerBuilder->build();
    }
    
    public function testInstall()
    {
        $this->assertTrue((bool)self::$moduleManager->install('mondialrelay'), "Could not install mondialrelay");
    }
    
    public function testCarrierCreation()
    {
        // Create the handler
        $handler = new ActionsHandler();
        
        /**
         * Add a new Prestashop carrier.
         *
         * - The carrier is created with all zones.
         * - Default pricing behavior is "according to weight".
         * - A default weight range will be created (depending on the selected
         * "delivery mode" for the Mondial Relay carrier).
         * - A default price range will be created as well (same values for every
         * "delivery mode").
         * - The carrier will be available for every client groups.
         *
         * @param string $name The new carrier's name
         * @param string $delay The new carrier's delay
         *
         * @return Carrier|false The new carrier or false
         */
        
        // Set input data
        $handler->setConveyor(array(
                'name' => "Mondial Relay Carrier 24R",
                'delay' => "24R",
                'delivery_mode' => "24R",
                'insurance_level' => "0",
                'weight_coeff' => 1,
            ))
            // Set list of actions to execute
            ->addActions(
                'addNativeCarrier',
                'addMondialRelayCarrierMethod',
                'setDefaultZones',
                'setDefaultRangeWeight',
                'setDefaultRangePrice',
                'setDefaultGroups'
            );
        
        // Process actions chain
        $processStatus = $handler->process('NewCarrier');
        $this->assertTrue($processStatus);
        
        // Get process result
        $actionsResult = $handler->getConveyor();
        
        $this->assertTrue(!empty($actionsResult['carrier']) && is_object($actionsResult['carrier']));
        $carrier = $actionsResult['carrier'];
        
        $carrierMethod = \MondialrelayCarrierMethod::getFromNativeCarrierId($carrier->id);
        $this->assertTrue(!empty($carrierMethod) && is_object($carrierMethod) && $carrierMethod->id_carrier == $carrier->id);
    }
    
    public static function tearDownAfterClass()
    {
        if (self::$moduleManager->isInstalled('mondialrelay')) {
            self::$moduleManager->uninstall('mondialrelay');
        }
    }
}
