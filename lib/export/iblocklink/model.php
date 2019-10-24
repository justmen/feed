<?php

namespace Ligacom\Feed\Export\IblockLink;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Ligacom\Feed;

Loc::loadMessages(__FILE__);

class Model extends Feed\Reference\Storage\Model
{
	protected $iblockContext;
	protected $tagDescriptionList;

	public function getTagDescription($tagName)
	{
		$result = null;
		$tagDescriptionList = $this->getTagDescriptionList();

		foreach ($tagDescriptionList as $tagDescription)
		{
			if ($tagDescription['TAG'] === $tagName)
			{
				$result = $tagDescription;
				break;
			}
		}

		return $result;
	}

	public function getTagDescriptionList()
	{
		if ($this->tagDescriptionList === null)
		{
			$this->tagDescriptionList = $this->createTagDescriptionList();
		}

		return $this->tagDescriptionList;
	}

	protected function createTagDescriptionList()
	{
		$paramCollection = $this->getParamCollection();
		$result = [];
		$textType = Feed\Export\Entity\Manager::TYPE_TEXT;

		/** @var Feed\Export\Param\Model $param */
		foreach ($paramCollection as $param)
		{
			$paramValueCollection = $param->getValueCollection();
			$tagResult = [
				'TAG' => $param->getField('XML_TAG'),
				'VALUE' => null,
				'ATTRIBUTES' => [],
				'SETTINGS' => $param->getSettings()
			];

			/** @var Feed\Export\ParamValue\Model $paramValue */
			foreach ($paramValueCollection as $paramValue)
			{
				$sourceType = $paramValue->getSourceType();
				$sourceField = $paramValue->getSourceField();
				$sourceMap = (
					$sourceType === $textType
						? [ 'VALUE' => $sourceField ]
						: [ 'TYPE' => $sourceType, 'FIELD' => $sourceField ]
				);

				if ($paramValue->isAttribute())
				{
					$attributeName = $paramValue->getAttributeName();

					$tagResult['ATTRIBUTES'][$attributeName] = $sourceMap;
				}
				else
				{
					$tagResult['VALUE'] = $sourceMap;
				}
			}

			$result[] = $tagResult;
		}

		return $result;
	}

