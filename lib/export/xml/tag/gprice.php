<?php

namespace Ligacom\Feed\Export\Xml\Tag;

use Ligacom\Feed;

class GPrice extends Base
{
	public function getDefaultParameters()
	{
		return [
		    'id' => 'g:price',
			'name' => 'xmlns:g:price',
			'value_type' => Feed\Type\Manager::TYPE_NUMBER,
			'value_positive' => true,
            'added_value' => Feed\Export\Xml\Tag\CurrencyId::class
		];
	}


	public function getSourceRecommendation(array $context = [])
	{
		$result = [];

		if ($context['HAS_CATALOG'])
		{
			$result[] = [
				'TYPE' => Feed\Export\Entity\Manager::TYPE_CATALOG_PRICE,
				'FIELD' => 'MINIMAL.DISCOUNT_VALUE'
			];

			$result[] = [
				'TYPE' => Feed\Export\Entity\Manager::TYPE_CATALOG_PRICE,
				'FIELD' => 'OPTIMAL.DISCOUNT_VALUE'
			];

			$result[] = [
				'TYPE' => Feed\Export\Entity\Manager::TYPE_CATALOG_PRICE,
				'FIELD' => 'BASE.DISCOUNT_VALUE'
			];
		}

		return $result;
	}
}