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

namespace SumUp\Services;

use SumUp\Application\ApplicationConfigurationInterface;
use SumUp\Exceptions\SumUpConfigurationException;
use SumUp\Exceptions\SumUpArgumentException;
use SumUp\HttpClients\SumUpHttpClientInterface;
use SumUp\Application\ApplicationConfiguration;
use SumUp\Authentication\AccessToken;
use SumUp\Utils\ExceptionMessages;
use SumUp\Utils\Headers;

/**
 * Class Authorization
 *
 * @package SumUp\Services
 */
class Authorization implements SumUpService
{
    /**
     * The client for the http communication.
     *
     * @var SumUpHttpClientInterface
     */
    protected $client;

    /**
     * The application's configuration.
     *
     * @var ApplicationConfiguration
     */
    protected $appConfig;

    /**
     * Authorization constructor.
     *
     * @param SumUpHttpClientInterface $client
     * @param ApplicationConfigurationInterface $config
     */
    public function __construct(SumUpHttpClientInterface $client, ApplicationConfigurationInterface $config)
    {
        $this->client = $client;
        $this->appConfig = $config;
    }

    /**
     * Returns an access token according to the grant_type.
     *
     * @return null|AccessToken
     *
     * @throws SumUpConfigurationException
     * @throws \SumUp\Exceptions\SumUpAuthenticationException
     * @throws \SumUp\Exceptions\SumUpConnectionException
     * @throws \SumUp\Exceptions\SumUpResponseException
     * @throws \SumUp\Exceptions\SumUpSDKException
     */
    public function getToken()
    {
        $accessToken = null;
        if (!empty($this->appConfig->getAccessToken())) {
            $accessToken = new AccessToken(
                $this->appConfig->getAccessToken(),
                '',
                0,
                $this->appConfig->getScopes(),
                $this->appConfig->getRefreshToken()
            );
        } else if (!empty($this->appConfig->getRefreshToken())) {
            $accessToken = new AccessToken(
                '',
                '',
                0,
                $this->appConfig->getScopes(),
                $this->appConfig->getRefreshToken()
            );
        } else {
            switch ($this->appConfig->getGrantType()) {
                case 'authorization_code':
                    $accessToken = $this->getTokenByCode();
                    break;
                case 'client_credentials':
                    $accessToken = $this->getTokenByClientCredentials();
                    break;
                case 'password':
                    $accessToken = $this->getTokenByPassword();
                    break;
            }
        }
        return $accessToken;
    }

    /**
     * Returns an access token for the grant type "authorization_code".
     *
     * @return AccessToken
     *
     * @throws \SumUp\Exceptions\SumUpConnectionException
     * @throws \SumUp\Exceptions\SumUpResponseException
     * @throws \SumUp\Exceptions\SumUpAuthenticationException
     * @throws \SumUp\Exceptions\SumUpSDKException
     */
    public function getTokenByCode()
    {
        $payload = array(
            'grant_type' => 'authorization_code',
            'client_id' => $this->appConfig->getAppId(),
            'client_secret' => $this->appConfig->getAppSecret(),
            'scope' => $this->appConfig->getFormattedScopes(),
            'code' => $this->appConfig->getCode()
        );
        $headers = Headers::getStandardHeaders();
        $response = $this->client->send('POST', '/token', $payload, $headers);
        $resBody = $response->getBody();
        $scopes = array();
        if (!empty($resBody->scope)) {
            $scopes = explode(' ', $resBody->scope);
        }
        return new AccessToken($resBody->access_token, $resBody->token_type, $resBody->expires_in, $scopes, $resBody->refresh_token);
    }

    /**
     * Returns an access token for the grant type "client_credentials".
     *
     * @return AccessToken
     *
     * @throws \SumUp\Exceptions\SumUpConnectionException
     * @throws \SumUp\Exceptions\SumUpResponseException
     * @throws \SumUp\Exceptions\SumUpAuthenticationException
     * @throws \SumUp\Exceptions\SumUpSDKException
     */
    public function getTokenByClientCredentials()
    {
        $payload = array(
            'grant_type' => 'client_credentials',
            'client_id' => $this->appConfig->getAppId(),
            'client_secret' => $this->appConfig->getAppSecret(),
            'scope' => $this->appConfig->getFormattedScopes()
        );
        $headers = Headers::getStandardHeaders();
        $response = $this->client->send('POST', '/token', $payload, $headers);
        $resBody = $response->getBody();
        return new AccessToken($resBody->access_token, $resBody->token_type, $resBody->expires_in);
    }

    /**
     * Returns an access token for the grant type "password".
     *
     * @return AccessToken
     *
     * @throws SumUpConfigurationException
     * @throws \SumUp\Exceptions\SumUpConnectionException
     * @throws \SumUp\Exceptions\SumUpResponseException
     * @throws \SumUp\Exceptions\SumUpAuthenticationException
     * @throws \SumUp\Exceptions\SumUpSDKException
     */
    public function getTokenByPassword()
    {
        if (empty($this->appConfig->getUsername())) {
            throw new SumUpConfigurationException(ExceptionMessages::getMissingParamMsg('username'));
        }
        if (empty($this->appConfig->getPassword())) {
            throw new SumUpConfigurationException(ExceptionMessages::getMissingParamMsg("password"));
        }
        $payload = array(
            'grant_type' => 'password',
            'client_id' => $this->appConfig->getAppId(),
            'client_secret' => $this->appConfig->getAppSecret(),
            'scope' => $this->appConfig->getFormattedScopes(),
            'username' => $this->appConfig->getUsername(),
            'password' => $this->appConfig->getPassword()
        );
        $headers = Headers::getStandardHeaders();
        $response = $this->client->send('POST', '/token', $payload, $headers);
        $resBody = $response->getBody();
        $scopes = array();
        if (!empty($resBody->scope)) {
            $scopes = explode(' ', $resBody->scope);
        }
        return new AccessToken($resBody->access_token, $resBody->token_type, $resBody->expires_in, $scopes, $resBody->refresh_token);
    }

    /**
     * Refresh access token.
     *
     * @param string $refreshToken
     *
     * @return AccessToken
     *
     * @throws SumUpArgumentException
     * @throws \SumUp\Exceptions\SumUpConnectionException
     * @throws \SumUp\Exceptions\SumUpResponseException
     * @throws \SumUp\Exceptions\SumUpAuthenticationException
     * @throws \SumUp\Exceptions\SumUpSDKException
     */
    public function refreshToken($refreshToken)
    {
        if (empty($refreshToken)) {
            throw new SumUpArgumentException(ExceptionMessages::getMissingParamMsg('refresh token'));
        }
        $payload = array(
            'grant_type' => 'refresh_token',
            'client_id' => $this->appConfig->getAppId(),
            'client_secret' => $this->appConfig->getAppSecret(),
            'refresh_token' => $refreshToken,
            'scope' => $this->appConfig->getFormattedScopes()
        );
        $headers = Headers::getStandardHeaders();
        $response = $this->client->send('POST', '/token', $payload, $headers);
        $resBody = $response->getBody();
        $scopes = array();
        if (!empty($resBody->scope)) {
            $scopes = explode(' ', $resBody->scope);
        }
        return new AccessToken($resBody->access_token, $resBody->token_type, $resBody->expires_in, $scopes, $resBody->refresh_token);
    }
}
