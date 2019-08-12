<?php

namespace Ligacom\Feed\Component\Setup;

use Bitrix\Main;
use Ligacom\Feed;

Main\Localization\Loc::loadMessages(__FILE__);

class EditForm extends Feed\Component\Model\EditForm
{
	public function modifyRequest($request, $fields)
	{
		$result = parent::modifyRequest($request, $fields);
		$hasIblockRequest = isset($request['IBLOCK']);
		$hasIblockLinkRequest = isset($request['IBLOCK_LINK']);

		if ($hasIblockRequest || $hasIblockLinkRequest)
		{
			$iblockIds = $hasIblockRequest ? (array)$request['IBLOCK'] : [];
			$iblockIdsMap = array_flip($iblockIds);
			$usedIblockIds = [];
			$result['IBLOCK_LINK'] = $hasIblockLinkRequest ? (array)$request['IBLOCK_LINK'] : [];

			foreach ($result['IBLOCK_LINK'] as $iblockLinkKey => $iblockLink)
			{
				$iblockId = (int)$iblockLink['IBLOCK_ID'];

				if ($iblockId > 0 && isset($iblockIdsMap[$iblockId]))
				{
					$usedIblockIds[$iblockId] = true;
				}
				else
				{
					unset($result['IBLOCK_LINK'][$iblockLinkKey]);
				}
			}

			foreach ($iblockIds as $iblockId)
			{
				if (!isset($usedIblockIds[$iblockId]))
				{
					$result['IBLOCK_LINK'][] = [
						'IBLOCK_ID' => $iblockId
					];
				}
			}
		}

		return $result;
	}

	public function load($primary, array $select = [], $isCopy = false)
	{
		$result = parent::load($primary, $select, $isCopy);

		if ($isCopy)
		{
			$copyNameMarker = Feed\Config::getLang('COMPONENT_SETUP_EDIT_FORM_COPY_NAME_MARKER');

			if (isset($result['NAME']) && stripos($result['NAME'], $copyNameMarker) === false)
			{
				$result['NAME'] .= ' ' . $copyNameMarker;
			}

			if (isset($result['FILE_NAME']))
			{
				$result['FILE_NAME'] = null;
			}
		}

		return $result;
	}

	public function validate($data, array $fields = null)
	{
		$result = parent::validate($data, $fields);

		$this->validateIblock($result, $data, $fields);
		$this->validateDelivery($result, $data, $fields);
		$this->validateFilterCondition($result, $data, $fields);

		return $result;
	}

	protected function validateIblock(Main\Entity\Result $result, $data, array $fields = null)
	{
		if (empty($data['IBLOCK_LINK']))
		{
			$result->addError(new Feed\Error\EntityError(
				Feed\Config::getLang('COMPONENT_SETUP_EDIT_FORM_ERROR_IBLOCK_EMPTY'),
				0,
				[ 'FIELD' => 'IBLOCK' ]
			));
		}
	}

	protected function validateDelivery(Main\Entity\Result $result, $data, array $fields = null)
	{
		if (isset($fields['DELIVERY'])) // has delivery in validation list
		{
			$deliveryTypeList = [
				Feed\Export\Delivery\Table::DELIVERY_TYPE_DELIVERY
			];

			foreach ($deliveryTypeList as $deliveryType)
			{
				if (empty($data['DELIVERY']) || !$this->isValidDeliveryDataList($data['DELIVERY'], $deliveryType)) // and empty primary delivery
				{
					$hasChildDeliveryOptions = false;

					foreach ($data['IBLOCK_LINK'] as $iblockLink)
					{
						if (!empty($iblockLink['DELIVERY']) && $this->isValidDeliveryDataList($iblockLink['DELIVERY'], $deliveryType))
						{
							$hasChildDeliveryOptions = true;
							break;
						}
						else if (!empty($iblockLink['FILTER']))
						{
							foreach ($iblockLink['FILTER'] as $filter)
							{
								if (!empty($filter['DELIVERY']) && $this->isValidDeliveryDataList($filter['DELIVERY'], $deliveryType))
								{
									$hasChildDeliveryOptions = true;
									break 2;
								}
							}
						}
					}

					if ($hasChildDeliveryOptions)
					{
						$result->addError(new Feed\Error\EntityError(
							Feed\Config::getLang('COMPONENT_SETUP_EDIT_FORM_ERROR_CHILD_DELIVERY_OPTIONS_WITHOUT_ROOT'),
							0,
							[ 'FIELD' => 'DELIVERY' ]
						));
						break;
					}
				}
			}
		}
	}

