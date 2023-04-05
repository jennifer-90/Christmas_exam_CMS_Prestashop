<?php
/**
 * bPost CustomsInfo class
 *
 * @author    Serge Jamasb <serge@stigmi.eu>
 * @version   3.4.0
 *            01-aug-2016: getPossibleShipmentTypeValues += [GOODS]
 *            14-mar-2021: (optional) Non-EU EAD properties + refactor class placement.
 * @copyright Copyright (c), Eontech.net. All rights reserved.
 * @license   BSD License
 */

class EontechModBpostOrderBoxCustomsInfo
{
	/**
	 * @var int
	 */
	private $parcel_value;

	/**
	 * @var string
	 */
	private $content_description;

	/**
	 * @var string
	 */
	private $shipment_type;

	/**
	 * @var string
	 */
	private $parcel_return_instructions;

	/**
	 * @var bool
	 */
	private $private_address;

	/**
	 * @var string
	 */
	private $currency;

	/**
	 * @var string
	 */
	private $amt_postage_paid_by_addresse;

	/**
	 * @param int $parcel_value
	 */
	public function setParcelValue($parcel_value)
	{
		$this->parcel_value = $parcel_value;
	}

	/**
	 * @return int
	 */
	public function getParcelValue()
	{
		return $this->parcel_value;
	}

	/**
	 * @param string $content_description
	 * @throws EontechModException
	 */
	public function setContentDescription($content_description)
	{
		$length = 50;
		if (mb_strlen($content_description) > $length)
			throw new EontechModException(sprintf('Invalid length, maximum is %1$s.', $length));

		$this->content_description = $content_description;
	}

	/**
	 * @return string
	 */
	public function getContentDescription()
	{
		return $this->content_description;
	}

	/**
	 * @param string $shipment_type
	 * @throws EontechModException
	 */
	public function setShipmentType($shipment_type)
	{
		$shipment_type = \Tools::strtoupper($shipment_type);

		if (!in_array($shipment_type, self::getPossibleShipmentTypeValues()))
			throw new EontechModException(
				sprintf(
					'Invalid value, possible values are: %1$s.',
					implode(', ', self::getPossibleShipmentTypeValues())
				)
			);

		$this->shipment_type = $shipment_type;
	}

	/**
	 * @return string
	 */
	public function getShipmentType()
	{
		return $this->shipment_type;
	}

	/**
	 * @return array
	 */
	public static function getPossibleShipmentTypeValues()
	{
		return array(
			'SAMPLE',
			'GIFT',
			'DOCUMENTS',
			'OTHER',
			'GOODS',
		);
	}

	/**
	 * @param string $parcel_return_instructions
	 * @throws EontechModException
	 */
	public function setParcelReturnInstructions($parcel_return_instructions)
	{
		$parcel_return_instructions = \Tools::strtoupper($parcel_return_instructions);

		if (!in_array($parcel_return_instructions, self::getPossibleParcelReturnInstructionValues()))
			throw new EontechModException(
				sprintf(
					'Invalid value, possible values are: %1$s.',
					implode(', ', self::getPossibleParcelReturnInstructionValues())
				)
			);

		$this->parcel_return_instructions = $parcel_return_instructions;
	}

	/**
	 * @return string
	 */
	public function getParcelReturnInstructions()
	{
		return $this->parcel_return_instructions;
	}

	/**
	 * @return array
	 */
	public static function getPossibleParcelReturnInstructionValues()
	{
		return array(
			'RTA',
			'RTS',
			'ABANDONED',
		);
	}

	/**
	 * @param boolean $private_address
	 */
	public function setPrivateAddress($private_address)
	{
		$this->private_address = $private_address;
	}

	/**
	 * @return boolean
	 */
	public function getPrivateAddress()
	{
		return $this->private_address;
	}

	/**
	 * @param string $currency
	 * @throws EontechModException
	 */
	public function setCurrency($currency)
	{
		$currency = \Tools::strtoupper($currency);

		if (!in_array($currency, self::getPossibleCurrencyValues()))
			throw new EontechModException(
				sprintf(
					'Invalid value, possible values are: %1$s.',
					implode(', ', self::getPossibleCurrencyValues())
				)
			);

		$this->currency = $currency;
	}

	/**
	 * @return string
	 */
	public function getCurrency()
	{
		return $this->currency;
	}

	/**
	 * @return array
	 */
	public static function getPossibleCurrencyValues()
	{
		return array(
			'EUR',
			'GBP',
			'USD',
			'CNY',
		);
	}

