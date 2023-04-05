<?php
/**
 * 2007-2019 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2019 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

namespace SumUp\Utils;

use SumUp\Authentication\AccessToken;

/**
 * Class Headers
 *
 * @package SumUp\Utils
 */
class Headers
{
    /**
     * Cached value of the project's version.
     *
     * @var string $cacheVersion
     */
    protected static $cacheVersion;
    /**
     * Get the common header for Content-Type: application/json.
     *
     * @return array
     */
    public static function getCTJson()
    {
        return array('Content-Type' => 'application/json');
    }

    /**
     * Get the common header for Content-Type: application/x-www-form-urlencoded.
     *
     * @return array
     */
    public static function getCTForm()
    {
        return array('Content-Type' => 'application/x-www-form-urlencoded');
    }

    /**
     * Get the authorization header with token.
     *
     * @param AccessToken $accessToken
     *
     * @return array
     */
    public static function getAuth(AccessToken $accessToken)
    {
        return array('Authorization' => 'Bearer ' . $accessToken->getValue());
    }

    /**
     * Get custom array.
     *
     * @return array
     */
    public static function getTrk()
    {
        return array('X-SDK' => 'PHP-SDK/v' . self::getProjectVersion() . ' PHP/v' . phpversion());
    }

    /**
     * Get the version of the project accroding to the composer.json
     *
     * @return string
     */
    public static function getProjectVersion()
    {
        if (is_null(self::$cacheVersion)) {
            $pathToComposer = realpath(dirname(__FILE__) . '/../../../composer.json');
            $content = \Tools::file_get_contents($pathToComposer);
            $content = json_decode($content, true);
            self::$cacheVersion = $content['version'];
        }

        return self::$cacheVersion;
    }

    /**
     * Get standard headers needed for every request.
     *
     * @return array
     */
    public static function getStandardHeaders()
    {
        $headers = self::getCTJson();
        $headers += self::getTrk();
        return $headers;
    }
}
