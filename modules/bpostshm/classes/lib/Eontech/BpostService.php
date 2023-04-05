<?php
/**
 * Bpost class
 *
 * @author    Serge <serge@stigmi.eu>
 * @version   6.4.0
 * @copyright Copyright (c), Eontech.net All rights reserved.
 * @license   BSD License
 */

class EontechBpostService extends EontechModBpost
{
	/**
	 * Request header refactor
	 * @var  array [header type]
	 *
	 * Srg: 24-sep-2020
	 */
	protected static $_req_header = array(
		'product-config' => 'Accept: application/vnd.bpost.shm-productConfiguration-v3.1+XML',
		'order-place' => 'Content-type: application/vnd.bpost.shm-order-v3.3+XML',
		'order-fetch' => 'Accept: application/vnd.bpost.shm-order-v3.5+XML',
		'order-update' => 'Content-type: application/vnd.bpost.shm-orderUpdate-v3+XML',
		'label-req' => 'Content-Type: application/vnd.bpost.shm-labelRequest-v3+XML',
		'label-pdf' => 'Accept: application/vnd.bpost.shm-label-pdf-v3+XML',
		'label-img' => 'Accept: application/vnd.bpost.shm-label-image-v3.4+XML',
		// 'label-pdf' => 'Accept: application/vnd.bpost.shm-label-pdf-v3.6+XML',
		// 'label-img' => 'Accept: application/vnd.bpost.shm-label-image-v3.6+XML',
	);

	/**
	 * XML errors
	 *
	 * @var array
	 */
	private $xml_errors = array();
	private $logger;
	/* - - - */

	public function __construct($account_id, $pass_phrase, $api_url = '', $mode_debug = false)
	{
		parent::__construct($account_id, $pass_phrase, $api_url);

		if ((bool)$mode_debug)
			$this->logger = new EontechBpostDebugLog();
	}

	public function getProductConfig()
	{
		$msg_invalid = 'Invalid Account ID / Passphrase';
		$acc_id = $this->getAccountId();
		if (empty($acc_id))
			throw new EontechModException($msg_invalid);

		$url = '/productconfig';
		$headers = array(
			// 'Accept: application/vnd.bpost.shm-productConfiguration-v3.1+XML'
			static::$_req_header['product-config']
		);

		$prev_use = libxml_use_internal_errors(true);
		try {
			$xml = $this->doCall(
				$url,
				null,
				$headers
			);

		} catch (EontechModException $e) {
			libxml_clear_errors();
			libxml_use_internal_errors($prev_use);
			if (401 === (int)$e->getCode())
				throw new EontechModException($msg_invalid);
			else
				throw $e;
		}

		libxml_clear_errors();
		libxml_use_internal_errors($prev_use);

		if (!isset($xml->deliveryMethod))
			throw new EontechModException('No suitable delivery method');

		$business24h = false;
		$intl_countries = false;
		$ppi_countries = false;
		foreach ($xml->deliveryMethod as $dm)
			foreach ($dm->product as $product)
			{
				$product_name = (string)$product->attributes()->name;
				if ('bpack World' === mb_substr($product_name, 0, 11)
					&& isset($product->price))
				{
					$intl_countries = array();
					foreach ($product->price as $price)
						$intl_countries[] = (string)$price->attributes()->countryIso2Code;

					break;
				}
				elseif ('bpack@bpost international' === $product_name
					&& isset($product->price))
				{
					$ppi_countries = array();
					foreach ($product->price as $price)
						$ppi_countries[] = (string)$price->attributes()->countryIso2Code;

					break;
				}
				elseif (!$business24h && 'bpack 24h business' === $product_name)
					$business24h = true;
				
			}
				
		$response = array(
			'24hBusiness' => $business24h,
			'countries' => array(
				// BpostShm::SHM_INTL => $intl_countries,
				// BpostShm::SHM_PPI => $ppi_countries,
				'intl' => $intl_countries,
				'ppi' => $ppi_countries,
			),
		);
		// if (! empty($intl_countries))
		// 	$response['productCountries'] = $intl_countries;
		
		return $response;
	}

