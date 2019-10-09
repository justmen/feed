<?php

namespace Ligacom\Feed\Export\Entity\Catalog\Product;

use Ligacom\Feed;
use Bitrix\Main;
use Bitrix\Catalog;

Main\Localization\Loc::loadMessages(__FILE__);

class Source extends Feed\Export\Entity\Reference\Source
{
	public function getQuerySelect($select)
	{
		$result = [
			'CATALOG' => []
		];

		foreach ($select as $fieldName)
		{
			if ($fieldName === 'YM_SIZE')
			{
				$result['CATALOG'][] = 'CATALOG_LENGTH';
				$result['CATALOG'][] = 'CATALOG_WIDTH';
				$result['CATALOG'][] = 'CATALOG_HEIGHT';
			}
			else
			{
				$result['CATALOG'][] = 'CATALOG_' . $fieldName;
			}
		}

		return $result;
	}

	public function isFilterable()
	{
		return true;
	}

	public function getQueryFilter($filter, $select)
	{
		$result = [
			'ELEMENT' => [],
			'CATALOG' => []
		];

		foreach ($filter as $filterItem)
		{
			$sourceKey = 'CATALOG';

			if ($filterItem['FIELD'] === 'TYPE')
			{
				$sourceKey = 'ELEMENT';
			}

            $this->pushQueryFilter($result[$sourceKey], $filterItem['COMPARE'], 'CATALOG_' . $filterItem['FIELD'], $filterItem['VALUE']);
		}

		return $result;
	}

	public function getElementListValues($elementList, $parentList, $select, $queryContext, $sourceValues)
	{
		$result = [];

		if (!empty($elementList))
		{
			foreach ($elementList as $elementId => $element)
			{
				$result[$elementId] = [];

				foreach ($select as $fieldName)
				{
					$result[$elementId][$fieldName] = $this->getDisplayValue($element, $fieldName);
				}
			}
		}

		return $result;
	}

	public function getFields(array $context = [])
	{
		$result = [];

		if ($context['HAS_CATALOG'])
		{
			$result = $this->buildFieldsDescription([
				'WEIGHT' => [
					'TYPE' => Feed\Export\Entity\Data::TYPE_NUMBER
				],
				'LENGTH' => [
					'TYPE' => Feed\Export\Entity\Data::TYPE_NUMBER
				],
				'HEIGHT' => [
					'TYPE' => Feed\Export\Entity\Data::TYPE_NUMBER
				],
				'WIDTH' => [
					'TYPE' => Feed\Export\Entity\Data::TYPE_NUMBER
				],
				'YM_SIZE' => [
					'TYPE' => Feed\Export\Entity\Data::TYPE_STRING,
					'FILTERABLE' => false
				],
				'AVAILABLE' => [
					'TYPE' => Feed\Export\Entity\Data::TYPE_BOOLEAN
				],
				'QUANTITY' => [
					'TYPE' => Feed\Export\Entity\Data::TYPE_NUMBER
				],
				'MEASURE' => [
					'TYPE' => Feed\Export\Entity\Data::TYPE_STRING
				],
				'VAT' => [
					'TYPE' => Feed\Export\Entity\Data::TYPE_NUMBER
				],
				'TYPE' => [
					'TYPE' => Feed\Export\Entity\Data::TYPE_ENUM,
					'SELECTABLE' => false
				]
			]);
		}

		return $result;
	}

	public function getFieldEnum($field, array $context = [])
	{
		$result = null;

		switch ($field['ID'])
		{
			case 'TYPE':
				$result = $this->getCatalogProductTypes();
			break;

			default:
				$result = parent::getFieldEnum($field, $context);
			break;
		}

		return $result;
	}

	protected function getLangPrefix()
	{
		return 'CATALOG_PRODUCT_';
	}

	protected function getCatalogProductTypes()
	{
		$result = [];

		if (Main\Loader::includeModule('catalog'))
		{
			$types = [
				'TYPE_PRODUCT',
				'TYPE_SET',
				'TYPE_SKU'
			];

			foreach ($types as $type)
			{
				$constantName = '\CCatalogProduct::' . $type;

				if (defined($constantName))
				{
					$result[] = [
						'ID' => constant($constantName),
						'VALUE' => Feed\Config::getLang($this->getLangPrefix() . 'FIELD_TYPE_ENUM_' . $type)
					];
				}
			}
		}

		return $result;
	}

	protected function getDisplayValue($element, $fieldName)
	{
		$result = null;

		if ($fieldName === 'YM_SIZE')
		{
			$keys = [ 'CATALOG_LENGTH', 'CATALOG_WIDTH', 'CATALOG_HEIGHT' ];
			$values = [];
			$hasIsset = false;
			$measureRatio = 0.1; // bitrix mm => market cm

			foreach ($keys as $key)
			{
				$value = 0;

				if (isset($element[$key]))
				{
					$value = (float)$element[$key];

					if ($value > 0)
					{
						$value *= $measureRatio;
						$hasIsset = true;
					}
				}

				$values[] = $value;
			}

			if ($hasIsset)
			{
				$result = implode('/', $values);
			}
		}
		else
		{
			$elementKey = 'CATALOG_' . $fieldName;

			if (isset($element[$elementKey]))
			{
				$originalValue = $element[$elementKey];

				switch ($fieldName)
				{
					case 'WEIGHT':
						if ((float)$originalValue > 0)
						{
							$result = $originalValue;
						}
					break;

					default:
						$result = $originalValue;
					break;
				}
			}
		}

		return $result;
	}
}