	protected function validateFilterCondition(Main\Entity\Result $result, $data, array $fields = null)
	{
		if (!empty($data['IBLOCK_LINK']))
		{
			$isNeedExportAllValidation = false;
			$hasNotEmptyIblockLink = false;

			foreach ($data['IBLOCK_LINK'] as $iblockLinkIndex => $iblockLink)
			{
				$filterFieldName = 'IBLOCK_LINK_' . $iblockLinkIndex . '_FILTER';
				$filterInputName = 'IBLOCK_LINK[' . $iblockLinkIndex . '][FILTER]';
				$exportAllFieldName = 'IBLOCK_LINK_' . $iblockLinkIndex . '_EXPORT_ALL';
				$hasNotEmptyFilter = false;

				if (isset($fields[$filterFieldName]) && !empty($iblockLink['FILTER']))
				{
					foreach ($iblockLink['FILTER'] as $filterIndex => $filter)
					{
						$hasValidCondition = false;

						if (!empty($filter['FILTER_CONDITION']))
						{
							foreach ($filter['FILTER_CONDITION'] as $filterCondition)
							{
								if (Feed\Export\FilterCondition\Table::isValidData($filterCondition))
								{
									$hasValidCondition = true;
									break;
								}
							}
						}

						if ($hasValidCondition)
						{
							$hasNotEmptyFilter = true;
						}
						else
						{
							$result->addError(new Feed\Error\EntityError(
								Feed\Config::getLang('COMPONENT_SETUP_EDIT_FORM_ERROR_FILTER_CONDITION_EMPTY'),
								0,
								[ 'FIELD' => $filterInputName ]
							));
							break 2;
						}
					}
				}

				if (isset($fields[$exportAllFieldName]))
				{
					$isNeedExportAllValidation = true;
					$isExportAll = (!empty($iblockLink['EXPORT_ALL']) && (string)$iblockLink['EXPORT_ALL'] === Feed\Export\Setup\Table::BOOLEAN_Y);

					if ($isExportAll || $hasNotEmptyFilter)
					{
						$hasNotEmptyIblockLink = true;
					}
				}
			}

			if ($isNeedExportAllValidation && !$hasNotEmptyIblockLink)
			{
				$result->addError(new Feed\Error\EntityError(
					Feed\Config::getLang('COMPONENT_SETUP_EDIT_FORM_ERROR_IBLOCK_LINK_EXPORT_EMPTY')
				));
			}
		}
	}

	public function extend($data, array $select = [])
	{
		$result = $data;

		if (!isset($result['FILE_NAME']) || trim($result['FILE_NAME']) === '')
		{
			$result['FILE_NAME'] = 'export_' . randString(3) . '.xml';
		}

		if (!empty($result['IBLOCK_LINK']))
		{
			$setup = $this->loadSetupModel($data);
			$setupContext = $setup->getContext();

			foreach ($result['IBLOCK_LINK'] as &$iblockLink)
			{
				$iblockId = isset($iblockLink['IBLOCK_ID']) ? (int)$iblockLink['IBLOCK_ID'] : null;
				$iblockLink['CONTEXT'] = Feed\Export\Entity\Iblock\Provider::getContext($iblockId) + $setupContext;
			}
			unset($iblockLink);
		}

		return $result;
	}

