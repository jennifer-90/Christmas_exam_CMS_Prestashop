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

use SumUp\Exceptions\SumUpArgumentException;
use SumUp\HttpClients\SumUpHttpClientInterface;
use SumUp\Authentication\AccessToken;
use SumUp\Utils\ExceptionMessages;
use SumUp\Utils\Headers;

/**
 * Class Checkouts
 *
 * @package SumUp\Services
 */
class Checkouts implements SumUpService
{
    /**
     * The client for the http communication.
     *
     * @var SumUpHttpClientInterface
     */
    protected $client;

    /**
     * The access token needed for authentication for the services.
     *
     * @var AccessToken
     */
    protected $accessToken;

    /**
     * Checkouts constructor.
     *
     * @param SumUpHttpClientInterface $client
     * @param AccessToken $accessToken
     */
    public function __construct(SumUpHttpClientInterface $client, AccessToken $accessToken)
    {
        $this->client = $client;
        $this->accessToken = $accessToken;
    }

    /**
     * Create new checkout.
     *
     * @param float  $amount
     * @param string $currency
     * @param string $checkoutRef
     * @param string $payToEmail
     * @param string $description
     * @param null   $payFromEmail
     * @param null   $returnURL
     *
     * @return \SumUp\HttpClients\Response
     *
     * @throws SumUpArgumentException
     * @throws \SumUp\Exceptions\SumUpConnectionException
     * @throws \SumUp\Exceptions\SumUpResponseException
     * @throws \SumUp\Exceptions\SumUpAuthenticationException
     * @throws \SumUp\Exceptions\SumUpSDKException
     */
    public function create($amount, $currency, $checkoutRef, $payToEmail, $description = '', $payFromEmail = null, $returnURL = null)
    {
        if (empty($amount) || !is_numeric($amount)) {
            throw new SumUpArgumentException(ExceptionMessages::getMissingParamMsg('amount'));
        }
        if (empty($currency)) {
            throw new SumUpArgumentException(ExceptionMessages::getMissingParamMsg('currency'));
        }
        if (empty($checkoutRef)) {
            throw new SumUpArgumentException(ExceptionMessages::getMissingParamMsg('checkout reference id'));
        }
        if (empty($payToEmail)) {
            throw new SumUpArgumentException(ExceptionMessages::getMissingParamMsg('pay to email'));
        }
        $payload = array(
            'amount' => $amount,
            'currency' => $currency,
            'checkout_reference' => $checkoutRef,
            'pay_to_email' => $payToEmail,
            'description' => $description
        );
        if (isset($payFromEmail)) {
            $payload['pay_from_email'] = $payFromEmail;
        }
        if (isset($returnURL)) {
            $payload['return_url'] = $returnURL;
        }
        $path = '/v0.1/checkouts';
        $headers = array_merge(Headers::getStandardHeaders(), Headers::getAuth($this->accessToken));
        return $this->client->send('POST', $path, $payload, $headers);
    }

    /**
     * Get single checkout by provided checkout ID.
     *
     * @param string $checkoutId
     *
     * @return \SumUp\HttpClients\Response
     *
     * @throws SumUpArgumentException
     * @throws \SumUp\Exceptions\SumUpConnectionException
     * @throws \SumUp\Exceptions\SumUpResponseException
     * @throws \SumUp\Exceptions\SumUpAuthenticationException
     * @throws \SumUp\Exceptions\SumUpSDKException
     */
    public function findById($checkoutId)
    {
        if (empty($checkoutId)) {
            throw new SumUpArgumentException(ExceptionMessages::getMissingParamMsg('checkout id'));
        }
        $path = '/v0.1/checkouts/' . $checkoutId;
        $headers = array_merge(Headers::getStandardHeaders(), Headers::getAuth($this->accessToken));
        return $this->client->send('GET', $path, array(), $headers);
    }

