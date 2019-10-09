<?php

namespace Ligacom\Feed\Export\Xml\Tag;

use Ligacom\Feed;

class GSalePrice extends Price
{
	public function getDefaultParameters()
	{
		return [
		    'id' => ':g:sale_price',
		    'name' => 'xmlns:g:sale_price'
            ]
            + parent::getDefaultParameters();
	}

	public function validate($value, array $context, $siblingsValues = null, Feed\Result\XmlNode $nodeResult = null, $settings = null)
	{
		$result = parent::validate($value, $context, $siblingsValues, $nodeResult);

		if ($result)
		{
			$priceValue = $this->getTagValues($siblingsValues, 'price', false);

			if (!isset($priceValue['VALUE']) || (float)$priceValue['VALUE'] >= (float)$value)
			{
				$result = false; // is not error, silent skip element
			}
		}

		return $result;
	}

	public function getSourceRecommendation(array $context = [])
	{
		$result = parent::getSourceRecommendation($context);

		foreach ($result as &$recommendation)
		{
			$recommendation['FIELD'] = str_replace('.DISCOUNT_VALUE', '.VALUE', $recommendation['FIELD']);
		}
		unset($recommendation);

		return $result;
	}
}