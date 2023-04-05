<?php
/**
 * bPost AtIntlPugo class
 *
 * @author    Serge Jamasb <serge@stigmi.eu>
 * @version   3.4.0
 * @copyright Copyright (c), Eontech.net. All rights reserved.
 * @license   BSD License
 */

class EontechModBpostOrderBoxAtIntlPugo extends EontechModBpostOrderBoxInternational
{
	const TAG_TYPE = 'atIntlPugo';

	/**
	 * @var string
	 */
	protected $product = 'bpack@bpost international';

	/**
	 * @var string
	 */
	private $pugo_id;

	/**
	 * @var string
	 */
	private $pugo_name;

	/**
	 * @var EontechModBpostOrderIntlPugoAddress;
	 */
	private $pugo_address;

	/**
	 * @return array
	 */
	protected static function getPossibleProductValues()
	{
		return array(
			'bpack@bpost international',
		);
	}

	/**
	 * @return array
	 */
	protected static function getSupportedOptions()
	{
		return array(
			'infoDistributed',
			'infoNextDay',
			'infoReminder',
			'keepMeInformed',
		);
	}

	/**
	 * @param string $pugo_id
	 */
	public function setPugoId($pugo_id)
	{
		$this->pugo_id = $pugo_id;
	}

	/**
	 * @return string
	 */
	public function getPugoId()
	{
		return $this->pugo_id;
	}

	/**
	 * @param string $pugo_name
	 */
	public function setPugoName($pugo_name)
	{
		$this->pugo_name = $pugo_name;
	}

	/**
	 * @return string
	 */
	public function getPugoName()
	{
		return $this->pugo_name;
	}

	/**
	 * @param EontechModBpostOrderIntlPugoAddress $pugo_address
	 */
	public function setPugoAddress($pugo_address)
	{
		$this->pugo_address = $pugo_address;
	}

	/**
	 * @return EontechModBpostOrderIntlPugoAddress
	 */
	public function getPugoAddress()
	{
		return $this->pugo_address;
	}

	/**
	 * Additional elements within descendants
	 * 
	 * @param  \DOMDocument $document
	 * @param  \DOMElement  $type_element
	 * @return \DOMElement
	 */
	protected function alsoToXML(\DOMDocument $document, \DOMElement $type_element)
	{
		if ($this->getPugoId() !== null)
		{
			$tag_name = 'international:pugoId';
			$type_element->appendChild(
				$document->createElement(
					$tag_name,
					$this->getPugoId()
				)
			);
		}
		if ($this->getPugoName() !== null)
		{
			$tag_name = 'international:pugoName';
			$type_element->appendChild(
				$document->createElement(
					$tag_name,
					$this->getPugoName()
				)
			);
		}
		if ($this->getPugoAddress() !== null)
			$type_element->appendChild(
				$this->getPugoAddress()->toXML($document, 'common')
			);
	}

	/**
	 * @param  \SimpleXMLElement $xml_elm
	 * @return Derivative instance
	 */
	protected function createAlsoFromXML(\SimpleXMLElement $xml_elm)
	{
		if (isset($xml_elm->pugoId) && $xml_elm->pugoId != '')
			$this->setPugoId((string)$xml_elm->pugoId);
		if (isset($xml_elm->pugoName) && $xml_elm->pugoName != '')
			$this->setPugoName((string)$xml_elm->pugoName);
		if (isset($xml_elm->pugoAddress))
		{
			$pugo_address_data = $xml_elm->pugoAddress->children(
				'http://schema.post.be/shm/deepintegration/v3/common'
			);
			$this->setPugoAddress(
				EontechModBpostOrderIntlPugoAddress::createFromXML($pugo_address_data)
			);
		}
	}
}