	/**
	 * @param string $amount_paid
	 * @throws EontechModException
	 */
	public function setAmtPostagePaidByAddresse($amount_paid)
	{
		$amount_paid = round($amount_paid, 2);

		if ($amount_paid < 0 || $amount_paid > 999.99)
			throw new EontechModException(
				sprintf('Invalid value, should be between: 0 and 999.99')
			);

		$this->amt_postage_paid_by_addresse = (string)number_format($amount_paid, 2, '.', '');
	}

	/**
	 * @return string
	 */
	public function getAmtPostagePaidByAddresse()
	{
		return $this->amt_postage_paid_by_addresse;
	}

	/**
	 * Return the object as an array for usage in the XML
	 *
	 * @param  \DomDocument $document
	 * @param  string	   $prefix
	 * @return \DomElement
	 */
	public function toXML(\DOMDocument $document, $prefix = null)
	{
		$tag_name = 'customsInfo';
		if ($prefix !== null)
			$tag_name = $prefix.':'.$tag_name;

		$customs_info = $document->createElement($tag_name);

		if ($this->getParcelValue() !== null)
		{
			$tag_name = 'parcelValue';
			if ($prefix !== null)
				$tag_name = $prefix.':'.$tag_name;
			$customs_info->appendChild(
				$document->createElement(
					$tag_name,
					$this->getParcelValue()
				)
			);
		}
		if ($this->getContentDescription() !== null)
		{
			$tag_name = 'contentDescription';
			if ($prefix !== null)
				$tag_name = $prefix.':'.$tag_name;
			$customs_info->appendChild(
				$document->createElement(
					$tag_name,
					$this->getContentDescription()
				)
			);
		}
		if ($this->getShipmentType() !== null)
		{
			$tag_name = 'shipmentType';
			if ($prefix !== null)
				$tag_name = $prefix.':'.$tag_name;
			$customs_info->appendChild(
				$document->createElement(
					$tag_name,
					$this->getShipmentType()
				)
			);
		}
		if ($this->getParcelReturnInstructions() !== null)
		{
			$tag_name = 'parcelReturnInstructions';
			if ($prefix !== null)
				$tag_name = $prefix.':'.$tag_name;
			$customs_info->appendChild(
				$document->createElement(
					$tag_name,
					$this->getParcelReturnInstructions()
				)
			);
		}
		if ($this->getPrivateAddress() !== null)
		{
			$tag_name = 'privateAddress';
			if ($prefix !== null)
				$tag_name = $prefix.':'.$tag_name;
			if ($this->getPrivateAddress())
				$value = 'true';
			else
				$value = 'false';
			$customs_info->appendChild(
				$document->createElement(
					$tag_name,
					$value
				)
			);
		}
		if ($this->getCurrency() !== null)
		{
			$tag_name = 'currency';
			if ($prefix !== null)
				$tag_name = $prefix.':'.$tag_name;
			$customs_info->appendChild(
				$document->createElement(
					$tag_name,
					$this->getCurrency()
				)
			);
		}
		if ($this->getAmtPostagePaidByAddresse() !== null)
		{
			$tag_name = 'amtPostagePaidByAddresse';
			if ($prefix !== null)
				$tag_name = $prefix.':'.$tag_name;
			$customs_info->appendChild(
				$document->createElement(
					$tag_name,
					$this->getAmtPostagePaidByAddresse()
				)
			);
		}

		return $customs_info;
	}

	/**
	 * @param  \SimpleXMLElement $xml
	 * @return EontechModBpostOrderBoxCustomsInfo
	 */
	public static function createFromXML(\SimpleXMLElement $xml)
	{
		$customs_info = new EontechModBpostOrderBoxCustomsInfo();

		if (isset($xml->parcelValue) && $xml->parcelValue != '')
			$customs_info->setParcelValue((int)$xml->parcelValue);
		if (isset($xml->contentDescription) && $xml->contentDescription != '')
			$customs_info->setContentDescription((string)$xml->contentDescription);
		if (isset($xml->shipmentType) && $xml->shipmentType != '')
			$customs_info->setShipmentType((string)$xml->shipmentType);
		if (isset($xml->parcelReturnInstructions) && $xml->parcelReturnInstructions != '')
			$customs_info->setParcelReturnInstructions((string)$xml->parcelReturnInstructions);
		if (isset($xml->privateAddress) && $xml->privateAddress != '')
			$customs_info->setPrivateAddress(((string)$xml->privateAddress == 'true'));
		if (isset($xml->currency) && $xml->currency != '')
			$customs_info->setCurrency((string)$xml->currency);
		if (isset($xml->amtPostagePaidByAddresse) && $xml->amtPostagePaidByAddresse != '')
			$customs_info->setAmtPostagePaidByAddresse((string)$xml->amtPostagePaidByAddresse);

		return $customs_info;
	}
}
