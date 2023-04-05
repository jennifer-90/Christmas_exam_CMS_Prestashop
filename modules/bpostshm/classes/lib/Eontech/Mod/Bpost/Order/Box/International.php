<?php
/**
 * bPost International base class
 *
 * @author    Serge Jamasb <serge@stigmi.eu>
 * @version   3.4.0
 * @copyright Copyright (c), Eontech.net. All rights reserved.
 * @license   BSD License
 */

abstract class EontechModBpostOrderBoxInternational
{
	const TAG_TYPE = 'international';

	/**
	 * @var string
	 */
	protected $product;

	/**
	 * @var array
	 */
	protected $options;

	/**
	 * @var EontechModBpostOrderReceiver
	 */
	protected $receiver;

	/**
	 * @var int
	 */
	protected $parcel_weight;

	/**
	 * @var EontechModBpostOrderBoxCustomsInfo
	 */
	protected $customs_info;

	/**
	 * Additional elements within descendants
	 * 
	 * @param  \DOMDocument $document
	 * @param  \DOMElement  $type_element
	 * @return \DOMElement
	 */
	abstract protected function alsoToXML(\DOMDocument $document, \DOMElement $type_element);

	/**
	 * @param  \SimpleXMLElement $xml_elm
	 * @return Derivative instance
	 */
	abstract protected function createAlsoFromXML(\SimpleXMLElement $xml_elm);

	/**
	 * @remark would be abstract (but for an idiotic E_STRICT in php < 7.0)
	 * abstract protected static function getPossibleProductValues()
	 * @return array
	 */
	protected static function getPossibleProductValues()
	{
		return array();
	}

	/**
	 * ditto.. could be abstract
	 * @return array
	 */
	protected static function getSupportedOptions()
	{
		return array('infoDistributed');
	}

	/**
	 * @param string $product
	 * @throws EontechModException
	 */
	public function setProduct($product)
	{
		if (!in_array($product, static::getPossibleProductValues()))
			throw new EontechModException(
				sprintf(
					'Invalid value, possible values are: %1$s.',
					implode(', ', static::getPossibleProductValues())
				)
			);

		$this->product = $product;
	}

	/**
	 * @return string
	 */
	public function getProduct()
	{
		return $this->product;
	}

	/**
	 * @param EontechModBpostOrderBoxCustomsInfo $customs_info
	 */
	public function setCustomsInfo($customs_info)
	{
		$this->customs_info = $customs_info;
	}

	/**
	 * @return EontechModBpostOrderBoxCustomsInfo
	 */
	public function getCustomsInfo()
	{
		return $this->customs_info;
	}

	/**
	 * @param array $options
	 */
	public function setOptions($options)
	{
		$this->options = $options;
	}

	/**
	 * @return array
	 */
	public function getOptions()
	{
		return $this->options;
	}

	/**
	 * @param EontechModBpostOrderBoxOption $option
	 */
	public function addOption(EontechModBpostOrderBoxOption $option)
	{
		$this->options[] = $option;
	}

	/**
	 * @param int $parcel_weight
	 */
	public function setParcelWeight($parcel_weight)
	{
		$this->parcel_weight = $parcel_weight;
	}

	/**
	 * @return int
	 */
	public function getParcelWeight()
	{
		return $this->parcel_weight;
	}

	/**
	 * @param EontechModBpostOrderReceiver $receiver
	 */
	public function setReceiver($receiver)
	{
		$this->receiver = $receiver;
	}

	/**
	 * @return EontechModBpostOrderReceiver
	 */
	public function getReceiver()
	{
		return $this->receiver;
	}

	/**
	 * Return the object as an array for usage in the XML
	 *
	 * @param  \DomDocument $document
	 * @param  string	   $prefix
	 * @param  string	   $type
	 * @return \DomElement
	 */
	public function toXML(\DOMDocument $document, $prefix = null, $type = null)
	{
		$tag_name = 'internationalBox';
		if ($prefix !== null)
			$tag_name = $prefix.':'.$tag_name;

		$international_box = $document->createElement($tag_name);
		//
		$prefix = 'international';
		$type = static::TAG_TYPE;
		$tag_prefix = ($prefix !== null) ? $prefix.':' : '';
		$type_element = $document->createElement($tag_prefix.$type);
		
		if ($this->getProduct() !== null)
		{
			$tag_name = $tag_prefix.'product';
			$type_element->appendChild(
				$document->createElement(
					$tag_name,
					$this->getProduct()
				)
			);
		}

		$options = $this->getOptions();
		if (!empty($options))
		{
			$options_element = $document->createElement($tag_prefix.'options');
			foreach ($options as $option)
				$options_element->appendChild($option->toXML($document));
			$type_element->appendChild($options_element);
		}

		if ($this->getReceiver() !== null)
			$type_element->appendChild($this->getReceiver()->toXML($document, $prefix));

		if ($this->getParcelWeight() !== null)
			$type_element->appendChild(
				$document->createElement(
					$tag_prefix.'parcelWeight',
					$this->getParcelWeight()
				)
			);

		if ($this->getCustomsInfo() !== null)
			$type_element->appendChild($this->getCustomsInfo()->toXML($document, $prefix));

		// additional elements
		$this->alsoToXML($document, $type_element);
		//
		$international_box->appendChild($type_element);

		return $international_box;
	}

	/**
	 * @param  \SimpleXMLElement $xml
	 * @return
	 */
	public static function createFromXML(\SimpleXMLElement $xml)
	{
		$intl_instance = new static();

		$intl_type = static::TAG_TYPE;
		$xml_elm = $xml->$intl_type;

		if (isset($xml_elm->product) && $xml_elm->product != '')
			$intl_instance->setProduct((string)$xml_elm->product);
		if (isset($xml_elm->options))
			foreach ($xml_elm->options as $option_data)
			{
				$option_data = $option_data->children('http://schema.post.be/shm/deepintegration/v3/common');

				// if (in_array($option_data->getName(), array('infoDistributed')))
				if (in_array($option_data->getName(), static::getSupportedOptions()))
					$option = EontechModBpostOrderBoxOptionMessaging::createFromXML($option_data);
				else
				{
					$class_name = 'EontechModBpostOrderBoxOption'.\Tools::ucfirst($option_data->getName());
					if (!method_exists($class_name, 'createFromXML'))
						throw new EontechModException('Not Implemented');
					$option = call_user_func(
						array($class_name, 'createFromXML'),
						$option_data
					);
				}

				$intl_instance->addOption($option);
			}
		if (isset($xml_elm->parcelWeight) && $xml_elm->parcelWeight != '')
			$intl_instance->setParcelWeight((int)$xml_elm->parcelWeight);
		if (isset($xml_elm->receiver))
		{
			$receiver_data = $xml_elm->receiver->children(
				'http://schema.post.be/shm/deepintegration/v3/common'
			);
			$intl_instance->setReceiver(
				EontechModBpostOrderReceiver::createFromXML($receiver_data)
			);
		}
		if (isset($xml_elm->customsInfo))
			$intl_instance->setCustomsInfo(EontechModBpostOrderBoxCustomsInfo::createFromXML($xml_elm->customsInfo));
		// additional elements
		$intl_instance->createAlsoFromXML($xml_elm);

		return $intl_instance;
	}
}
