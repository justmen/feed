<?php

namespace Ligacom\Feed\Export\Run\Steps;

use Bitrix\Main;
use Ligacom\Feed;

Main\Localization\Loc::loadMessages(__FILE__);

class Offer extends Base
{
	const ELEMENT_TYPE_PRODUCT = 1;
	const ELEMENT_TYPE_SET = 2;
	const ELEMENT_TYPE_SKU = 3;
	const ELEMENT_TYPE_OFFER = 4;
	const ELEMENT_TYPE_FREE_OFFER = 5;
	const ELEMENT_TYPE_EMPTY_SKU = 6;

	protected $queryExcludeFilterList = [];
	protected $isCatalogTypeCompatibility;
	protected $sourceCurrencyConversion = null;

	public function getName()
	{
		return 'offer';
	}

	public function getReadyCount()
	{
		$dataClass = $this->getStorageDataClass();
		$context = $this->getContext();
		$readyFilter = $this->getStorageReadyFilter($context);
		$result = 0;

		$query = $dataClass::getList([
			'filter' => $readyFilter,
			'select' => [ 'CNT' ],
			'runtime' => [
				new Main\Entity\ExpressionField('CNT', 'COUNT(1)')
			]
		]);

		if ($row = $query->fetch())
		{
			$result = (int)$row['CNT'];
		}

		return $result;
	}

	public function getSuccessCount($context = null)
	{
		if ($context === null) { $context = $this->getContext(); }

		$dataClass = $this->getStorageDataClass();
		$readyFilter = $this->getStorageReadyFilter($context, true);
		$readyFilter['=STATUS'] = static::STORAGE_STATUS_SUCCESS;
		$result = 0;

		$query = $dataClass::getList([
			'filter' => $readyFilter,
			'select' => [ 'CNT' ],
			'runtime' => [
				new Main\Entity\ExpressionField('CNT', 'COUNT(1)')
			]
		]);

		if ($row = $query->fetch())
		{
			$result = (int)$row['CNT'];
		}

		return $result;
	}

	public function getTotalCount($isDisableCalculation = false)
	{
		if ($this->totalCount === null && !$isDisableCalculation)
		{
			$this->totalCount = 0;

			$iblockConfigList = $this->getIblockConfigList();
			$iblockConfigIndex = 0;

			foreach ($iblockConfigList as $iblockConfig)
			{
				if ($iblockConfig['EXPORT_ALL'])
				{
					$queryFilter = $this->makeQueryFilter([], [], $iblockConfig['CONTEXT']);

					$this->totalCount += $this->queryTotalCount($queryFilter, $iblockConfig['CONTEXT']);
				}
				else
				{
					$filterCountList = $this->getCount($iblockConfigIndex, false);

					$this->totalCount += $filterCountList->getSum();
				}

				$iblockConfigIndex++;
			}
		}

		return $this->totalCount;
	}

	public function getCount($offset = null, $isNeedAll = null)
	{
		$result = new Feed\Result\StepCount();
		$offsetIblockConfigIndex = null;
		$offsetFilterIndex = null;

		if (isset($offset))
		{
			$offsetParts = explode(':', $offset);
			$offsetIblockConfigIndex = (int)$offsetParts[0];
			$offsetFilterIndex = isset($offsetParts[1]) ? (int)$offsetParts[1] : null;
		}

		$iblockConfigList = $this->getIblockConfigList($isNeedAll);
		$iblockConfigIndex = 0;

		foreach ($iblockConfigList as $iblockConfig)
		{
			if ($offsetIblockConfigIndex !== null && $offsetIblockConfigIndex !== $iblockConfigIndex) // is iblock out of offset
			{
				$iblockConfigIndex++;
				continue;
			}

			$counterManager = new Feed\Export\Run\Counter\Manager();

			do
			{
				$isNeedRepeatCount = false;
				$counter = null;
				$sourceFilterIndex = 0;
				$previousFilterSum = 0;

				try
				{
					foreach ($iblockConfig['FILTER_LIST'] as $sourceFilter)
					{
						if ($offsetFilterIndex === null || $offsetFilterIndex >= $sourceFilterIndex) // is filter in offset or no offset
						{
							$filterContext = $sourceFilter['CONTEXT'] + $iblockConfig['CONTEXT'];
							$queryFilter = $this->makeQueryFilter($sourceFilter['FILTER'], [], $filterContext);
							$filterCount = 0;
							$isIblockConfigFilter = ($sourceFilter['ID'] === null);

							if ($isIblockConfigFilter)
							{
								$totalCount = $this->queryTotalCount($queryFilter, $filterContext);
								$filterCount = $totalCount - $previousFilterSum;

								if ($this->isCatalogTypeCompatibility($filterContext))
								{
									$result->addCountWarning($iblockConfig['ID'], new Feed\Error\Base(
										Feed\Config::getLang('EXPORT_RUN_STEP_OFFER_COUNT_CATALOG_TYPE_COMPATIBILITY')
									));
								}
							}
							else if (!empty($sourceFilter['FILTER']))
							{
								if ($counter === null)
								{
									$counter = $counterManager->getCounter();
									$counter->start();
								}

								$filterCount = $this->queryCount($queryFilter, $filterContext, $counter);

								$previousFilterSum += $filterCount;
							}

							if ($isIblockConfigFilter) // is iblock link
							{
								$result->setCount($iblockConfig['ID'], $filterCount);
							}
							else
							{
								$result->setCount($iblockConfig['ID'] . ':' . $sourceFilter['ID'], $filterCount);
							}
						}

						$sourceFilterIndex++;
					}

					if ($counter !== null)
					{
						$counter->finish();
					}
				}
				catch (Main\SystemException $exception)
				{
					$counterManager->invalidateCounter();

					if ($counterManager->hasCounter())
					{
						$isNeedRepeatCount = true;
					}
					else
					{
						$result->addCountWarning($iblockConfig['ID'], new Feed\Error\Base(
							Feed\Config::getLang('EXPORT_RUN_STEP_OFFER_COUNT_FAILED')
						));
					}
				}
			}
			while ($isNeedRepeatCount);

			$iblockConfigIndex++;
		}

		return $result;
	}