    /**
     * Get single checkout by provided checkout reference ID.
     *
     * @param string $referenceId
     *
     * @return \SumUp\HttpClients\Response
     *
     * @throws SumUpArgumentException
     * @throws \SumUp\Exceptions\SumUpConnectionException
     * @throws \SumUp\Exceptions\SumUpResponseException
     * @throws \SumUp\Exceptions\SumUpAuthenticationException
     * @throws \SumUp\Exceptions\SumUpSDKException
     */
    public function findByReferenceId($referenceId)
    {
        if (empty($referenceId)) {
            throw new SumUpArgumentException(ExceptionMessages::getMissingParamMsg('reference id'));
        }
        $path = '/v0.1/checkouts?checkout_reference=' . $referenceId;
        $headers = array_merge(Headers::getStandardHeaders(), Headers::getAuth($this->accessToken));
        return $this->client->send('GET', $path, array(), $headers);
    }

    /**
     * Delete a checkout.
     *
     * @param string $checkoutId
     *
     * @return \SumUp\HttpClients\Response
     *
     * @throws SumUpArgumentException
     * @throws \SumUp\Exceptions\SumUpConnectionException
     * @throws \SumUp\Exceptions\SumUpResponseException
     * @throws \SumUp\Exceptions\SumUpAuthenticationException
     * @throws \SumUp\Exceptions\SumUpSDKException
     */
    public function delete($checkoutId)
    {
        if (empty($checkoutId)) {
            throw new SumUpArgumentException(ExceptionMessages::getMissingParamMsg('checkout id'));
        }
        $path = '/v0.1/checkouts/' . $checkoutId;
        $headers = array_merge(Headers::getStandardHeaders(), Headers::getAuth($this->accessToken));
        return $this->client->send('DELETE', $path, array(), $headers);
    }

    /**
     * Pay a checkout with tokenized card.
     *
     * @param string $checkoutId
     * @param string $customerId
     * @param string $cardToken
     * @param int    $installments
     *
     * @return \SumUp\HttpClients\Response
     *
     * @throws SumUpArgumentException
     * @throws \SumUp\Exceptions\SumUpConnectionException
     * @throws \SumUp\Exceptions\SumUpResponseException
     * @throws \SumUp\Exceptions\SumUpAuthenticationException
     * @throws \SumUp\Exceptions\SumUpSDKException
     */
    public function pay($checkoutId, $customerId, $cardToken, $installments = 1)
    {
        if (empty($checkoutId)) {
            throw new SumUpArgumentException(ExceptionMessages::getMissingParamMsg('checkout id'));
        }
        if (empty($customerId)) {
            throw new SumUpArgumentException(ExceptionMessages::getMissingParamMsg('customer id'));
        }
        if (empty($cardToken)) {
            throw new SumUpArgumentException(ExceptionMessages::getMissingParamMsg('card token'));
        }
        if (empty($installments) || !is_int($installments)) {
            throw new SumUpArgumentException(ExceptionMessages::getMissingParamMsg('installments'));
        }
        $payload = array(
            'payment_type' => 'card',
            'customer_id' => $customerId,
            'token' => $cardToken,
            'installments' => $installments
        );
        $path = '/v0.1/checkouts/' . $checkoutId;
        $headers = array_merge(Headers::getStandardHeaders(), Headers::getAuth($this->accessToken));
        return $this->client->send('PUT', $path, $payload, $headers);
    }

    public function payWithCard($checkoutId, $card)
    {
        if (empty($checkoutId)) {
            throw new SumUpArgumentException(ExceptionMessages::getMissingParamMsg('checkout id'));
        }

        $payload = array(
            'payment_type' => 'card',
            'card' => $card
        );
        $path = '/v0.1/checkouts/' . $checkoutId;
        $headers = array_merge(Headers::getStandardHeaders(), Headers::getAuth($this->accessToken));
        return $this->client->send('PUT', $path, $payload, $headers);
    }
}
