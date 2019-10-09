<?php

namespace Ligacom\Feed\Export\Entity\Reference;

use Ligacom\Feed;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

abstract class Source
{
	protected $type;

	public function setType($type)
	{
		$this->type = $type;
	}

	public function getType()
	{
		return $this->type;
	}

	/**
	 * ���� ��������
	 *
	 * @param array $context
	 *
	 * @return array
	 */
	abstract public function getFields(array $context = []);

    /**
     * ���� ��������
     *
     * @param $fieldId
     * @param array $context
     *
     * @return array|null
     */
    public function getField($fieldId, array $context = [])
    {
        $fields = $this->getFields($context);
        $result = null;

        if (!empty($fields))
        {
            foreach ($fields as $field)
            {
                if ($field['ID'] === $fieldId)
                {
                    $result = $field;
                    break;
                }
            }
        }

        return $result;
    }

	/**
	 * �������� �������� ��������
	 *
	 * @param       $field
	 * @param array $context
	 *
	 * @return array|null
	 */
	public function getFieldEnum($field, array $context = [])
	{
		$result = null;

		if (!empty($field['TYPE']))
		{
			switch ($field['TYPE'])
			{
				case Feed\Export\Entity\Data::TYPE_BOOLEAN:

					$result = [
						[
							'ID' => 'Y',
							'VALUE' => Feed\Config::getLang('EXPORT_ENTITY_SOURCE_BOOLEAN_TYPE_ENUM_Y')
						],
						[
							'ID' => 'N',
							'VALUE' => Feed\Config::getLang('EXPORT_ENTITY_SOURCE_BOOLEAN_TYPE_ENUM_N')
						]
					];

				break;

				case Feed\Export\Entity\Data::TYPE_FILE:

					$result = [
						[
							'ID' => Feed\Export\Entity\Data::SPECIAL_VALUE_EMPTY,
							'VALUE' => Feed\Config::getLang('EXPORT_ENTITY_SOURCE_FILE_TYPE_ENUM_EMPTY')
						]
					];

				break;

				case Feed\Export\Entity\Data::TYPE_SERVICE_CATEGORY:

					$sectionList = Feed\Service\Data\Category::getList();
					$currentTree = [];
					$currentTreeDepth = 0;
					$sectionNameCache = [];
					$result = [];

					foreach ($sectionList as $sectionKey => $section)
					{
						if ($section['depth'] < $currentTreeDepth)
						{
							array_splice($currentTree, $section['depth']);
						}

						$currentTree[$section['depth']] = $sectionKey;
						$currentTreeDepth = $section['depth'];
						$sectionFullName = '';

						foreach ($currentTree as $treeKey)
						{
							$treeSection = $sectionList[$treeKey];
							$treeSectionName = null;

							if (isset($sectionNameCache[$treeSection['id']]))
							{
								$treeSectionName = $sectionNameCache[$treeSection['id']];
							}
							else
							{
								$treeSectionName = Feed\Service\Data\Category::getTitle($treeSection['id']);
								$sectionNameCache[$treeSection['id']] = $treeSectionName;
							}

							$sectionFullName .= ($sectionFullName === '' ? '' : ' / ') . $treeSectionName;
						}

						$result[] = [
							'ID' => $section['id'],
							'VALUE' => $sectionFullName
						];
					}

				break;
			}
		}

		return $result;
	}

    /**
     * ������������ ��� ����
     *
     * @param array $field
     * @param string $query
     * @param array $context
     *
     * @return array|null
     */
    public function getFieldAutocomplete($field, $query, array $context = [])
    {
        return null;
    }

    /**
     * ������ �������� ��� ����������� ������������
     *
     * @param array $field
     * @param array $valueList
     * @param array $context
     *
     * @return array|null
     */
    public function getFieldDisplayValue($field, $valueList, array $context = [])
    {
        return null;
    }

	/**
	 * �������� ��������
	 *
	 * @return string
	 */
	public function getTitle()
	{
		$langPrefix = $this->getLangPrefix();

		return Feed\Config::getLang($langPrefix . 'TITLE');
	}

	/**
	 * ���� ������������: �� ����� ������������ �����, ����� ��������� ����� ��������
	 *
	 * @return bool
	 */
	public function isVariable()
	{
		return false;
	}

	/**
	 * �������� �� ��������
	 *
	 * @return bool
	 */
	public function isTemplate()
	{
		return false;
	}

	/**
	 * ����� ����������� � �������
	 *
	 * @return bool
	 */
	public function isSelectable()
	{
		return true;
	}

	/**
	 * ���� select ��� ������� CIBlockElement::GetList
	 *
	 * @param $select
	 *
	 * @return array
	 */
	public function getQuerySelect($select)
	{
		return [];
	}

	/**
	 * ����� �� ������������ ������ ��� CIBlockElement::GetList
	 *
	 * @return bool
	 */
	public function isFilterable()
	{
		return false;
	}

	/**
	 * ������ ��� ������� CIBlockElement::GetList
	 *
	 * @param $filter
	 * @param $select
	 *
	 * @return array
	 */
	public function getQueryFilter($filter, $select)
	{
		return [];
	}

	protected function pushQueryFilter(&$filter, $compare, $field, $value)
    {
        $queryKey = $compare . $field;

        if (!isset($filter[$queryKey]))
        {
            $filter[$queryKey] = $value;
        }
        else
        {
            $newValue = (array)$filter[$queryKey];

            if (is_array($value))
            {
                $newValue = array_merge($newValue, $value);
            }
            else
            {
                $newValue[] = $value;
            }

            $filter[$queryKey] = $newValue;
        }
    }

	/**
	 * ������� ���������� ��� ��������� ���������
	 *
	 * @return int
	 * */
	public function getOrder()
	{
		return 500;
	}

	public function initializeQueryContext($select, &$queryContext, &$sourceSelect)
	{
		// nothing by default
	}

	public function releaseQueryContext($select, $queryContext, $sourceSelect)
	{
		// nothing by default
	}

	/**
	 * ������� �������� ����� �� ����������� ������� CIBlockElement::GetList
	 *
	 * @param $elementList
	 * @param $parentList
	 * @param $selectFields
	 * @param $queryContext
	 * @param $sourceValues
	 *
	 * @return array
	 */
	public function getElementListValues($elementList, $parentList, $selectFields, $queryContext, $sourceValues)
	{
		return [];
	}

	/**
	 * ��������������� ����� ��� ��������� �������� ����� ��������
	 *
	 * @param $fieldList
	 *
	 * @return array
	 */
	protected function buildFieldsDescription($fieldList)
	{
		$result = [];
		$langPrefix = $this->getLangPrefix();

		foreach ($fieldList as $fieldId => $field)
		{
			$field['ID'] = $fieldId;

			if (!isset($field['VALUE']))
			{
				$field['VALUE'] = Feed\Config::getLang($langPrefix . 'FIELD_' . $fieldId);
			}

			if (!isset($field['FILTERABLE']))
			{
				$field['FILTERABLE'] = true;
			}

			if (!isset($field['SELECTABLE']))
			{
				$field['SELECTABLE'] = true;
			}

			if (!isset($field['AUTOCOMPLETE']))
			{
				$field['AUTOCOMPLETE'] = false;
			}

			$result[] = $field;
		}

		return $result;
	}

	/**
	 * ������� ��� �������� ���� ������
	 *
	 * @return string
	 */
	abstract protected function getLangPrefix();
}
