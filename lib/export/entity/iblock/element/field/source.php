<?php

namespace Ligacom\Feed\Export\Entity\Iblock\Element\Field;

use Ligacom\Feed;
use Bitrix\Main;
use Bitrix\Iblock;

Main\Localization\Loc::loadMessages(__FILE__);

class Source extends Feed\Export\Entity\Reference\Source
{
	public function getQuerySelect($select)
	{
		return [
			'ELEMENT' => $select
		];
	}

	public function isFilterable()
	{
		return true;
	}

	public function getQueryFilter($filter, $select)
	{
		return [
			'ELEMENT' => $this->buildQueryFilter($filter)
		];
	}

	public function getOrder()
	{
		return 100;
	}

	public function getElementListValues($elementList, $parentList, $select, $queryContext, $sourceValues)
	{
		$result = [];

		foreach ($elementList as $elementId => $element)
		{
			$parent = null;

			if (!isset($element['PARENT_ID'])) // is not offer
			{
				$parent = $element;
			}
			else if (isset($parentList[$element['PARENT_ID']])) // has parent element
			{
				$parent = $parentList[$element['PARENT_ID']];
			}

			if (isset($parent))
			{
				$result[$elementId] = $this->getFieldValues($parent, $select);
			}
		}

		return $result;
	}

	public function getFields(array $context = [])
	{
		return $this->buildFieldsDescription([
			'ID' => [
				'TYPE' => Feed\Export\Entity\Data::TYPE_NUMBER,
                'AUTOCOMPLETE' => true,
                'AUTOCOMPLETE_FIELD' => 'NAME'
			],
			'NAME' => [
				'TYPE' => Feed\Export\Entity\Data::TYPE_STRING,
                'AUTOCOMPLETE' => true,
			],
			'IBLOCK_SECTION_ID' => [
				'TYPE' => Feed\Export\Entity\Data::TYPE_IBLOCK_SECTION
			],
			'STRICT_SECTION_ID' => [
				'TYPE' => Feed\Export\Entity\Data::TYPE_IBLOCK_SECTION,
				'SELECTABLE' => false
			],
			'CODE'=> [
				'TYPE' => Feed\Export\Entity\Data::TYPE_STRING,
                'AUTOCOMPLETE' => true,
                'AUTOCOMPLETE_FIELD' => 'NAME'
			],
			'PREVIEW_PICTURE' => [
				'TYPE' => Feed\Export\Entity\Data::TYPE_FILE
			],
			'PREVIEW_TEXT' => [
				'TYPE' => Feed\Export\Entity\Data::TYPE_STRING
			],
			'DETAIL_PICTURE' => [
				'TYPE' => Feed\Export\Entity\Data::TYPE_FILE
			],
			'DETAIL_TEXT' => [
				'TYPE' => Feed\Export\Entity\Data::TYPE_STRING
			],
			'DETAIL_PAGE_URL' => [
				'TYPE' => Feed\Export\Entity\Data::TYPE_URL
			],
			'DATE_CREATE' => [
				'TYPE' => Feed\Export\Entity\Data::TYPE_DATE
			],
			'TIMESTAMP_X' => [
				'TYPE' => Feed\Export\Entity\Data::TYPE_DATE
			],
			'XML_ID' => [
				'TYPE' => Feed\Export\Entity\Data::TYPE_STRING
			]
		]);
	}

	public function getFieldEnum($field, array $context = [])
	{
		$result = null;

		switch ($field['ID'])
		{
			case 'IBLOCK_SECTION_ID':
			case 'STRICT_SECTION_ID':

				if (isset($context['IBLOCK_ID']) && Main\Loader::includeModule('iblock'))
				{
					$result = [];

					$queryEnum = \CIBlockSection::getList(
						[
							'LEFT_MARGIN' => 'ASC'
						],
						[
							'IBLOCK_ID' => $context['IBLOCK_ID'],
							'ACTIVE' => 'Y',
							'CHECK_PERMISSIONS' => 'N'
						],
						false,
						[
							'ID',
							'NAME',
							'DEPTH_LEVEL'
						]
					);

					while ($enum = $queryEnum->fetch())
					{
						$result[] = [
							'ID' => $enum['ID'],
							'VALUE' => str_repeat('.', $enum['DEPTH_LEVEL'] - 1) . $enum['NAME']
						];
					}
				}

			break;

		}

		if ($result === null)
		{
			$result = parent::getFieldEnum($field, $context);
		}

		return $result;
	}

	public function getFieldAutocomplete($field, $query, array $context = [])
    {
        $result = [];
        $query = (string)$query;
        $iblockId = (int)$this->getContextIblockId($context);

        if ($query !== '' && $iblockId > 0 && !empty($field['ID']))
        {
            $select = [ $field['ID'] ];
            $filter = [
                'IBLOCK_ID' => $iblockId,
                'ACTIVE' => 'Y',
                'ACTIVE_DATE' => 'Y'
            ];
            $autocompleteField = isset($field['AUTOCOMPLETE_FIELD'])
                ? $field['AUTOCOMPLETE_FIELD']
                : null;

            if ($autocompleteField !== null)
            {
                $select[] = $autocompleteField;

                $filter[] = [
                    'LOGIC' => 'OR',
                    [ '%' . $field['ID'] => $query ],
                    [ '%' . $autocompleteField => $query ],
                ];
            }
            else
            {
                $filter['%' . $field['ID']] = $query;
            }

            // query

            $queryList = \CIBlockElement::GetList(
                [],
                $filter,
                false,
                [ 'nTopCount' => 20 ],
                $select
            );

            while ($item = $queryList->Fetch())
            {
                $itemValue = isset($item[$field['ID']]) ? trim($item[$field['ID']]) : '';

                if ($itemValue !== '')
                {
                    $itemTitle = $itemValue;

                    if ($autocompleteField !== null && isset($item[$autocompleteField]))
                    {
                        $itemTitle = '[' . $itemValue . '] ' . $item[$autocompleteField];
                    }

                    $result[] = [
                        'ID' => $itemValue,
                        'VALUE' => $itemTitle
                    ];
                }
            }
        }

        return $result;
    }

