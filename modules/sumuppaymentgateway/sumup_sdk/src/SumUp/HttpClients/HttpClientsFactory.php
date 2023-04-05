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

namespace SumUp\HttpClients;

use SumUp\Exceptions\SumUpConfigurationException;
use SumUp\Application\ApplicationConfigurationInterface;

/**
 * Class HttpClientsFactory
 *
 * @package SumUp\HttpClients
 */
class HttpClientsFactory
{
    private function __construct()
    {
        // a factory constructor should never be invoked
    }

    /**
     * Create the HTTP client needed for communication with the SumUp's servers.
     *
     * @param ApplicationConfigurationInterface $appConfig
     * @param SumUpHttpClientInterface|null $customHttpClient
     *
     * @return SumUpHttpClientInterface
     *
     * @throws SumUpConfigurationException
     */
    public static function createHttpClient(ApplicationConfigurationInterface $appConfig, SumUpHttpClientInterface $customHttpClient = null)
    {
        if ($customHttpClient) {
            return $customHttpClient;
        }
        return self::detectDefaultClient($appConfig->getBaseURL(), $appConfig->getForceGuzzle(), $appConfig->getCustomHeaders());
    }

    /**
     * Detect the default HTTP client.
     *
     * @param string $baseURL
     * @param bool $forceUseGuzzle
     *
     * @return SumUpCUrlClient|SumUpGuzzleHttpClient
     *
     * @throws SumUpConfigurationException
     */
    private static function detectDefaultClient($baseURL, $forceUseGuzzle, $customHeaders)
    {
        if (extension_loaded('curl') && !$forceUseGuzzle) {
            return new SumUpCUrlClient($baseURL, $customHeaders);
        }
        if (class_exists('GuzzleHttp\Client')) {
            return new SumUpGuzzleHttpClient($baseURL, $customHeaders);
        }

        throw new SumUpConfigurationException('No default http client found. Please install cURL or GuzzleHttp.');
    }
}