    /**
     * @param $action
     * @param null $offset
     * @return Feed\Result\Step
     * @throws Main\ObjectNotFoundException
     */
	public function run($action, $offset = null) :Feed\Result\Step
	{

		$result = new Feed\Result\Step();

		$this->setRunAction($action);

		$iblockConfigList = $this->getIblockConfigList();

		//мой кусок для разбиения по пробелу одиночных элементов
        //todo переписать - сделать нормально и прозрачнее
        foreach ($iblockConfigList[0]['FILTER_LIST'][0]['FILTER']['iblock_element_property'] as $kPropertyFilter=>$iblockLinkFilter) {


                $conditionValueList =explode(' ', $iblockLinkFilter['VALUE'], -1);
                $conditionValueList = array_filter($conditionValueList);
                if(count($conditionValueList) >0) {
                    if(is_array($iblockConfigList[0]['FILTER_LIST'][0]['FILTER']['iblock_element_property'][$kPropertyFilter]['VALUE'][$kValue])) {
                        unset($iblockConfigList[0]['FILTER_LIST'][0]['FILTER']['iblock_element_property'][$kPropertyFilter]['VALUE'][$kValue]);
                    } else {
                       $iblockConfigList[0]['FILTER_LIST'][0]['FILTER']['iblock_element_property'][$kPropertyFilter]['VALUE'] = [];
                    }

                    foreach ($conditionValueList as $addCondition) {
                        $iblockConfigList[0]['FILTER_LIST'][0]['FILTER']['iblock_element_property'][$kPropertyFilter]['VALUE'][]  = $addCondition;
                    }
                }
        }
        //end мой кусок
        /** $formatTag Feed\Export\Xml\Tag\Base*/
		$formatTag = $this->getTag();

		// calculate offset and total

		$offsetIblockConfigIndex = null;
		$offsetFilterIndex = null;
		$offsetFilterShift = null;
		$totalFilterCount = 0;
		$iblockConfigWeightList = [];

		if ($offset !== null)
		{
			$offsetParts = explode(':', $offset);
			$offsetIblockConfigIndex = (int)$offsetParts[0];
			$offsetFilterIndex = isset($offsetParts[1]) ? (int)$offsetParts[1] : null;
			$offsetFilterShift = isset($offsetParts[2]) ? (int)$offsetParts[2] : null;
		}

		foreach ($iblockConfigList as $iblockConfig)
		{
			$iblockConfigWeight = count($iblockConfig['FILTER_LIST']);

			$iblockConfigWeightList[] = $iblockConfigWeight;
			$totalFilterCount += $iblockConfigWeight;
		}

		$result->setTotal($totalFilterCount);

		// run export

		$iblockConfigIndex = 0;
		$isTimeExpired = false;

		foreach ($iblockConfigList as $iblockConfig)
		{
			if ($offsetIblockConfigIndex !== null && $offsetIblockConfigIndex > $iblockConfigIndex) // is iblock coffset
			{
				$result->increaseProgress($iblockConfigWeightList[$iblockConfigIndex]);
				$iblockConfigIndex++;
				continue;
			}
            //todo что такое TAG_DESCRIPTION_LIST и как формируется?
			$tagDescriptionList = $iblockConfig['TAG_DESCRIPTION_LIST'];
			$iblockContext = $iblockConfig['CONTEXT'];
			$iblockLimit = (isset($iblockConfig['LIMIT']) ? (int)$iblockConfig['LIMIT'] : null);
			$iblockReadyCount = 0;
			$hasIblockLimit = ($iblockLimit > 0);
			$isExceededIblockLimit = false;
			$changesFilter = null;

			if ($action === 'change')
			{
				$changes = $this->getChanges();
				$changesFilter = $this->getQueryChangesFilter($changes, $iblockContext);

				if ($changesFilter === null) // changed other entity
				{
					$result->increaseProgress($iblockConfigWeightList[$iblockConfigIndex]);
					$iblockConfigIndex++;
					continue;
				}
			}

			$formatTag->extendTagDescriptionList($tagDescriptionList, $iblockContext);

			$sourceSelect = $this->getSourceSelect($tagDescriptionList);

			$this->initializeQueryContext($iblockContext, $sourceSelect);
			$this->sortSourceSelect($sourceSelect);
			$this->applySelectMap($tagDescriptionList, $iblockContext);

			$querySelect = $this->makeQuerySelect($sourceSelect, $iblockContext);
			$sourceFilterIndex = 0;

			if ($hasIblockLimit && ($offsetFilterIndex !== null || $offsetFilterShift !== null))
			{
				$iblockReadyCount = $this->getSuccessCount($iblockContext);
				$isExceededIblockLimit = ($iblockReadyCount >= $iblockLimit);
			}

			foreach ($iblockConfig['FILTER_LIST'] as $filterConfig)
			{
				if ($isExceededIblockLimit)
				{
					$result->increaseProgress(1);
					$result->setOffset($iblockConfigIndex + 1);
				}
				else if ($offsetFilterIndex === null || $sourceFilterIndex >= $offsetFilterIndex) // is not filter out of offset
				{
					$queryOffset = ($offsetFilterShift !== null ? $offsetFilterShift : 0);
					$filterContext = $filterConfig['CONTEXT'] + $iblockContext;
					$filterResult = $this->exportIblockFilter($filterConfig['FILTER'], $sourceSelect, $querySelect, $tagDescriptionList, $filterContext, $changesFilter, $queryOffset, $iblockLimit, $iblockReadyCount);
					$iblockReadyCount += $filterResult['SUCCESS_COUNT'];

					if ($filterResult['OFFSET'] !== null)
					{
						$isTimeExpired = true;

						$result->setOffset($iblockConfigIndex . ':' . $sourceFilterIndex . ':' . $filterResult['OFFSET']);
					}
					else if ($hasIblockLimit && $iblockReadyCount >= $iblockLimit)
					{
						$isExceededIblockLimit = true;

						$result->increaseProgress(1);
						$result->setOffset($iblockConfigIndex + 1);
					}
					else
					{
						$isTimeExpired = $this->getProcessor()->isTimeExpired();

						$result->increaseProgress(1);
						$result->setOffset($iblockConfigIndex . ':' . ($sourceFilterIndex + 1));
					}

					$offsetFilterShift = null; // reset page offset for next filter
				}
				else
				{
					$result->increaseProgress(1);
				}

				if ($isTimeExpired) { break; }

				$sourceFilterIndex++;
			}

			$this->releaseQueryContext($iblockContext, $sourceSelect);

			if ($isExceededIblockLimit)
			{
				$isTimeExpired = $this->getProcessor()->isTimeExpired();
			}

			if ($isTimeExpired) { break; }

			$offsetFilterIndex = null; // reset filter offset for next iblock link
			$offsetFilterShift = null; // reset page offset for next iblock link

			$iblockConfigIndex++;
		}

		if ($this->getParameter('progressCount') === true)
		{
			$readyCount = $this->getReadyCount();

			$result->setReadyCount($readyCount);
		}

		return $result;
	}