    public function getFieldDisplayValue($field, $valueList, array $context = [])
    {
        $result = null;
        $iblockId = (int)$this->getContextIblockId($context);

        if ($iblockId > 0 && !empty($field['AUTOCOMPLETE_FIELD']))
        {
            $result = [];

            $queryList = \CIBlockElement::GetList(
                [],
                [
                    'IBLOCK_ID' => $iblockId,
                    '=' . $field['ID'] => $valueList
                ],
                false,
                [ 'nTopCount' => count($valueList) ],
                [
                    $field['ID'],
                    $field['AUTOCOMPLETE_FIELD']
                ]
            );

            while ($item = $queryList->Fetch())
            {
                $itemValue = isset($item[$field['ID']]) ? trim($item[$field['ID']]) : '';
                $itemTitle = isset($item[$field['AUTOCOMPLETE_FIELD']]) ? trim($item[$field['AUTOCOMPLETE_FIELD']]) : '';

                if ($itemValue !== '' && $itemTitle !== '')
                {
                    $result[] = [
                        'ID' => $itemValue,
                        'VALUE' => '[' . $itemValue . '] ' . $itemTitle
                    ];
                }
            }
        }

        return $result;
    }

    protected function buildQueryFilter($filter)
	{
		$result = [];

		foreach ($filter as $filterItem)
		{
			$compare = $filterItem['COMPARE'];
			$field = $filterItem['FIELD'];
			$value = $filterItem['VALUE'];

			if ($field === 'IBLOCK_SECTION_ID' || $field === 'SECTION_ID')
			{
				$field = 'SECTION_ID';
				$isCompareEqual = (strpos($compare, '!') !== 0);
				$compare = $isCompareEqual ? '' : '!';

				if (empty($value))
				{
					// nothing
				}
				else if ($isCompareEqual)
				{
					$result['INCLUDE_SUBSECTIONS'] = 'Y';
				}
				else
				{
					$sectionMargins = $this->loadSectionMargin($value);

					if ($this->hasSectionChild($sectionMargins))
					{
						$field = 'SUBSECTION';
						$value = $sectionMargins;
					}
				}
			}
			else if ($field === 'STRICT_SECTION_ID')
			{
				$field = 'SECTION_ID';
				$isCompareEqual = (strpos($compare, '!') !== 0);
				$compare = $isCompareEqual ? '' : '!';
			}

			$this->pushQueryFilter($result, $compare, $field, $value);
        }

        return $result;
	}

	protected function getContextIblockId(array $context = [])
    {
        return $context['IBLOCK_ID'];
    }

	protected function getFieldValues($element, $select, $parent = null)
	{
		$result = [];

		foreach ($select as $fieldName)
		{
			$fieldValue = null;

			if (isset($element[$fieldName]))
			{
				$fieldValue = $element[$fieldName];

				switch ($fieldName)
				{
					case 'PREVIEW_PICTURE':
					case 'DETAIL_PICTURE':
						$fieldValue = \CFile::GetPath($fieldValue);
					break;

					case 'DETAIL_PAGE_URL':
						if (isset($parent['DETAIL_PAGE_URL']) && strpos($fieldValue, '#PRODUCT_URL#') !== false)
						{
							$parentUrl = \CIBlock::ReplaceDetailUrl($parent['DETAIL_PAGE_URL'], $parent, true, 'E');

							$fieldValue = str_replace('#PRODUCT_URL#', $parentUrl, $fieldValue);
						}

						$fieldValue = \CIBlock::ReplaceDetailUrl($fieldValue, $element, true, 'E');
					break;
				}
			}

			$result[$fieldName] = $fieldValue;
		}

		return $result;
	}

	protected function getLangPrefix()
	{
		return 'IBLOCK_ELEMENT_FIELD_';
	}

	protected function loadSectionMargin($sectionIds)
	{
		$result = [];

		$querySections = Iblock\SectionTable::getList([
			'filter' => [
				'=ID' => $sectionIds
			],
			'select' => [
				'ID',
				'LEFT_MARGIN',
				'RIGHT_MARGIN'
			]
		]);

		while ($section = $querySections->fetch())
		{
			$result[] = [
				(int)$section['LEFT_MARGIN'],
				(int)$section['RIGHT_MARGIN']
			];
		}

		return $result;
	}

	protected function hasSectionChild($sectionMargins)
	{
		$result = false;

		foreach ($sectionMargins as $sectionMargin)
		{
			if ($sectionMargin[1] > $sectionMargin[0] + 1)
			{
				$result = true;
				break;
			}
		}

		return $result;
	}
}
