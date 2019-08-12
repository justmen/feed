<?php

namespace Ligacom\Feed\Export\Xml\Tag;

use Ligacom\Feed;

class CategoryId extends Base
{
	public function getDefaultParameters()
	{
		return [
			'name' => 'categoryId',
			'value_type' => Feed\Type\Manager::TYPE_CATEGORY
		];
	}

	public function getSourceRecommendation(array $context = [])
	{
		$result = [
			[
				'TYPE' => Feed\Export\Entity\Manager::TYPE_IBLOCK_ELEMENT_FIELD,
				'FIELD' => 'IBLOCK_SECTION_ID'
			]
		];

		if (isset($context['OFFER_IBLOCK_ID']))
		{
			$result[] = [
				'TYPE' => Feed\Export\Entity\Manager::TYPE_IBLOCK_OFFER_FIELD,
				'FIELD' => 'IBLOCK_SECTION_ID'
			];
		}

		return $result;
	}
}