	public function getFormatTag(Feed\Export\Xml\Format\Reference\Base $format, $type = null)
	{
		return $format->getOffer();
	}

	public function getFormatTagParentName(Feed\Export\Xml\Format\Reference\Base $format)
	{
		return $format->getOfferParentName();
	}

	protected function useHashCollision()
	{
		return true;
	}

	protected function getStorageDataClass()
	{
		return Feed\Export\Run\Storage\OfferTable::getClassName();
	}

	protected function getStorageChangesFilter($changes, $context)
	{
		$isNeedFull = false;
		$result = [];

		if (!empty($changes))
		{
			foreach ($changes as $changeType => $entityIds)
			{
				switch ($changeType)
				{
					case Feed\Export\Run\Manager::ENTITY_TYPE_OFFER:

						$dataClass = $this->getStorageDataClass();
						$elementFilter = [];
						$parentFilter = [];

						$query = $dataClass::getList([
							'filter' => [
								'=SETUP_ID' => $context['SETUP_ID'],
								[
									'LOGIC' => 'OR',
									[ '=ELEMENT_ID' => $entityIds ],
									[ '=PARENT_ID' => $entityIds ]
								]

							],
							'select' => [
								'ELEMENT_ID',
								'PARENT_ID'
							]
						]);

						while ($row = $query->fetch())
						{
							$parentId = (int)$row['PARENT_ID'];

							if ($parentId > 0)
							{
								$parentFilter[$parentId] = true;
							}
							else
							{
								$elementFilter[] = (int)$row['ELEMENT_ID'];
							}
						}

						$hasParentFilter = !empty($parentFilter);
						$hasElementFilter = !empty($elementFilter);

						if ($hasParentFilter || $hasElementFilter)
						{
							if ($hasParentFilter)
							{
								$result[] = [
									'=PARENT_ID' => array_keys($parentFilter)
								];
							}

							if ($hasElementFilter)
							{
								$result[] = [
									'=ELEMENT_ID' => $elementFilter
								];
							}
						}
					break;

					case Feed\Export\Run\Manager::ENTITY_TYPE_CATEGORY:
						$result[] = [
							'=CATEGORY_ID' => $entityIds
						];
					break;

					case Feed\Export\Run\Manager::ENTITY_TYPE_CURRENCY:
						$result[] = [
							'=CURRENCY_ID' => $entityIds
						];
					break;

					default:
						$isNeedFull = true;
					break;
				}

				if ($isNeedFull)
				{
					break;
				}
			}
		}

		if ($isNeedFull)
		{
			$result = [];
		}
		else if (empty($result))
		{
			$result = null;
		}
		else if (count($result) > 1)
		{
			$result['LOGIC'] = 'OR';
		}

		return $result;
	}

	protected function getStorageAdditionalData($tagResult, $tagValues, $element, $context, $data)
	{
		$categoryId = $tagValues->getTagValue('categoryId') ?: '';
		$currencyId = $tagValues->getTagValue('currencyId') ?: '';

		return [
			'PARENT_ID' => isset($element['PARENT_ID']) ? $element['PARENT_ID'] : '',
			'IBLOCK_LINK_ID' => isset($context['IBLOCK_LINK_ID']) ? $context['IBLOCK_LINK_ID'] : '',
			'FILTER_ID' => isset($context['FILTER_ID']) ? $context['FILTER_ID'] : '',
			'CATEGORY_ID' => $categoryId,
			'CURRENCY_ID' => $currencyId
		];
	}

	protected function getDataLogEntityType()
	{
		return Feed\Logger\Table::ENTITY_TYPE_EXPORT_RUN_OFFER;
	}

	protected function isAllowPublicDelete()
	{
		return true;
	}

	protected function getIgnoredTypeChanges()
	{
		$result = [
			Feed\Export\Run\Manager::ENTITY_TYPE_PROMO => true,
			Feed\Export\Run\Manager::ENTITY_TYPE_GIFT => true,
		];

		if (!$this->hasSourceCurrencyConversion())
		{
			$result[Feed\Export\Run\Manager::ENTITY_TYPE_CURRENCY] = true;
		}

		return $result;
	}

	/**
	 * ������������ �� ����������� ������
	 *
	 * @return bool
	 */
	protected function hasSourceCurrencyConversion()
	{
		if ($this->sourceCurrencyConversion === null)
		{
			$this->sourceCurrencyConversion = $this->findSourceCurrencyConversion();
		}

		return $this->sourceCurrencyConversion;
	}

	/**
	 * ��������� ��������� ��� ������� �� ������� ����������� ������
	 *
	 * @return bool
	 */
	protected function findSourceCurrencyConversion()
	{
		$setup = $this->getSetup();
		$iblockLinkCollection = $setup->getIblockLinkCollection();
		$tags = [
			'price',
			'oldprice'
		];
		$result = false;

		/** @var Feed\Export\IblockLink\Model $iblockLink */
		foreach ($iblockLinkCollection as $iblockLink)
		{
			foreach ($tags as $tagName)
			{
				$tagDescription = $iblockLink->getTagDescription($tagName);

				if (isset($tagDescription['VALUE']['TYPE'], $tagDescription['VALUE']['FIELD']))
				{
					$source = $this->getSource($tagDescription['VALUE']['TYPE']);

					if (
						method_exists($source, 'hasCurrencyConversion')
						&& $source->hasCurrencyConversion($tagDescription['VALUE']['FIELD'], $tagDescription['SETTINGS'])
					)
					{
						$result = true;
						break 2;
					}
				}
			}
		}

		return $result;
	}

    /**
     * @param null $isNeedAll
     * @return array
     * @throws Main\SystemException
     */
	protected function getIblockConfigList($isNeedAll = null)
	{
	    /*$var Feed\Export\Setup\Model*/
		$setup = $this->getSetup();
		$result = [];

		/** @var Feed\Export\IblockLink\Model $iblockLink */
		foreach ($setup->getIblockLinkCollection() as $iblockLink)
		{
			$iblockContext = $iblockLink->getContext();

			$result[] = [
				'ID' => $iblockLink->getInternalId(),
				'EXPORT_ALL' => $iblockLink->isExportAll(),
				'TAG_DESCRIPTION_LIST' => $iblockLink->getTagDescriptionList(),
				'FILTER_LIST' => $this->getSourceFilterList($iblockLink, $iblockContext, $isNeedAll),
				'CONTEXT' => $iblockContext,
			];
		}

		return $result;
	}

