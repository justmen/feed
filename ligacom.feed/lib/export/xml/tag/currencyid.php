<?php

namespace Ligacom\Feed\Export\Xml\Tag;

use Ligacom\Feed;
use Bitrix\Currency;

class CurrencyId extends Base
{
	public function getDefaultParameters()
	{
		return [
			'name' => 'currencyId',
			'value_type' => Feed\Type\Manager::TYPE_CURRENCY
		];
	}

	public function getSourceRecommendation(array $context = [])
	{
		$result = [];

		if ($context['HAS_CATALOG'])
		{
			$result = [
				[
					'TYPE' => Feed\Export\Entity\Manager::TYPE_CATALOG_PRICE,
					'FIELD' => 'MINIMAL.CURRENCY'
				],
				[
					'TYPE' => Feed\Export\Entity\Manager::TYPE_CATALOG_PRICE,
					'FIELD' => 'OPTIMAL.CURRENCY'
				],
				[
					'TYPE' => Feed\Export\Entity\Manager::TYPE_CATALOG_PRICE,
					'FIELD' => 'BASE.CURRENCY'
				]
			];
		}

		return $result;
	}
}