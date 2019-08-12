<?php

namespace Ligacom\Feed\Export\Xml\Tag;

use Ligacom\Feed;
use Bitrix\Main;
use Bitrix\Catalog;

class Vat extends Base
{
	public function getDefaultParameters()
	{
		return [
			'name' => 'vat',
			'value_type' => Feed\Type\Manager::TYPE_VAT
		];
	}

	public function getSourceRecommendation(array $context = [])
	{
		$result = [];

		if ($context['HAS_CATALOG'])
		{
			$result[] = [
				'TYPE' => Feed\Export\Entity\Manager::TYPE_CATALOG_PRODUCT,
				'FIELD' => 'VAT'
			];
		}

		return $result;
	}
}