<?php

namespace Ligacom\Feed\Export\Filter;

use Ligacom\Feed;

class Model extends Feed\Reference\Storage\Model
{
	/** @var array|null */
	protected $plainFilter;
	/** @var array|null */
	protected $plainData;

	public static function getDataClass()
	{
		return Table::getClassName();
	}

	public function getPlainFilter()
	{
		return $this->plainFilter;
	}

	public function setPlainFilter(array $plainFilter)
	{
		$this->plainFilter = $plainFilter;
	}

	public function getPlainData()
	{
		return $this->plainData;
	}

	public function setPlainData(array $data)
	{
		$this->plainData = $data;
	}

	public function getSourceFilter()
	{
		if ($this->plainFilter !== null)
		{
			$result = $this->plainFilter;
		}
		else
		{
			$result = [];

			/** @var Feed\Export\FilterCondition\Model $condition */
			foreach ($this->getConditionCollection() as $condition)
			{
				if ($condition->isValid())
				{
					$conditionCompare = $condition->getQueryCompare();
					$conditionField = $condition->getQueryField();
					$conditionValue = $condition->getQueryValue();
					$conditionSource = $condition->getSourceName();

					if (!isset($result[$conditionSource]))
					{
						$result[$conditionSource] = [];
					}

					$result[$conditionSource][] = [
						'FIELD' => $conditionField,
						'COMPARE' => $conditionCompare,
						'VALUE' => $conditionValue
					];
				}
			}
		}

		return $result;
	}

	public function getUsedSources()
	{
		$sourceFilter = $this->getSourceFilter();
		$usedSources = $this->getFilterUsedSources($sourceFilter);

		return array_keys($usedSources);
	}

	/**
	 * @param $sourceFilter
	 *
	 * @return array
	 */
	protected function getFilterUsedSources($sourceFilter)
	{
		$result = [];

		foreach ($sourceFilter as $sourceName => $filter)
		{
			if ($sourceName === 'LOGIC')
			{
				// nothing
			}
			else if (is_numeric($sourceName))
			{
				$result += $this->getFilterUsedSources($filter);
			}
			else
			{
				$result[$sourceName] = true;
			}
		}

		return $result;
	}

	/**
	 * @return array
	 */
	public function getContext($isOnlySelf = false)
	{
		$result = [
			'FILTER_ID' => $this->getId()
		];


		if (!$isOnlySelf)
		{
			$result = $this->mergeParentContext($result);
		}

		return $result;
	}

	protected function mergeParentContext($selfContext)
	{
		$collection = $this->getCollection();
		$iblockLink = $collection ? $collection->getParent() : null;
		$iblockLinkContext = $iblockLink ? $iblockLink->getContext() : null;
		$result = $selfContext;

		if (isset($iblockLinkContext))
		{
			$result += $iblockLinkContext;
		}

		return $result;
	}

    /**
     * @return Feed\Reference\Storage\Collection
     * @throws \Bitrix\Main\SystemException
     */
	public function getConditionCollection()
	{
		return $this->getChildCollection('FILTER_CONDITION');
	}

	protected function getChildCollectionReference($fieldKey)
	{
		$result = null;

		switch ($fieldKey)
		{
			case 'FILTER_CONDITION':
				$result = Feed\Export\FilterCondition\Collection::getClassName();
			break;
		}

		return $result;
	}
}
