<?php

namespace Ligacom\Feed\Export\Xml\Tag;

use Ligacom\Feed;

class Dimensions extends Base
{
	public function getDefaultParameters()
	{
		return [
			'name' => 'dimensions',
			'value_type' => Feed\Type\Manager::TYPE_DIMENSIONS
		];
	}

	public function getSourceRecommendation(array $context = [])
	{
		return [
			[
				'TYPE' => Feed\Export\Entity\Manager::TYPE_CATALOG_PRODUCT,
				'FIELD' => 'YM_SIZE'
			]
		];
	}
}