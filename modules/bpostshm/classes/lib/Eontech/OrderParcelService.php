<?php
/**
 * OrderParcelService class
 *
 * @author    Serge <serge@stigmi.eu>
 * @version   1.65.0
 * @copyright Copyright (c), Eontech.net All rights reserved.
 * @license   BSD License
 */

if (!defined('_PS_VERSION_'))
	exit;

class EontechOrderParcelService extends EontechBaseObject
{
	const MAX_WEIGHT = 30000;
	
	const ERR_INVALID = 3;

	protected $ps_order = false;
	protected $grams_ratio = false;

	protected $parcels = false;
	protected $box_index = 0;
	protected $ret_index = 0;
	protected $num_parcels = 0;

	protected function __construct($ps_order = null, $raise_exceptions = false)
	{
		parent::__construct(true, $raise_exceptions);

		if (Validate::isLoadedObject($ps_order))
			$this->ps_order = $ps_order;
		else
			$this->setError('Invalid order', ERR_INVALID);
	}

	public function getOrder()
	{
		return $this->ps_order;
	}

	public static function fromOrder($ps_order = null, $raise_exceptions = false)
	{
		return new self($ps_order, (bool)$raise_exceptions);
	}

	public static function fromOrderID($id = 0, $raise_exceptions = false)
	{
		$ps_order = (int)$id > 0 ? new Order((int)$id) : null;

		return new self($ps_order, (bool)$raise_exceptions);
	}

	protected function getProducts()
	{
		$products = false;
		if ($this->isValid())
			$products = $this->ps_order->getProducts();

		return $products;
	}

	protected function getProductWeights()
	{
		$pw = array();

		if ($products = $this->getProducts())
		{
			foreach ($products as $idx => $product)
				for ($q = (int)$product['product_quantity']; $q; $q--)
					if (($weight_grams = $this->inGrams($product['product_weight'])) > self::MAX_WEIGHT)
					{
						// throw new Exception(printf('Product id: %d exceeds maximum allowed weight', $product['id']));
						$this->setError(printf('Product id: %d exceeds maximum allowed weight', $product['id']));
						break 2;
					}
					else
						$pw[] = [$weight_grams, $idx];

			arsort($pw);

		}

		return $pw;
	}

	public function getMaxLabels()
	{
		$max_labels = 0;
		if ($this->isValid())
		{
			$quantities = array_column($this->ps_order->getProducts(), 'product_quantity');
			$max_labels = array_sum($quantities);
		}

		return (int)$max_labels;
	}

	public function getCurrencyIso()
	{
		$iso = false;
		if ($this->isValid())
		{
			$currency = Currency::getCurrency((int)$this->ps_order->id_currency);
			$iso = (string)$currency['iso_code'];
			if (! in_array($iso, EontechModBpostOrderBoxCustomsInfo::getPossibleCurrencyValues()))
				$iso = 'EUR';
		}

		return $iso;
	}

	public function getPostagePaidPerParcel()
	{
		$postage = 0.0;
		if ($this->isValid())
		{
			$postage = (float)$this->ps_order->total_shipping_tax_incl;
			if ($this->num_parcels)
				$postage = round($postage / $this->num_parcels, 2);
		}

		return $postage;
	}

	public function getProductLines()
	{
		$lines = array();
		if ($products = $this->getProducts())
			foreach ($products as $product)
				$lines[] = array(
					'name' => $product['product_name'],
					'quantity' => (int)$product['product_quantity'],
				);

		return $lines;
	}

	public function getNumParcels()
	{
		return $this->num_parcels;
	}

