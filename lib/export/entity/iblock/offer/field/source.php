<?php

namespace Ligacom\Feed\Export\Entity\Iblock\Offer\Field;

use Ligacom\Feed;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

class Source extends Feed\Export\Entity\Iblock\Element\Field\Source
{
	public function getQuerySelect($select)
	{
		return [
			'ELEMENT' => $select,
			'OFFERS' => $select
		];
	}

	public function getQueryFilter($filter, $select)
	{
		return [
			'OFFERS' => $this->buildQueryFilter($filter)
		];
	}

	public function getElementListValues(array $elementList, array $parentList, array $select, array $queryContext, array $sourceValues) :array
	{
		$result = [];

		foreach ($elementList as $elementId => $element)
		{
			$parent = isset($parentList[$element['PARENT_ID']]) ? $parentList[$element['PARENT_ID']] : null;

			$result[$elementId] = $this->getFieldValues($element, $select, $parent); // extract for all
		}

		return $result;
	}

	public function getFields(array $context = [])
	{
		return isset($context['OFFER_IBLOCK_ID']) ? parent::getFields($context) : [];
	}

	protected function getContextIblockId(array $context = [])
    {
        return isset($context['OFFER_IBLOCK_ID']) ? (int)$context['OFFER_IBLOCK_ID'] : null;
    }

    protected function getLangPrefix()
	{
		return 'IBLOCK_OFFER_FIELD_';
	}
}