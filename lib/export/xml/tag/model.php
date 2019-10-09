<?php

namespace Ligacom\Feed\Export\Xml\Tag;

use Ligacom\Feed;

class Model extends Base
{
	public function getDefaultParameters()
	{
		return [
			'name' => 'model'
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