    /**
     * @param array $sourceFilter - массив фильтров по источнику данных
     * @param array $sourceSelect - массив источников данных (сущностей) с выбранными полями
     * @param array $querySelect - поля выбираемые из элементов каталога и предложений
     * @param array $tagDescriptionList - массив тагов с описанием
     * @param array $context - настройки профиля выгрузки
     * @param null $changesFilter
     * @param null $queryOffset - отступ в выборке
     * @param null $limit
     * @param int $successCount - количество успешно полученных элементов
     * @return array
     * @throws Main\ObjectNotFoundException
     */
	protected function exportIblockFilter(
	    array $sourceFilter,
        array $sourceSelect,
        array $querySelect,
        array $tagDescriptionList,
        array $context,
        $changesFilter = null,
        $queryOffset = null,
        $limit = null,
        $successCount = 0) :array
	{
		$queryFilter = $this->makeQueryFilter($sourceFilter, $sourceSelect, $context, $changesFilter);
		$hasLimit = ($limit > 0);
		$chunkSize = ($hasLimit ? $limit : 500);
		$result = [
			'OFFSET' => null,
			'SUCCESS_COUNT' => 0
		];

		do
		{
			$queryResult = $this->queryElementList($queryFilter, $querySelect, $context, $queryOffset);
			$queryOffset = (int)$queryResult['OFFSET'];

			$this->processExportElementList($queryResult['ELEMENT'], $queryResult['PARENT'], $context);

			foreach (array_chunk($queryResult['ELEMENT'], $chunkSize, true) as $elementChunk)
			{
				$writeLimit = ($hasLimit ? $limit - $successCount : null);
				$sourceValueList = $this->extractElementListValues($sourceSelect, $elementChunk, $queryResult['PARENT'], $context);
				$tagValuesList = $this->buildTagValuesList($tagDescriptionList, $sourceValueList, $context);

				$writeResultList = $this->writeData($tagValuesList, $elementChunk, $context, [
					'PARENT_LIST' => $queryResult['PARENT']
				], $writeLimit);

				foreach ($writeResultList as $writeResult)
				{
					if ($writeResult['STATUS'] === static::STORAGE_STATUS_SUCCESS)
					{
						$successCount++;
						$result['SUCCESS_COUNT']++;
					}
				}
			}

			if ($hasLimit && $successCount >= $limit)
			{
				$result['OFFSET'] = null;
				break;
			}
			else if ($queryResult['HAS_NEXT'] && $this->getProcessor()->isTimeExpired())
			{
				$result['OFFSET'] = $queryOffset;
				break;
			}
		}
		while ($queryResult['HAS_NEXT']);

		return $result;
	}

	protected function processExportElementList(&$elementList, &$parentList, $context)
	{
		// nothing by default
	}

	protected function initializeQueryContext(&$iblockContext, &$sourceSelect)
	{
		foreach ($sourceSelect as $sourceType => $sourceFields)
		{
			$source = $this->getSource($sourceType);

			$source->initializeQueryContext($sourceFields, $iblockContext, $sourceSelect);
		}
	}

	protected function applySelectMap(&$tagDescriptionList, $iblockContext)
	{
		if (!empty($iblockContext['SELECT_MAP']))
		{
			$selectMap = $iblockContext['SELECT_MAP'];

			foreach ($tagDescriptionList as &$tagDescription)
			{
				if (isset($tagDescription['VALUE']))
				{
					$valueSourceMap = $tagDescription['VALUE'];

					if (isset($selectMap[$valueSourceMap['TYPE']][$valueSourceMap['FIELD']]))
					{
						$tagDescription['VALUE']['FIELD'] = $selectMap[$valueSourceMap['TYPE']][$valueSourceMap['FIELD']];
					}
				}

				if (isset($tagDescription['ATTRIBUTES']))
				{
					foreach ($tagDescription['ATTRIBUTES'] as &$attributeSourceMap)
					{
						if (isset($selectMap[$attributeSourceMap['TYPE']][$attributeSourceMap['FIELD']]))
						{
							$attributeSourceMap['FIELD'] = $selectMap[$attributeSourceMap['TYPE']][$attributeSourceMap['FIELD']];
						}
					}
					unset($attributeSourceMap);
				}
			}
			unset($tagDescription);
		}
	}

	protected function releaseQueryContext($iblockContext, $sourceSelect)
	{
		foreach ($sourceSelect as $sourceType => $sourceFields)
		{
			$source = $this->getSource($sourceType);

			$source->releaseQueryContext($sourceFields, $iblockContext, $sourceSelect);
		}
	}

	protected function getQueryChangesFilter($changes, $context)
	{
		$changesFilter = [];
		$isNeedFull = false;

		foreach ($changes as $changeType => $entityIds)
		{
			$entityType = null;
			$entityFilter = null;

			switch ($changeType)
			{
				case Feed\Export\Run\Manager::ENTITY_TYPE_OFFER:
				case Feed\Export\Run\Manager::ENTITY_TYPE_GIFT:

					if (!isset($context['OFFER_IBLOCK_ID']))
					{
						$entityType = 'ELEMENT';
						$entityFilter = [
							'ID' => $entityIds
						];
					}
					else
					{
						// no support for only one offer change

						$elementIdsMap = array_flip($entityIds);

						$queryOffers = \CIBlockElement::GetList(
							array(),
							array(
								'IBLOCK_ID' => $context['OFFER_IBLOCK_ID'],
								'ID' => $entityIds
							),
							false,
							false,
							array(
								'IBLOCK_ID',
								'ID',
								'PROPERTY_' . $context['OFFER_PROPERTY_ID']
							)
						);

						while ($offer = $queryOffers->Fetch())
						{
							$offerId = (int)$offer['ID'];
							$offerElementId = (int)$offer['PROPERTY_' . $context['OFFER_PROPERTY_ID'] . '_VALUE'];

							if ($offerElementId > 0 && !isset($elementIdsMap[$offerElementId]))
							{
								$elementIdsMap[$offerElementId] = true;
							}

							if (isset($elementIdsMap[$offerId]))
							{
								unset($elementIdsMap[$offerId]);
							}
						}

						$entityType = 'ELEMENT';
						$entityFilter = [
							'ID' => !empty($elementIdsMap) ? array_keys($elementIdsMap) : -1
						];
					}

				break;

				case Feed\Export\Run\Manager::ENTITY_TYPE_CATEGORY:
					$entityType = 'ELEMENT';
					$entityFilter = [
						'SECTION_ID' => $entityIds,
						'INCLUDE_SUBSECTIONS' => 'Y'
					];
				break;

				case Feed\Export\Run\Manager::ENTITY_TYPE_PROMO:
					if (isset($context['PROMO_ID']) && in_array($context['PROMO_ID'], (array)$entityIds))
					{
						$isNeedFull = true;
					}
				break;

				default: // unsupported change, need full refresh
					$isNeedFull = true;
				break;
			}

			if ($isNeedFull)
			{
				$changesFilter = [];
				break;
			}
			else if (isset($entityType) && isset($entityFilter))
			{
				if (!isset($changesFilter[$entityType]))
				{
					$changesFilter[$entityType] = [];
				}
				else if (count($changesFilter[$entityType]) === 1)
				{
					$changesFilter[$entityType]['LOGIC'] = 'OR';
				}

				$changesFilter[$entityType][] = $entityFilter;
			}
		}

		if (!$isNeedFull && empty($changesFilter))
		{
			$changesFilter = null;
		}

		return $changesFilter;
	}

