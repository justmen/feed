<?php

namespace Ligacom\Feed\Export\Xml\Tag;

use Ligacom\Feed;

class Price extends Base
{
	public function getDefaultParameters()
	{
		return [
			'name' => 'price',
			'value_type' => Feed\Type\Manager::TYPE_NUMBER,
			'value_positive' => true
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