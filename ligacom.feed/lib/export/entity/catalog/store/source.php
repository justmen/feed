<?php

namespace Ligacom\Feed\Export\Entity\Catalog\Store;

use Ligacom\Feed;
use Bitrix\Main;
use Bitrix\Catalog;

Main\Localization\Loc::loadMessages(__FILE__);

class Source extends Feed\Export\Entity\Reference\Source
{
	public function isSelectable()
	{
		return false;
	}

	public function getQuerySelect($select)
	{
		return []; // no support
	}

	public function isFilterable()
	{
		return true;
	}

	public function getQueryFilter($filter, $select)
	{
		$result = [
			'CATALOG' => []
		];

		foreach ($filter as $filterItem)
		{
		    $this->pushQueryFilter($result['CATALOG'], $filterItem['COMPARE'], 'CATALOG_STORE_' . $filterItem['FIELD'], $filterItem['VALUE']);
		}

		return $result;
	}

	public function getElementListValues($elementList, $parentList, $select, $queryContext, $sourceValues)
	{
		return []; // no support
	}

	public function getFields(array $context = [])
	{
		$result = [];

		if ($context['HAS_CATALOG'] && Main\Loader::includeModule('catalog'))
		{
			$langPrefix = $this->getLangPrefix();

			$queryStores = \CCatalogStore::GetList(
				[],
				[ 'ACTIVE' => 'Y' ],
				false,
				false,
				[ 'ID', 'TITLE', 'ADDRESS' ]
			);

			while ($store = $queryStores->Fetch())
			{
				$storeTitle = ($store['TITLE'] ?: $store['ADDRESS'] ?: $store['ID']);

				$result['AMOUNT_' . $store['ID']] = [
					'ID' => 'AMOUNT_' . $store['ID'],
					'VALUE' => Feed\Config::getLang($langPrefix . 'FIELD_AMOUNT', [ '#STORE_NAME#' => $storeTitle ]),
					'TYPE' => Feed\Export\Entity\Data::TYPE_NUMBER,
					'FILTERABLE' => true,
					'SELECTABLE' => false
				];
			}
		}

		return $result;
	}

	protected function getLangPrefix()
	{
		return 'CATALOG_STORE_';
	}
}