	protected function queryCount($queryFilter, $queryContext, Feed\Export\Run\Counter\Base $counter)
	{
		$countContext = $queryContext;
		$countContext['PAGE_SIZE'] = (int)($this->getParameter('offerPageSize') ?: Feed\Config::getOption('export_count_offer_page_size') ?: 100);
		$countContext['CATALOG_TYPE_COMPATIBILITY'] = $queryContext['HAS_OFFER'] && $this->isCatalogTypeCompatibility($queryContext);

		return $counter->count($queryFilter, $countContext);
	}

	protected function queryTotalCount($queryFilter, $queryContext)
	{
		$hasOffers = isset($queryContext['OFFER_PROPERTY_ID']);
		$isOnlyOffers = !empty($queryContext['OFFER_ONLY']);
		$result = 0;

		// element count

		if (!$isOnlyOffers)
		{
			$elementFilter = $queryFilter['ELEMENT'];

			if ($hasOffers)
			{
				$elementFilter['!CATALOG_TYPE'] = static::ELEMENT_TYPE_SKU;
			}

			$result += (int)\CIBlockElement::GetList([], $elementFilter, []);
		}

		// offers count

		if ($hasOffers)
		{
			$result += (int)\CIBlockElement::GetList([], $queryFilter['OFFERS'], []);
		}

		return $result;
	}

	/**
	 * ����������� �������� �� ���� ������
	 *
	 * @param $queryFilter
	 * @param $querySelect
	 * @param $queryContext
	 * @param $offset
	 * @param $limit
	 *
	 * @return array
	 */
	protected function queryElementList($queryFilter, $querySelect, $queryContext, $offset = 0, $limit = null)
	{
		$pageSize = $this->getQueryElementListPageSize($queryContext, $limit);
		$pageElementCount = 0;
		$elementList = [];
		$parentList = [];
		$foundParents = [];
		$hasOffers = isset($queryContext['OFFER_PROPERTY_ID']);
		$isCatalogTypeCompatibility = ($hasOffers && $this->isCatalogTypeCompatibility($queryContext));

		$elementFilter = $queryFilter['ELEMENT'];

		$this->extendQueryElementListFilter($elementFilter, $queryContext, $isCatalogTypeCompatibility);

		if ($offset > 0)
		{
			$elementFilter[] = [ '>ID' => $offset ];
		}

		$queryElementList = \CIBlockElement::GetList(
			[ 'ID' => 'ASC' ],
			$elementFilter,
			false,
			[ 'nTopCount' => $pageSize ],
			$querySelect['ELEMENT']
		);

		while ($element = $queryElementList->Fetch())
		{
			if ($isCatalogTypeCompatibility)
			{
				$parentList[$element['ID']] = $element;
				$elementList[$element['ID']] = $element;
			}
			else if ($hasOffers && $this->getElementCatalogType($element, $queryContext) === static::ELEMENT_TYPE_SKU)
			{
				$parentList[$element['ID']] = $element;
			}
			else
			{
				$elementList[$element['ID']] = $element;
			}

			$offset = (int)$element['ID'];
			$pageElementCount++;
		}

		if ($hasOffers && !empty($parentList))
		{
			$offerList = [];

			$skuPropertyKey = 'PROPERTY_' . $queryContext['OFFER_PROPERTY_ID'];
			$skuPropertyValueKey = $skuPropertyKey . '_VALUE';

			$offerSelect = $querySelect['OFFERS'];
			$offerSelect[] = $skuPropertyKey;

			$offerFilter = $queryFilter['OFFERS'];
			$offerFilter['=' . $skuPropertyKey] = array_keys($parentList);

			$this->extendQueryOfferListFilter($offerFilter, $queryContext, $isCatalogTypeCompatibility);

			$queryOfferList = \CIBlockElement::GetList(
				array(),
				$offerFilter,
				false,
				false,
				$offerSelect
			);

			while ($offer = $queryOfferList->Fetch())
			{
				$offerElementId = (int)$offer[$skuPropertyValueKey];

				if (isset($parentList[$offerElementId]))
				{
					$foundParents[$offerElementId] = true;
					$offer['PARENT_ID'] = $offerElementId;

					$offerList[$offer['ID']] = $offer;
				}
			}

			$this->processQueryResultOfferList($offerList, $queryContext, $isCatalogTypeCompatibility);

			$elementList += $offerList;
		}

		// release parents without offers

		foreach ($parentList as $parentId => $parent)
		{
			if (!isset($foundParents[$parentId]))
			{
				unset($parentList[$parentId]);
			}
			else if ($isCatalogTypeCompatibility)
			{
				if (isset($elementList[$parentId]))
				{
					unset($elementList[$parentId]);
				}
			}
		}

		return [
			'ELEMENT' => $elementList,
			'PARENT' => $parentList,
			'OFFSET' => $offset,
			'HAS_NEXT' => ($pageElementCount >= $pageSize) // iblock distinct
		];
	}

