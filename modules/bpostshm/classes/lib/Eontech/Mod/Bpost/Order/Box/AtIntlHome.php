<?php
/**
 * bPost AtIntlHome class
 *
 * @author    Serge Jamasb <serge@stigmi.eu>
 * @version   3.4.0
 * @copyright Copyright (c), Eontech.net. All rights reserved.
 * @license   BSD License
 */

class EontechModBpostOrderBoxAtIntlHome extends EontechModBpostOrderBoxInternational
{
	const TAG_TYPE = 'atIntlHome';

	/**
	 * @var array
	 */
	private $parcel_contents;

	/**
	 * @param array $parcel_contents
	 */
	public function setParcelContents($parcel_contents)
	{
		$this->parcel_contents = $parcel_contents;
	}

	/**
	 * @return array
	 */
	public function getParcelContents()
	{
		return $this->parcel_contents;
	}

	/**
	 * @param EontechModBpostOrderBoxParcelContent $parcel_content
	 */
	public function addParcelContent(EontechModBpostOrderBoxParcelContent $parcel_content)
	{
		$this->parcel_contents[] = $parcel_content;
	}

	/**
	 * @return array
	 */
	protected static function getPossibleProductValues()
	{
		return array(
			'bpack World Business',
			'bpack World Express Pro',
			'bpack Europe Business',
			'bpack World Easy Return',
		);
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
		$parcel_contents = $this->getParcelContents();
		if (!empty($parcel_contents))
		{
			//
			$prefix = 'international';
			$tag_prefix = $prefix.':';
			//
			$contents_element = $document->createElement($tag_prefix.'parcelContents');
			foreach ($parcel_contents as $content)
				$contents_element->appendChild($content->toXML($document, $prefix));
			$type_element->appendChild($contents_element);
		}
	}

	/**
	 * @param  \SimpleXMLElement $xml_elm
	 * @return Derivative instance
	 */
	protected function createAlsoFromXML(\SimpleXMLElement $xml_elm)
	{
		if (isset($xml_elm->parcelContents))
			foreach ($xml_elm->parcelContents as $parcel_content_data)
				$this->addParcelContent(EontechModBpostOrderBoxParcelContent::createFromXML($parcel_content_data));
	}
}
