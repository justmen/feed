<?php

namespace Ligacom\Feed\Export\Xml\Tag;

use Bitrix\Main;
use Ligacom\Feed;
use Bitrix\Iblock;

class GBrand extends Base
{
	public function getDefaultParameters()
	{
		return [
			'id' => 'g:brand',
			'name' => 'xmlns:g:brand',
		];
	}

	public function getSourceRecommendation(array $context = [])
	{
		$returnList = [];

		if (!empty($context['IBLOCK_ID']) && Main\Loader::includeModule('iblock'))
		{
			$query = Iblock\PropertyTable::getList([
				'filter' => [
					'=IBLOCK_ID' => $context['IBLOCK_ID'],
					'@CODE' => [
						'MANUFACTURER',
						'VENDOR',
						'BRAND'
					]
				],
				'select' => [
					'ID'
				]
			]);

			while ($prop = $query->fetch())
			{
				$returnList[] = [
					'TYPE' => Feed\Export\Entity\Manager::TYPE_IBLOCK_ELEMENT_PROPERTY,
					'FIELD' => $prop['ID']
				];
			}
		}

		return $returnList;
	}
}