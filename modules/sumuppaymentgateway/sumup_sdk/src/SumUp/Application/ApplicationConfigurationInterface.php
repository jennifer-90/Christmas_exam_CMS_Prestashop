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

namespace SumUp\Application;

/**
 * Interface ApplicationConfigurationInterface
 *
 * @package SumUp\Application
 */
interface ApplicationConfigurationInterface
{
    /**
     * Returns application's ID.
     *
     * @return string
     */
    public function getAppId();

    /**
     * Returns application's secret.
     *
     * @return string
     */
    public function getAppSecret();

    /**
     * Returns the scopes formatted as they should appear in the request.
     *
     * @return string
     */
    public function getScopes();

    /**
     * Returns the base URL of the SumUp API.
     *
     * @return string
     */
    public function getBaseURL();

    /**
     * Returns authorization code.
     *
     * @return string
     */
    public function getCode();

    /**
     * Returns grant type.
     *
     * @return string
     */
    public function getGrantType();

    /**
     * Returns merchant's username;
     *
     * @return string
     */
    public function getUsername();

    /**
     * Returns merchant's passowrd;
     *
     * @return string
     */
    public function getPassword();

    /**
     * Returns access token.
     *
     * @return string
     */
    public function getAccessToken();

    /**
     * Returns refresh token.
     *
     * @return string
     */
    public function getRefreshToken();

    /**
     * Returns a flag whether to use GuzzleHttp over cURL if both are present.
     *
     * @return bool
     */
    public function getForceGuzzle();

    /**
     * Returns associative array with custom headers.
     *
     * @return array
     */
    public function getCustomHeaders();
}