	/**
	 * ���������� ��������� �������������� �� ���� ���
	 *
	 * @param $limit int|null
	 * @param $context array
	 *
	 * @return int
	 */
	protected function getQueryElementListPageSize($context, $limit = null)
	{
		if ($limit > 0)
		{
			$result = (int)$limit;
		}
		else
		{
			$parameter = (int)$this->getParameter('offerPageSize');
			$option = (int)Feed\Config::getOption('export_run_offer_page_size');

			if ($parameter > 0)
			{
				$result = $parameter;
			}
			else if ($option > 0)
			{
				$result = $option;
			}
			else
			{
				$result = $context['HAS_OFFER'] ? 50 : 100;
			}
		}

		return $result;
	}

	/**
	 * @param      $filter
	 * @param      $context
	 * @param bool $isCatalogTypeCompatibility
	 */
	protected function extendQueryElementListFilter(&$filter, $context, $isCatalogTypeCompatibility = false)
	{
		if (empty($context['IGNORE_EXCLUDE']))
		{
			$filter[] = [
				'!ID' => $this->getQueryExcludeFilter($context)
			];
		}
	}

	/**
	 * @param      $filter
	 * @param      $context
	 * @param bool $isCatalogTypeCompatibility
	 */
	protected function extendQueryOfferListFilter(&$filter, $context, $isCatalogTypeCompatibility = false)
	{
		if (!$isCatalogTypeCompatibility && empty($context['IGNORE_EXCLUDE']))
		{
			$filter[] = [
				'!ID' => $this->getQueryExcludeFilter($context)
			];
		}
	}

	/**
	 * @param      $offerList
	 * @param      $context
	 * @param bool $isCatalogTypeCompatibility
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 */
	protected function processQueryResultOfferList(&$offerList, $context, $isCatalogTypeCompatibility = false)
	{
		if ($isCatalogTypeCompatibility && !empty($offerList) && empty($context['IGNORE_EXCLUDE']))
		{
			$storageDataClass = $this->getStorageDataClass();
			$storageReadyFilter = $this->getStorageReadyFilter($context);
			$offerIds = array_keys($offerList);

			foreach (array_chunk($offerIds, 500) as $offerIdsChunk)
			{
				$storageReadyFilter['@ELEMENT_ID'] = $offerIdsChunk;

				$queryReadyOffers = $storageDataClass::getList([
					'filter' => $storageReadyFilter,
					'select' => [ 'ELEMENT_ID' ]
				]);

				while ($readyOffer = $queryReadyOffers->fetch())
				{
					if (isset($offerList[$readyOffer['ELEMENT_ID']]))
					{
						unset($offerList[$readyOffer['ELEMENT_ID']]);
					}
				}
			}
		}
	}

	/**
	 * ��������� ������� ��� ��������
	 *
	 * @param $sourceFilterList
	 * @param $sourceSelectList
	 * @param $queryContext
	 *
	 * @return array
	 * @throws \Bitrix\Main\ObjectNotFoundException
	 */
	protected function makeQueryFilter($sourceFilterList, $sourceSelectList, $queryContext, $changesFilter = null)
	{
		$iblockIds = [
			'ELEMENT' => $queryContext['IBLOCK_ID'],
			'OFFERS' => $queryContext['OFFER_IBLOCK_ID']
		];
		$isOfferSubQueryInitialized = false;

		// extend filters by sourceFilter

		$result = $this->convertSourceFilterToQuery($sourceFilterList, $sourceSelectList, $iblockIds);

		if (!isset($result['ELEMENT']))
		{
			$result['ELEMENT'] = $this->getFilterDefaults('ELEMENT', $iblockIds['ELEMENT']);
		}

		// extend by changes filter

		if (!empty($changesFilter))
		{
			foreach ($changesFilter as $entityType => $entityFilter)
			{
				if (!isset($result[$entityType]))
				{
					$result[$entityType] = $this->getFilterDefaults($entityType, $iblockIds[$entityType]);
				}

				$result[$entityType][] = $entityFilter;
			}
		}

		// catalog filter

		if (!empty($result['CATALOG']))
		{
			if (empty($iblockIds['OFFERS'])) // hasn't offers
			{
				$result['ELEMENT'][] = $result['CATALOG'];
			}
			else if (!empty($result['OFFERS'])) // has required offers
			{
				$result['OFFERS'][] = $result['CATALOG'];
			}
			else if (!empty($queryContext['OFFER_ONLY']))
			{
				$result['OFFERS'] = $this->getFilterDefaults('OFFERS', $iblockIds['OFFERS']);
				$result['OFFERS'][] = $result['CATALOG'];
			}
			else
			{
				$isOfferSubQueryInitialized = true;
				$catalogOfferFilter = $this->getFilterDefaults('OFFERS', $iblockIds['OFFERS']);
				$catalogOfferFilter[] = $result['CATALOG'];

				// element match catalog condition or has offers match condition

				$result['ELEMENT'][] = [
					'LOGIC' => 'OR',
					$result['CATALOG'],
					[
						'ID' => \CIBlockElement::SubQuery('PROPERTY_' . $queryContext['OFFER_PROPERTY_ID'], $catalogOfferFilter)
					]
				];

				// filter offers by catalog rules

				$result['OFFERS'] = $catalogOfferFilter;
			}
		}

		// offer subquery for elements

		if (!empty($iblockIds['OFFERS']))
		{
			if (!$isOfferSubQueryInitialized && !empty($result['OFFERS']) && !$this->isIgnoreOfferSubQuery($queryContext))
			{
				$result['ELEMENT'][] = [
					'ID' => \CIBlockElement::SubQuery('PROPERTY_' . $queryContext['OFFER_PROPERTY_ID'], $result['OFFERS']),
				];
			}
			else if (!isset($result['OFFERS']))
			{
				$result['OFFERS'] = $this->getFilterDefaults('OFFERS', $iblockIds['OFFERS']);
			}
		}

		return $result;
	}

	protected function convertSourceFilterToQuery($sourceFilterList, $sourceSelectList, $iblockIds, $isChild = false)
	{
		$result = [];
		$logic = null;

		foreach ($sourceFilterList as $sourceName => $sourceFilter)
		{
			$queryFilter = null;

			if ($sourceName === 'LOGIC')
			{
				$logic = (string)$sourceFilter;
			}
			else if (is_numeric($sourceName))
			{
				$queryFilter = $this->convertSourceFilterToQuery($sourceFilter, $sourceSelectList, $iblockIds, true);
			}
			else
			{
				$source = $this->getSource($sourceName);

				if ($source->isFilterable())
				{
					$sourceSelect = isset($sourceSelectList[$sourceName]) ? $sourceSelectList[$sourceName] : [];

					$queryFilter = $source->getQueryFilter($sourceFilter, $sourceSelect);
				}
			}

			if ($queryFilter !== null)
			{
				foreach ($queryFilter as $chainType => $filter)
				{
					if (!empty($filter))
					{
						if (isset($result[$chainType]))
						{
							// nothing
						}
						else if ($isChild)
						{
							$result[$chainType] = ($logic !== null ? [ 'LOGIC' => $logic ] : []);
						}
						else
						{
							$result[$chainType] = $this->getFilterDefaults($chainType, $iblockIds[$chainType]);
						}

						$result[$chainType][] = $filter;
					}
				}
			}
		}

		return $result;
	}

