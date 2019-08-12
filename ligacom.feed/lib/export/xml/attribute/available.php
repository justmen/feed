<?php

namespace Ligacom\Feed\Export\Xml\Attribute;

use Ligacom\Feed;

class Available extends Base
{
	public function getDefaultParameters()
	{
		return [
			'name' => 'available',
			'value_type' => Feed\Type\Manager::TYPE_BOOLEAN
		];
	}

	public function getSourceRecommendation(array $context = [])
	{
		$result = [];

		if ($context['HAS_CATALOG'])
		{
		    $result[] = [
				'TYPE' => Feed\Export\Entity\Manager::TYPE_CATALOG_PRODUCT,
				'FIELD' => 'AVAILABLE'
			];
		}

		return $result;
	}
}