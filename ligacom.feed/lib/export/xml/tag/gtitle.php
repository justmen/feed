<?php

namespace Ligacom\Feed\Export\Xml\Tag;

use Ligacom\Feed;

class GTitle extends Base
{
	public function getDefaultParameters()
	{
		return [
			'id' => 'g:title',
			'name' => 'xmlns:g:title'
		];
	}

	public function getSourceRecommendation(array $context = [])
	{
		$result = [
			[
				'TYPE' => Feed\Export\Entity\Manager::TYPE_IBLOCK_ELEMENT_FIELD,
				'FIELD' => 'NAME'
			]
		];

		if (isset($context['OFFER_IBLOCK_ID']))
		{
			$result[] = [
				'TYPE' => Feed\Export\Entity\Manager::TYPE_IBLOCK_OFFER_FIELD,
				'FIELD' => 'NAME'
			];
		}

		return $result;
	}
}