	public function processAjaxAction($action, $data)
	{
		$result = null;

		switch ($action)
		{
			case 'filterCount':
				session_write_close(); // release sessions

				$result = $this->ajaxActionFilterCount($data);
			break;

			default:
				$result = parent::processAjaxAction($action, $data);
			break;
		}

		return $result;
	}

	public function ajaxActionFilterCount($data, $baseName = 'IBLOCK_LINK')
	{
		$request = Main\Context::getCurrent()->getRequest();

		$setup = $this->loadSetupModel($data);
		$offset = null;
		$offsetName = $request->getPost('offsetName');

		if ($offsetName !== null && preg_match('/^' . $baseName . '\[(\d+)\]\[FILTER\](?:\[(\d+)\])?/', $offsetName, $offsetNameMatches))
		{
			$offset = $offsetNameMatches[1] . (isset($offsetNameMatches[2]) ? ':' . $offsetNameMatches[2] : '');
		}

		return [ 'status' => 'ok' ] + $this->getFilterCount($setup, $offset, $baseName);
	}

	public function getFilterCount(Feed\Export\Setup\Model $setup, $offset = null, $baseName = 'IBLOCK_LINK')
	{
		/** @var $offerStep Feed\Export\Run\Steps\Offer */
		$processor = new Feed\Export\Run\Processor($setup);
		$offerStep = Feed\Export\Run\Manager::getStepProvider(
			Feed\Export\Run\Manager::ENTITY_TYPE_OFFER,
			$processor
		);

		$processor->loadModules();

		$filterCountList = $offerStep->getCount($offset, true);
		$iblockLinkIndex = 0;
		$result = [
			'countList' => [],
			'warningList' => []
		];

		foreach ($setup->getIblockLinkCollection() as $iblockLink)
		{
			$iblockLinkId = $iblockLink->getInternalId();
			$filterIndex = 0;

			if ($filterCountList->hasCount($iblockLinkId))
			{
				$inputName = $baseName . '[' . $iblockLinkIndex . '][FILTER]';
				$warning = $filterCountList->getCountWarning($iblockLinkId);

				$result['countList'][$inputName] = $filterCountList->getCount($iblockLinkId);
				$result['warningList'][$inputName] = $warning ? $warning->getMessage() : null;
			}

			/** @var Feed\Export\Filter\Model $filterModel */
			foreach ($iblockLink->getFilterCollection() as $filterModel)
			{
				$filterInternalId = $filterModel->getInternalId();
				$filterCountKey = $iblockLinkId . ':' . $filterInternalId;

				if ($filterCountList->hasCount($filterCountKey))
				{
					$inputName = $baseName . '[' . $iblockLinkIndex . '][FILTER][' . $filterIndex . '][FILTER_CONDITION]';
					$warning = $filterCountList->getCountWarning($filterCountKey);

					$result['countList'][$inputName] = $filterCountList->getCount($filterCountKey);
					$result['warningList'][$inputName] = $warning ? $warning->getMessage() : null;
				}

				$filterIndex++;
			}

			$iblockLinkIndex++;
		}

		return $result;
	}

	/**
	 * @param $data
	 *
	 * @return Feed\Export\Setup\Model
	 */
	protected function loadSetupModel($data)
	{
		/** @var Feed\Export\Setup\Model $modelClassName */
		$modelClassName = $this->getModelClass();

		return $modelClassName::initialize($data);
	}

	protected function isValidDeliveryDataList($dataList, $deliveryType)
	{
		$result = false;

		if (is_array($dataList))
		{
			foreach ($dataList as $data)
			{
				$isMatchType = (isset($data['DELIVERY_TYPE']) && $data['DELIVERY_TYPE'] === $deliveryType);

				if ($isMatchType && Feed\Export\Delivery\Table::isValidData($data))
				{
					$result = true;
					break;
				}
			}
		}

		return $result;
	}
}