	public function getProductCountries()
	{
		$msg_invalid = 'Invalid Account ID / Passphrase';
		$acc_id = $this->getAccountId();
		if (empty($acc_id))
			throw new EontechModException($msg_invalid);

		$url = '/productconfig';
		$headers = array(
			// 'Accept: application/vnd.bpost.shm-productConfiguration-v3.1+XML'
			static::$_req_header['product-config']
		);

		$prev_use = libxml_use_internal_errors(true);
		try {
			$xml = $this->doCall(
				$url,
				null,
				$headers
			);

		} catch (EontechModException $e) {
			libxml_clear_errors();
			libxml_use_internal_errors($prev_use);
			if (401 === (int)$e->getCode())
				throw new EontechModException($msg_invalid);
			else
				throw $e;
		}

		libxml_clear_errors();
		libxml_use_internal_errors($prev_use);

		if (!isset($xml->deliveryMethod))
			throw new EontechModException('No suitable delivery method');

		$product_countries = false;
		foreach ($xml->deliveryMethod as $dm)
			foreach ($dm->product as $product)
				if ('bpack World' === mb_substr((string)$product->attributes()->name, 0, 11)
					&& isset($product->price))
				{
					$product_countries = array();
					foreach ($product->price as $price)
						$product_countries[] = (string)$price->attributes()->countryIso2Code;

					break;
				}

		return empty($product_countries) ? false : $product_countries;
	}

	public function getXmlErrors()
	{
		if (is_array($this->xml_errors) && count($this->xml_errors))
			return $this->xml_errors;

		return false;
	}

	public function debugMode()
	{
		$return = false;
		if (isset($this->logger))
			$return = (bool)$this->logger->isValid();

		return $return;
	}

	public function getDebugLog()
	{
		return $this->debugMode() ? $this->logger->getLogs() : false;
	}

	public function getDebugXmlLink($key, $qr)
	{
		return $this->debugMode() ? $this->logger->getXmlLink($key, $qr) : false;
	}

	/**
	 * Make the call
	 *
	 * @param  string $url	   The URL to call.
	 * @param  string $body	  The data to pass.
	 * @param  array  $headers   The headers to pass.
	 * @param  string $method	The HTTP-method to use.
	 * @param  bool   $expect_xml Do we expect XML?
	 * @return mixed
	 * @throws EontechModException
	 */
	protected function doCall($url, $body = null, $headers = array(), $method = 'GET', $expect_xml = true)
	{
		if (! $this->debugMode())
			return parent::doCall($url, $body, $headers, $method, $expect_xml);

		$this->logger->logRequest($url, $body, $headers, $method, $this->getWSName($url, $method));
		try {
			$response = parent::doCall($url, $body, $headers, $method, false);
			$this->logger->logResponse($response);

		} catch (EontechModException $e) {
			// log it 1st then..
			$this->logger->logResponse($e->getMessage(), $e->getCode());
			throw $e;
		}

		return (bool)$expect_xml ? simplexml_load_string($response) : $response;
	}

	protected function getWSName($url, $method = 'GET')
	{
		$name = '';
		$is_get = 'GET' == $method;
		$url_start = substr($url, 1, 5);
		switch ($url_start)
		{
			case 'produ':
				$name = 'ProductConfig';
				break;

			case 'order':
				if ($is_get)
					$name = preg_match('/\/orders\/\w+\/labels\/\w+/', $url) ? 'createLabelForOrder' : 'FetchOrder';
				else
					$name = ('/orders' == $url) ? 'createOrReplaceOrder' : 'modifyOrderStatus';
				break;

			case 'label':
				$name = 'getLabel';
				break;

			case 'boxes':
				$name = 'createLabelForBox';
				break;

			default:
				$name = 'unknown';
		}

		return (string)$name;
	}
}