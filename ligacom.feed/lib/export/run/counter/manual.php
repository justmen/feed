<?php

namespace Ligacom\Feed\Export\Run\Counter;

use Ligacom\Feed;

class Manual extends Base
{
	protected $excludeList = [];

	public function start()
	{
		// nothing
	}

	public function count($filter, $context)
	{
		$result = 0;
		$pageOffset = 0;
		$elementSelect = $this->getElementSelect($context);
		$elementFilterOriginal = $filter['ELEMENT'];

		do
		{
			$pageElementCount = 0;
			$parentList = [];
			$elementFilter = $elementFilterOriginal;

			if ($pageOffset > 0)
			{
				$elementFilter[] = [ '>ID' => $pageOffset ];
			}

			$queryElementList = \CIBlockElement::GetList(
				[ 'ID' => 'ASC' ],
				$elementFilter,
				false,
				[ 'nTopCount' => $context['PAGE_SIZE'], 'checkOutOfRange' => true ],
				$elementSelect
			);

			while ($element = $queryElementList->Fetch())
			{
				if ($context['CATALOG_TYPE_COMPATIBILITY'])
				{
					$parentList[$element['ID']] = true;

					$this->excludeList[$element['ID']] = true;
					++$result;
				}
				else if ($this->isElementHasOffers($element, $context))
				{
					$parentList[$element['ID']] = true;
				}
				else if (!isset($this->excludeList[$element['ID']]))
				{
					$this->excludeList[$element['ID']] = true;
					++$result;
				}

				$pageOffset = (int)$element['ID'];
				$pageElementCount++;
			}

			if (!empty($parentList) && $context['HAS_OFFER']) // has parents by catalog_type
			{
				$skuPropertyKey = 'PROPERTY_' . $context['OFFER_PROPERTY_ID'];
				$skuPropertyValueKey = $skuPropertyKey . '_VALUE';

				$offerSelect = [
					'IBLOCK_ID',
					'ID',
					$skuPropertyKey
				];

				$offerFilter = $filter['OFFERS'];
				$offerFilter['=' . $skuPropertyKey] = array_keys($parentList);

				$queryOffers = \CIBlockElement::GetList(
					[],
					$offerFilter,
					false,
					false,
					$offerSelect
				);

				while ($offer = $queryOffers->Fetch())
				{
					$offerElementId = (int)$offer[$skuPropertyValueKey];

					if (isset($parentList[$offerElementId]))
					{
						if ($context['CATALOG_TYPE_COMPATIBILITY'] && isset($this->excludeList[$offerElementId]))
						{
							unset($this->excludeList[$offerElementId]);
							--$result;
						}

						if (!isset($this->excludeList[$offer['ID']]))
						{
							$this->excludeList[$offer['ID']] = true;
							++$result;
						}
					}
				}
			}
		}
		while ($context['PAGE_SIZE'] <= $pageElementCount); // has next (iblock DISTINCT)

		return $result;
	}

	public function finish()
	{
		$this->excludeList = [];
	}

	protected function getElementSelect($context)
	{
		$result = [ 'IBLOCK_ID', 'ID' ];

		if (!$context['CATALOG_TYPE_COMPATIBILITY'] && !$context['OFFER_ONLY'])
		{
			$result[] = 'CATALOG_TYPE';
		}

		return $result;
	}

	protected function isElementHasOffers($element, $context)
	{
		$result = false;

		if (!$context['HAS_OFFER'])
		{
			$result = true;
		}
		else if ($context['OFFER_ONLY'])
		{
			$result = true;
		}
		else if (isset($element['CATALOG_TYPE']))
		{
			$result = (int)$element['CATALOG_TYPE'] === Feed\Export\Run\Steps\Offer::ELEMENT_TYPE_SKU;
		}
		else if (array_key_exists('CATALOG_TYPE', $element))
		{
			$result = true;
		}

		return $result;
	}
}