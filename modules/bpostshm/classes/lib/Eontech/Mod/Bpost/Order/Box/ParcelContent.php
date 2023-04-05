<?php
/**
 * bPost CustomsInfo class
 *
 * @author    Serge Jamasb <serge@stigmi.eu>
 * @version   3.4.0
 * @copyright Copyright (c), Eontech.net. All rights reserved.
 * @license   BSD License
 */

class EontechModBpostOrderBoxParcelContent
{
	/**
	 * @var int
	 */
	private $number_of_item_type;

	/**
	 * @var int
	 */
	private $value_of_item;

	/**
	 * @var string
	 */
	private $item_description;

	/**
	 * @var int
	 */
	private $netto_weight;

	/**
	 * @var string
	 */
	private $hs_tariff_code;

	/**
	 * @var string
	 */
	private $origin_of_goods;

	/**
	 * @param int $quantity
	 */
	public function setNumberOfItemType($quantity)
	{
		$this->number_of_item_type = (int)$quantity;
	}

	/**
	 * @return int
	 */
	public function getNumberOfItemType()
	{
		return $this->number_of_item_type;
	}

	/**
	 * @param int $value
	 */
	public function setValueOfItem($value)
	{
		$this->value_of_item = (int)$value;
	}

	/**
	 * @return int
	 */
	public function getValueOfItem()
	{
		return $this->value_of_item;
	}

	/**
	 * @param string $item_description
	 * @throws EontechModException
	 */
	public function setItemDescription($item_description)
	{
		$length = 30;
		if (mb_strlen($item_description) > $length)
			throw new EontechModException(sprintf('Invalid length, maximum is %1$s.', $length));

		$this->item_description = (string)$item_description;
	}

	/**
	 * @return string
	 */
	public function getItemDescription()
	{
		return $this->item_description;
	}

	/**
	 * @param int $net_weight
	 */
	public function setNettoWeight($net_weight)
	{
		$this->netto_weight = (int)$net_weight;
	}

	/**
	 * @return int
	 */
	public function getNettoWeight()
	{
		return $this->netto_weight;
	}

	/**
	 * @param string $hs_code
	 * @throws EontechModException
	 */
	public function setHsTariffCode($hs_code)
	{
		// $hs_code = (int)$hs_code;
		// if ($hs_code < 100000 || $hs_code > 999999999)
		// 	throw new EontechModException(sprintf('Invalid HS Tariff code: %1$s.', $hs_code));
		if (1 !== preg_match('/^\d{4,9}$/', (string)$hs_code))
			throw new EontechModException(sprintf('Invalid HS Tariff code: %1$s.', $hs_code));

		$this->hs_tariff_code = (string)$hs_code;
	}

	/**
	 * @return string
	 */
	public function getHsTariffCode()
	{
		return $this->hs_tariff_code;
	}

	/**
	 * @param string $country_iso
	 * @throws EontechModException
	 */
	public function setOriginOfGoods($country_iso)
	{
		// $country_iso = Tools::strtoupper($country_iso);
		if (2 !== mb_strlen($country_iso))
			throw new EontechModException(sprintf('Invalid country iso: %1$s.', $country_iso));

		$this->origin_of_goods = (string)$country_iso;
	}

	/**
	 * @return string
	 */
	public function getOriginOfGoods()
	{
		return $this->origin_of_goods;
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
		$tag_name = 'parcelContent';
		if ($prefix !== null)
			$tag_name = $prefix.':'.$tag_name;

		$parcel_content = $document->createElement($tag_name);

		if ($this->getNumberOfItemType() !== null)
		{
			$tag_name = 'numberOfItemType';
			if ($prefix !== null)
				$tag_name = $prefix.':'.$tag_name;
			$parcel_content->appendChild(
				$document->createElement(
					$tag_name,
					$this->getNumberOfItemType()
				)
			);
		}
		if ($this->getValueOfItem() !== null)
		{
			$tag_name = 'valueOfItem';
			if ($prefix !== null)
				$tag_name = $prefix.':'.$tag_name;
			$parcel_content->appendChild(
				$document->createElement(
					$tag_name,
					$this->getValueOfItem()
				)
			);
		}
		if ($this->getItemDescription() !== null)
		{
			$tag_name = 'itemDescription';
			if ($prefix !== null)
				$tag_name = $prefix.':'.$tag_name;
			$parcel_content->appendChild(
				$document->createElement(
					$tag_name,
					$this->getItemDescription()
				)
			);
		}
		if ($this->getNettoWeight() !== null)
		{
			$tag_name = 'nettoWeight';
			if ($prefix !== null)
				$tag_name = $prefix.':'.$tag_name;
			$parcel_content->appendChild(
				$document->createElement(
					$tag_name,
					$this->getNettoWeight()
				)
			);
		}
		if ($this->getHsTariffCode() !== null)
		{
			$tag_name = 'hsTariffCode';
			if ($prefix !== null)
				$tag_name = $prefix.':'.$tag_name;
			$parcel_content->appendChild(
				$document->createElement(
					$tag_name,
					$this->getHsTariffCode()
				)
			);
		}
		if ($this->getOriginOfGoods() !== null)
		{
			$tag_name = 'originOfGoods';
			if ($prefix !== null)
				$tag_name = $prefix.':'.$tag_name;
			$parcel_content->appendChild(
				$document->createElement(
					$tag_name,
					$this->getOriginOfGoods()
				)
			);
		}

		return $parcel_content;
	}

	/**
	 * @param  \SimpleXMLElement $xml
	 * @return EontechModBpostOrderBoxParcelContent
	 */
	public static function createFromXML(\SimpleXMLElement $xml)
	{
		$parcel_content = new EontechModBpostOrderBoxParcelContent();

		if (isset($xml->numberOfItemType) && $xml->numberOfItemType != '')
			$parcel_content->setNumberOfItemType((int)$xml->numberOfItemType);
		if (isset($xml->valueOfItem) && $xml->valueOfItem != '')
			$parcel_content->setValueOfItem((int)$xml->valueOfItem);
		if (isset($xml->itemDescription) && $xml->itemDescription != '')
			$parcel_content->setItemDescription((string)$xml->itemDescription);
		if (isset($xml->nettoWeight) && $xml->nettoWeight != '')
			$parcel_content->setNettoWeight((int)$xml->nettoWeight);
		if (isset($xml->hsTariffCode) && $xml->hsTariffCode != '')
			$parcel_content->setHsTariffCode((string)$xml->hsTariffCode);
		if (isset($xml->originOfGoods) && $xml->originOfGoods != '')
			$parcel_content->setOriginOfGoods((string)$xml->originOfGoods);

		return $parcel_content;
	}
}
