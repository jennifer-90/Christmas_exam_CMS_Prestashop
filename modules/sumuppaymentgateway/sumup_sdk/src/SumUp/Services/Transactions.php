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

use SumUp\HttpClients\SumUpHttpClientInterface;
use SumUp\Authentication\AccessToken;
use SumUp\Exceptions\SumUpArgumentException;
use SumUp\Utils\ExceptionMessages;
use SumUp\Utils\Headers;

/**
 * Class Transactions
 *
 * @package SumUp\Services
 */
class Transactions implements SumUpService
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
     * Get single transaction by transaction ID.
     *
     * @param $transactionId
     *
     * @return \SumUp\HttpClients\Response
     *
     * @throws SumUpArgumentException
     * @throws \SumUp\Exceptions\SumUpConnectionException
     * @throws \SumUp\Exceptions\SumUpResponseException
     * @throws \SumUp\Exceptions\SumUpAuthenticationException
     * @throws \SumUp\Exceptions\SumUpSDKException
     */
    public function findById($transactionId)
    {
        if (empty($transactionId)) {
            throw new SumUpArgumentException(ExceptionMessages::getMissingParamMsg('transaction id'));
        }
        $path = '/v0.1/me/transactions?id=' . $transactionId;
        $headers = array_merge(Headers::getStandardHeaders(), Headers::getAuth($this->accessToken));
        return $this->client->send('GET', $path, array(), $headers);
    }

    /**
     * Get single transaction by internal ID.
     *
     * @param $internalId
     *
     * @return \SumUp\HttpClients\Response
     *
     * @throws SumUpArgumentException
     * @throws \SumUp\Exceptions\SumUpConnectionException
     * @throws \SumUp\Exceptions\SumUpResponseException
     * @throws \SumUp\Exceptions\SumUpAuthenticationException
     * @throws \SumUp\Exceptions\SumUpSDKException
     */
    public function findByInternalId($internalId)
    {
        if (empty($internalId)) {
            throw new SumUpArgumentException(ExceptionMessages::getMissingParamMsg('internal id'));
        }
        $path = '/v0.1/me/transactions?internal_id=' . $internalId;
        $headers = array_merge(Headers::getStandardHeaders(), Headers::getAuth($this->accessToken));
        return $this->client->send('GET', $path, array(), $headers);
    }

    /**
     * Get single transaction by transaction code.
     *
     * @param $transactionCode
     *
     * @return \SumUp\HttpClients\Response
     *
     * @throws SumUpArgumentException
     * @throws \SumUp\Exceptions\SumUpConnectionException
     * @throws \SumUp\Exceptions\SumUpResponseException
     * @throws \SumUp\Exceptions\SumUpAuthenticationException
     * @throws \SumUp\Exceptions\SumUpSDKException
     */
    public function findByTransactionCode($transactionCode)
    {
        if (empty($transactionCode)) {
            throw new SumUpArgumentException(ExceptionMessages::getMissingParamMsg('transaction code'));
        }
        $path = '/v0.1/me/transactions?transaction_code=' . $transactionCode;
        $headers = array_merge(Headers::getStandardHeaders(), Headers::getAuth($this->accessToken));
        return $this->client->send('GET', $path, array(), $headers);
    }

    /**
     * Get a list of transactions.
     *
     * @param array $filters
     *
     * @return \SumUp\HttpClients\Response
     *
     * @throws \SumUp\Exceptions\SumUpConnectionException
     * @throws \SumUp\Exceptions\SumUpResponseException
     * @throws \SumUp\Exceptions\SumUpAuthenticationException
     * @throws \SumUp\Exceptions\SumUpSDKException
     */
    public function getTransactionHistory($filters = array())
    {
        $filters = array_merge(array(
            'order' => 'ascending',
            'limit' => 10,
            'user_id' => null,
            'users' => array(),
            'statuses' => array(),
            'payment_types' => array(),
            'types' => array(),
            'changes_since' => null,
            'newest_time' => null,
            'newest_ref' => null,
            'oldest_time' => null,
            'oldest_ref' => null,
        ), $filters);

        $queryParams = http_build_query($filters);
        /**
         * Remove index from the [] because the server doesn't support it this way.
         */
        $queryParams = preg_replace('/%5B[0-9]+%5D/', '%5B%5D', $queryParams);

        $path = '/v0.1/me/transactions/history?' . $queryParams;
        $headers = array_merge(Headers::getStandardHeaders(), Headers::getAuth($this->accessToken));
        return $this->client->send('GET', $path, array(), $headers);
    }

    /**
     * Refund a transaction partially or fully.
     *
     * @param $transactionId
     * @param null $amount
     *
     * @return \SumUp\HttpClients\Response
     *
     * @throws SumUpArgumentException
     * @throws \SumUp\Exceptions\SumUpConnectionException
     * @throws \SumUp\Exceptions\SumUpResponseException
     * @throws \SumUp\Exceptions\SumUpAuthenticationException
     * @throws \SumUp\Exceptions\SumUpSDKException
     */
    public function refund($transactionId, $amount = null)
    {
        if (empty($transactionId)) {
            throw new SumUpArgumentException(ExceptionMessages::getMissingParamMsg('transaction id'));
        }
        $payload = array();
        if (!empty($amount)) {
            $payload = array(
                'amount' => $amount
            );
        }
        $path = '/v0.1/me/refund/' . $transactionId;
        $headers = array_merge(Headers::getStandardHeaders(), Headers::getAuth($this->accessToken));
        return $this->client->send('POST', $path, $payload, $headers);
    }

    /**
     * Get a receipt for a transaction.
     *
     * @param $transactionId
     * @param $merchantId
     *
     * @return \SumUp\HttpClients\Response
     *
     * @throws SumUpArgumentException
     * @throws \SumUp\Exceptions\SumUpConnectionException
     * @throws \SumUp\Exceptions\SumUpResponseException
     * @throws \SumUp\Exceptions\SumUpAuthenticationException
     * @throws \SumUp\Exceptions\SumUpSDKException
     */
    public function getReceipt($transactionId, $merchantId)
    {
        if (empty($transactionId)) {
            throw new SumUpArgumentException(ExceptionMessages::getMissingParamMsg('transaction id'));
        }
        if (empty($merchantId)) {
            throw new SumUpArgumentException(ExceptionMessages::getMissingParamMsg('merchant id'));
        }
        $queryParams = http_build_query(array('mid' => $merchantId));
        $path = '/v1.0/receipts/' . $transactionId . '?' . $queryParams;
        $headers = array_merge(Headers::getStandardHeaders(), Headers::getAuth($this->accessToken));
        return $this->client->send('GET', $path, array(), $headers);
    }
}