	public function getSourceSelect()
	{
		$result = [];
		$paramCollection = $this->getParamCollection();

		/** @var Feed\Export\Param\Model $param */
		foreach ($paramCollection as $param)
		{
			$paramValueCollection = $param->getValueCollection();

			/** @var Feed\Export\ParamValue\Model $paramValue */
			foreach ($paramValueCollection as $paramValue)
			{
				$sourceType = $paramValue->getSourceType();
				$sourceField = $paramValue->getSourceField();

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

		$result = $this->extendSourceSelectByTemplate($result);

		return $result;
	}

	protected function extendSourceSelectByTemplate($sourceSelect)
	{
		$result = $sourceSelect;

		foreach ($sourceSelect as $sourceType => $sourceFields)
		{
			$source = Feed\Export\Entity\Manager::getSource($sourceType);

			if ($source->isTemplate() && Feed\Template\Engine::load())
			{
				foreach ($sourceFields as $sourceField)
				{
					$templateNode = Feed\Template\Engine::compileTemplate($sourceField);
					$templateSourceSelect = $templateNode->getSourceSelect();

					foreach ($templateSourceSelect as $templateSourceType => $templateSourceFields)
					{
						if (!isset($result[$templateSourceType]))
						{
							$result[$templateSourceType] = [];
						}

						foreach ($templateSourceFields as $templateSourceField)
						{
							if (!in_array($templateSourceField, $result[$templateSourceType]))
							{
								$result[$templateSourceType][] = $templateSourceField;
							}
						}
					}
				}
			}
		}

		return $result;
	}

	public function getUsedSources()
	{
		$result = $this->getSourceSelect();

		foreach ($this->getFilterCollection() as $filterModel)
		{
			$filterUserSources = $filterModel->getUsedSources();

			foreach ($filterUserSources as $sourceType)
			{
				if (!isset($result[$sourceType]))
				{
					$result[$sourceType] = true;
				}
			}
		}

		return array_keys($result);
	}

	public function getTrackSourceList()
	{
		$sourceList = $this->getUsedSources();
		$context = $this->getContext();
		$result = [];

		foreach ($sourceList as $sourceType)
		{
			$eventHandler = Feed\Export\Entity\Manager::getEvent($sourceType);

            $result[] = [
                'SOURCE_TYPE' => $sourceType,
                'SOURCE_PARAMS' => $eventHandler->getSourceParams($context)
            ];
		}

		return $result;
	}

	/**
	 * @return array
	 */
	public function getContext()
	{
		$result = [
			'IBLOCK_LINK_ID' => $this->getId(),
		];

		$result += $this->getIblockContext();

		$result = $this->mergeParentContext($result);

		return $result;
	}

	protected function mergeParentContext($selfContext)
	{
		$collection = $this->getCollection();
		$setup = $collection ? $collection->getParent() : null;
		$setupContext = $setup ? $setup->getContext() : null;
		$result = $selfContext;

		if (isset($setupContext))
		{
			$result += $setupContext;

			if (isset($setupContext['DELIVERY_OPTIONS']) && !isset($selfContext['DELIVERY_OPTIONS']))
			{
				unset($result['DELIVERY_OPTIONS']);
			}
		}

		return $result;
	}

	protected function getIblockContext()
	{
		if ($this->iblockContext === null)
		{
			$iblockId = $this->getIblockId();

			$this->iblockContext = Feed\Export\Entity\Iblock\Provider::getContext($iblockId);
		}

		return $this->iblockContext;
	}


	public function getIblockId()
	{
		return (int)$this->getField('IBLOCK_ID');
	}

	public function getOfferIblockId()
	{
		$iblockContext = $this->getIblockContext();

		return (isset($iblockContext['OFFER_IBLOCK_ID']) ? $iblockContext['OFFER_IBLOCK_ID'] : null);
	}

	public function getOfferPropertyId()
	{
		$iblockContext = $this->getIblockContext();

		return (isset($iblockContext['OFFER_PROPERTY_ID']) ? $iblockContext['OFFER_PROPERTY_ID'] : null);
	}

	public function isExportAll()
	{
		return $this->getField('EXPORT_ALL') === '1';
	}

	public function getSiteId()
	{
		$iblockContext = $this->getIblockContext();

		return $iblockContext['SITE_ID'];
	}

	public function hasIblockCatalog()
	{
		$iblockContext = $this->getIblockContext();

		return $iblockContext['HAS_CATALOG'];
	}

	public function isIblockCatalogOnlyOffers()
	{
		$iblockContext = $this->getIblockContext();

		return !empty($iblockContext['OFFER_ONLY']);
	}

	public function hasIblockOffers()
	{
		$iblockContext = $this->getIblockContext();

		return $iblockContext['HAS_OFFER'];
	}

	/**
	 * �������� ������ �������
	 *
	 * @return Table
	 */
	public static function getDataClass()
	{
		return Table::getClassName();
	}

	/**
	 * @return Feed\Export\Filter\Collection
	 */
	public function getFilterCollection()
	{
		return $this->getChildCollection('FILTER');
	}

	/**
	 * @return Feed\Export\Param\Collection
	 */
	public function getParamCollection()
	{
		return $this->getChildCollection('PARAM');
	}

	protected function getChildCollectionReference($fieldKey)
	{
		$result = null;

		switch ($fieldKey)
		{
			case 'FILTER':
				$result = Feed\Export\Filter\Collection::getClassName();
			break;

			case 'PARAM':
				$result = Feed\Export\Param\Collection::getClassName();
			break;
		}

		return $result;
	}
}