	/**
	 * ��������� ��� ����������� ��������
	 *
	 * @param $queryFilter
	 * @param $queryContext
	 *
	 * @return Feed\Export\Run\Helper\ExcludeFilter
	 */
	protected function getQueryExcludeFilter($queryContext)
	{
		$primary = $this->getQueryExcludeFilterPrimary($queryContext);

		if (!isset($this->queryExcludeFilterList[$primary]))
		{
			$this->queryExcludeFilterList[$primary] = new Feed\Export\Run\Helper\ExcludeFilter(
				$this->getStorageDataClass(),
				'ELEMENT_ID',
				$this->getStorageReadyFilter($queryContext)
			);
		}

		return $this->queryExcludeFilterList[$primary];
	}

	/**
	 * ���� ��� ������� ����������
	 *
	 * @param $queryContext
	 *
	 * @return int
	 */
	protected function getQueryExcludeFilterPrimary($queryContext)
	{
		return (int)$queryContext['IBLOCK_LINK_ID'];
	}

	/**
	 * ������ �� ������� ���������
	 *
	 * @param $queryContext array
	 * @param $isNeedFull bool
	 *
	 * @return array
	 */
	protected function getStorageReadyFilter($queryContext, $isNeedFull = false)
	{
		$filter = [
			'=SETUP_ID' => $queryContext['SETUP_ID']
		];

		if (isset($queryContext['IBLOCK_LINK_ID']))
		{
			$filter['=IBLOCK_LINK_ID'] = $queryContext['IBLOCK_LINK_ID'];
		}

		if (!$isNeedFull)
		{
			switch ($this->getRunAction())
			{
				case 'change':
				case 'refresh':
					$filter['>=TIMESTAMP_X'] = $this->getParameter('initTime');
				break;
			}
		}

		return $filter;
	}

	/**
	 * ��������� select ��� ��������
	 *
	 * @param $sourceSelect
	 * @param $context
	 *
	 * @return array
	 * @throws \Bitrix\Main\ObjectNotFoundException
	 */
	protected function makeQuerySelect($sourceSelect, $context)
	{
		$result = [
			'ELEMENT' => $this->getSelectDefaults('ELEMENT', $context),
			'OFFERS' => $this->getSelectDefaults('OFFERS', $context)
		];

		foreach ($sourceSelect as $sourceType => $sourceFields)
		{
			$source = $this->getSource($sourceType);
			$querySelect = $source->getQuerySelect($sourceFields);

			foreach ($querySelect as $chainType => $fields)
			{
				if (!empty($fields))
				{
					if (!isset($result[$chainType]))
					{
						$result[$chainType] = [];
					}

					foreach ($fields as $field)
					{
						if (!in_array($field, $result[$chainType]))
						{
							$result[$chainType][] = $field;
						}
					}
				}
			}
		}

		if (empty($result['CATALOG']))
		{
			// nothing
		}
		else if (!empty($context['OFFER_ONLY']))
		{
			$result['OFFERS'] = array_merge($result['OFFERS'], $result['CATALOG']);
		}
		else
		{
			$result['ELEMENT'] = array_merge($result['ELEMENT'], $result['CATALOG']);
			$result['OFFERS'] = array_merge($result['OFFERS'], $result['CATALOG']);
		}

		return $result;
	}

	/**
	 * ���������� ��� �������� ��������� ��� ���������
	 *
	 * @param $element
	 * @param $context
	 *
	 * @return int
	 */
	protected function getElementCatalogType($element, $context)
	{
		$result = static::ELEMENT_TYPE_PRODUCT;

		if (!empty($context['OFFER_ONLY']))
		{
			$result = static::ELEMENT_TYPE_SKU;
		}
		else if (isset($element['CATALOG_TYPE']))
		{
			$result = (int)$element['CATALOG_TYPE'];
		}
		else if (
			array_key_exists('CATALOG_TYPE', $element)
			&& !empty($context['OFFER_IBLOCK_ID'])
		)
		{
			$result = static::ELEMENT_TYPE_SKU;
		}

		return $result;
	}

	/**
	 * ���������� ������ "Select �� ����������" �� ��������� �������� ����
	 *
	 * @param $tagDescriptionList
	 *
	 * @return array
	 */
	protected function getSourceSelect($tagDescriptionList)
	{
		$result = [];
		$childKeys = [
			'ATTRIBUTES',
			'SETTINGS'
		];

		foreach ($tagDescriptionList as $tagName => $tagSourceValue)
		{
			if (isset($tagSourceValue['VALUE']['TYPE']) && isset($tagSourceValue['VALUE']['FIELD']))
			{
				$sourceType = $tagSourceValue['VALUE']['TYPE'];
				$sourceField = $tagSourceValue['VALUE']['FIELD'];

				if (!isset($result[$sourceType]))
				{
					$result[$sourceType] = [];
				}

				if (!in_array($sourceField, $result[$sourceType]))
				{
					$result[$sourceType][] = $sourceField;
				}
			}

			foreach ($childKeys as $childKey)
			{
				if (isset($tagSourceValue[$childKey]) && is_array($tagSourceValue[$childKey]))
				{
					foreach ($tagSourceValue[$childKey] as $attributeValueSource)
					{
						if (
							isset($attributeValueSource['TYPE'])
							&& $attributeValueSource['TYPE'] !== Feed\Export\Entity\Manager::TYPE_TEXT
							&& !empty($attributeValueSource['FIELD'])
						)
						{
							$sourceType = $attributeValueSource['TYPE'];
							$sourceField = $attributeValueSource['FIELD'];

							if (!isset($result[$sourceType]))
							{
								$result[$sourceType] = [];
							}

							if (!in_array($sourceField, $result[$sourceType]))
							{
								$result[$sourceType][] = $sourceField;
							}
						}
					}
				}
			}
		}

		return $result;
	}