	public function setParcels($num_labels = 0)
	{
		$this->resetParcels();
		$prod_weights = $this->getProductWeights();
		if ($this->hasError())
			return false;

		$pkeys = array_keys($prod_weights);
		$pw = array_column($prod_weights, 0);
		$max = (int)count($pw);
		$total = (int)array_sum($pw);
		$min_boxes = (int)ceil($total / self::MAX_WEIGHT);
		// $remain = (int)($total % MAX_WEIGHT);
		$num_parcels = min(max($min_boxes, $num_labels), $max);
		//
		$bags = [];
		$boxes = [];
		for ($i = 0; $i < $num_parcels; $i++) {
		    $bags[$i] = [];
		    $boxes[$i] = [];
		}
		//
		foreach ($bags as $idx => &$bag)
		    if (empty($bag))
		    {
		       $bag[] = $pw[$idx];
		       $pi = $prod_weights[$pkeys[$idx]][1];  // product-index
		       $bq = empty($boxes[$idx][$pi]) ? 1 : $boxes[$idx][$pi] + 1;
		       $boxes[$idx][$pi] = $bq;
		       unset($pw[$idx]);
		       unset($pkeys[$idx]);
		    }
		//
		foreach ($pw as $iw => $item)
		    foreach ($bags as $ib => &$bag)
		    {
		        $bag_total = (int)array_sum($bag);
		        if ($bag_total + $item <= self::MAX_WEIGHT) {
		            $bag[] = $item;
		            $pi = $prod_weights[$pkeys[$iw]][1];  // product-index
		            $bq = empty($boxes[$ib][$pi]) ? 1 : $boxes[$ib][$pi] + 1;
		            $boxes[$ib][$pi] = $bq;
		            break;
		        }
		    }

		$parcels = [];
		$products = $this->getProducts();
		foreach ($boxes as $idx => $box)
		{
			$parcels[$idx] = array();
			$tw = $tv = 0;
			foreach ($box as $pi => $qty)
			{
				$product = $products[$pi];
				$box_weight = $this->inGrams($product['product_weight']) * $qty;
				// $box_value = (int)round($product['product_price'] * 100, 2) * $qty;
				$box_value = (int)round($product['unit_price_tax_incl'] * 100, 2) * $qty;
				$parcels[$idx]['contents'][] = array(
					'qty' => $qty,
					'value' => $box_value,
					'name' => $product['product_name'],
					'weight' => $box_weight,
					'id_product' => $product['id_product'],
					'product_index' => $pi,
				);
				$tw += $box_weight;
				$tv += $box_value;
			}
			$parcels[$idx]['total_weight'] = $tw;
			$parcels[$idx]['total_value'] = $tv;
		}

		$this->parcels = $parcels;
		$this->num_parcels = (int)count($parcels);

		return $this->isValid();
	}

	public function getNextParcel($is_retour = false)
	{
		$parcel = false;
		if ($this->num_parcels && $this->isValid())
		{
			$idx = $this->box_index;
			if ((bool)$is_retour)
			{
				$idx = $this->ret_index;
				$this->ret_index += 1;
				$this->ret_index %= $this->num_parcels;
			}
			else
			{
				$this->box_index += 1;
				$this->box_index %= $this->num_parcels;
			}
			$parcel = $this->parcels[$idx];
		}

		return $parcel;
	}

	protected function resetParcels()
	{
		$this->parcels = false;
		$this->box_index = 0;
		$this->ret_index = 0;
		$this->num_parcels = 0;
	}

	protected function inGrams($weight = 0)
	{
		if (!is_numeric($weight) || 0.0 == (float)$weight)
			return 10;

		if (false === $this->grams_ratio)
			switch (Tools::strtolower(Configuration::get('PS_WEIGHT_UNIT')))
			{
				case 'kg':
					$this->grams_ratio = 1000;
					break;

				case 'g':
					$this->grams_ratio = 1;
					break;

				case 'lbs':
				case 'lb':
					$this->grams_ratio = 453.592;
					break;

				case 'oz':
					$this->grams_ratio = 28.34952;
					break;

				default:
					$this->grams_ratio = 1000;
					break;
			}	
		
		return (int)ceil((float)$weight * $this->grams_ratio);
	}

	public static function getWeightGrams($weight = 0)
	{
		if (!is_numeric($weight) || 0.0 == (float)$weight)
			return 10;

		$weight = (float)$weight;
		$weight_unit = Tools::strtolower(Configuration::get('PS_WEIGHT_UNIT'));
		switch ($weight_unit)
		{
			case 'kg':
				$weight *= 1000;
				break;

			case 'g':
				break;

			case 'lbs':
			case 'lb':
				$weight *= 453.592;
				break;

			case 'oz':
				$weight *= 28.34952;
				break;

			default:
				$weight = 1000;
				break;
		}

		return (int)ceil($weight);
	}
}