	protected function sortSourceSelect(&$sourceSelect)
	{
		$order = [];

		foreach ($sourceSelect as $sourceType => $sourceFields)
		{
			$source = $this->getSource($sourceType);
			$order[$sourceType] = $source->getOrder();
		}

		uksort($sourceSelect, function($aType, $bType) use ($order) {
			$aOrder = $order[$aType];
			$bOrder = $order[$bType];

			if ($aOrder === $bOrder) { return 0; }

			return ($aOrder < $bOrder ? -1 : 1);
		});
	}

	/**
	 * ���������� ������ "�������� �� ����������" �� ��������� ��������
	 *
	 * @param Feed\Export\IblockLink\Model $iblockLink
	 * @param $iblockContext array
	 * @param $isNeedAll bool|null
	 *
	 * @return array
	 */
	protected function getSourceFilterList(Feed\Export\IblockLink\Model $iblockLink, $iblockContext, $isNeedAll = null)
	{
		$result = [];
		$filterCollection = $iblockLink->getFilterCollection();
		$isFirstFilter = true;

		/** @var Feed\Export\Filter\Model $filterModel */
		foreach ($filterCollection as $filterModel)
		{
			$sourceFilter = $filterModel->getSourceFilter();
			$result[] = [
				'ID' => $filterModel->getInternalId(),
				'FILTER' => $sourceFilter,
				'CONTEXT' => $filterModel->getContext(true) + [ 'IGNORE_EXCLUDE' => $isFirstFilter ]
			];

			$isFirstFilter = false;
		}

		if ($isNeedAll === null)
		{
			$isNeedAll = $iblockLink->isExportAll();
		}

		if ($isNeedAll)
		{
			$result[] = [
				'ID' => null,
				'FILTER' => [],
				'CONTEXT' => [ 'IGNORE_EXCLUDE' => $isFirstFilter ]
			];
		}

		return $result;
	}

	/**
	 * ���� ��� ������� �� ���������
	 *
	 * @param $entityType
	 * @param $context
	 *
	 * @return array
	 */
	protected function getSelectDefaults($entityType, $context)
	{
		switch ($entityType)
		{
			case 'ELEMENT':
				$result = [ 'IBLOCK_ID',  'ID' ];

				if (
					isset($context['OFFER_IBLOCK_ID']) // has offers
					&& empty($context['OFFER_ONLY']) // has not only offers
					&& !$this->isCatalogTypeCompatibility($context) // is valid catalog_type
				)
				{
					$result[] = 'CATALOG_TYPE';
				}
			break;

			case 'OFFERS':
				$result = [ 'IBLOCK_ID', 'ID' ];
			break;

			default:
				$result = [];
			break;
		}

		return $result;
	}

	/**
	 * ������ ��� ������� �� ���������
	 *
	 * @param $iblockId
	 *
	 * @return array
	 */
	protected function getFilterDefaults($entityType, $iblockId)
	{
		$result = null;

		switch ($entityType)
		{
			case 'ELEMENT':
			case 'OFFERS':
				$result = [
					'IBLOCK_ID' => $iblockId,
					'ACTIVE' => 'Y',
					'ACTIVE_DATE' => 'Y',
				];
			break;

			default:
				$result = [];
			break;
		}

		return $result;
	}

    /**
     * @param $sourceSelect
     * @param $elementList
     * @param $parentList
     * @param $queryContext
     * @return array
     * @throws Main\ObjectNotFoundException
     */
	protected function extractElementListValues($sourceSelect, $elementList, $parentList, $queryContext)
	{
		$result = [];
		$conflictList = $this->getProcessor()->getConflicts();

		foreach ($sourceSelect as $sourceType => $sourceFields)
		{
			$source = $this->getSource($sourceType);
			$sourceValues = $source->getElementListValues($elementList, $parentList, $sourceFields, $queryContext, $result);
			$sourceConflicts = (isset($conflictList[$sourceType]) ? $conflictList[$sourceType] : null);

			foreach ($sourceValues as $elementId => $elementValues)
			{
				if (!isset($result[$elementId]))
				{
					$result[$elementId] = [];
				}

				if ($sourceConflicts !== null)
				{
					foreach ($sourceConflicts as $fieldName => $conflictAction)
					{
						if (isset($elementValues[$fieldName]))
						{
							$elementValues[$fieldName] = $this->applyValueConflict($elementValues[$fieldName], $conflictAction);
						}
					}
				}

				$result[$elementId][$sourceType] = $elementValues;
			}
		}

		return $result;
	}

    /**
     * @param $type
     * @return Feed\Export\Entity\Reference\Source
     * @throws Main\ObjectNotFoundException
     */
	protected function getSource($type)
	{
		return Feed\Export\Entity\Manager::getSource($type);
	}

	/**
	 * ���� CATALOG_TYPE �������� �������� ���������� "����� �� ����� �������� �����������"
	 *
	 * @param $context
	 *
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	protected function isCatalogTypeCompatibility($context)
	{
		$result = false;

		if (!isset($context['OFFER_IBLOCK_ID'])) // hasn't offers
		{
			$result = false;
		}
		else if (!empty($context['OFFER_ONLY'])) // has only offers
		{
			$result = false;
		}
		else if ($this->isCatalogTypeCompatibility !== null) // already fetched
		{
			$result = $this->isCatalogTypeCompatibility;
		}
		else
		{
			$selfOption = (string)Feed\Config::getOption('export_offer_catalog_type_compatibility');
			$catalogVersion = Main\ModuleManager::getVersion('catalog');

			if ($selfOption !== '') // has self module option
			{
				$result = ($selfOption === 'Y');
			}
			else if ($catalogVersion !== false && CheckVersion('15.99.99', $catalogVersion)) // module catalog version less 16.0.0
			{
				$result = true;
			}
			else if (Main\Config\Option::get('catalog', 'show_catalog_tab_with_offers') === 'Y') // catalog tab open for product with offers
			{
				$result = true;
			}

			$this->isCatalogTypeCompatibility = $result;
		}

		return $result;
	}

	/**
	 * ������������ ��� ��������, ��� ����������� �� ������� �� �������� ������������
	 *
	 * @param $context
	 *
	 * @return bool
	 */
	protected function isIgnoreOfferSubQuery($context)
	{
		return (
			$this->getName() === 'offer'
			&& Feed\Config::getOption('export_offer_process_all_elements', 'N') === 'Y'
			&& !$this->isCatalogTypeCompatibility($context)
		);
	}
}
