<?php

namespace Ligacom\Feed\Export\Run\Steps;

use Bitrix\Main;
use Ligacom\Feed;

Main\Localization\Loc::loadMessages(__FILE__);

abstract class Base
{
	const STORAGE_STATUS_FAIL = 1;
	const STORAGE_STATUS_SUCCESS = 2;
	const STORAGE_STATUS_INVALID = 3;
	const STORAGE_STATUS_DUPLICATE = 4;
	const STORAGE_STATUS_DELETE = 5;

	/** @var Feed\Export\Run\Processor */
	protected $processor = null;
	/** @var Feed\Export\Xml\Tag\Base */
	protected $tag = null;
	/** @var Feed\Export\Xml\Tag\Base[] */
	protected $typedTagList = [];
	/** @var string|null */
	protected $tagParentName = null;
	/** @var array */
	protected $tagPath = null;
	/** @var bool */
	protected $isAllowCopyPublic = false;
	/** @var string */
	protected $runAction = null;
	/** @var int|null*/
	protected $totalCount;

	public static function getStorageStatusTitle($status)
	{
		return Feed\Config::getLang('EXPORT_RUN_STEP_STORAGE_STATUS_' . $status);
	}

	public function __construct(Feed\Export\Run\Processor $processor)
	{
		$this->processor = $processor;
	}

	public function destroy()
	{
		$this->processor = null;
		$this->tag = null;
		$this->tagParentName = null;
	}

	/**
	 * �������� ���� ��� �������
	 *
	 * @return mixed
	 */
	abstract public function getName();

	/**
	 * �� �������� � ������ ��������
	 *
	 * @return bool
	 */
	public function isVirtual()
	{
		return false;
	}

	/**
	 * ������������ ��� � ��������� ���� �� ����������
	 */
	public function invalidate()
	{
		$context = $this->getContext();
		$changes = $this->getChanges();

		$this->invalidateDataStorage($changes, $context);
	}

	/**
	 * ������� ��� � ��������� ���� ���������
	 *
	 * @param $isStrict bool
	 */
	public function clear($isStrict = false)
	{
		$context = $this->getContext();

		$this->clearDataLog($context);
		$this->clearDataStorage($context);
	}

	public function getReadyCount()
	{
		return null;
	}

	public function getTotalCount()
	{
		return $this->totalCount;
	}

	public function setTotalCount($count)
	{
		$this->totalCount = ($count !== null ? (int)$count : null);
	}

	/**
	 * ���������� ������� ����� ��������
	 *
	 * @param $action
	 */
	protected function setRunAction($action)
	{
		$this->runAction = $action;
	}

	/**
	 * ������� ����� ��������
	 *
	 * @return string
	 */
	public function getRunAction()
	{
		return $this->runAction;
	}

	/**
	 * ���������� �� ��������� �������
	 *
	 * @param $action
	 *
	 * @return bool
	 */
	public function validateAction($action)
	{
		$result = true;
		$initTime = $this->getParameter('initTime');
		$isValidInitTime = ($initTime && $initTime instanceof Main\Type\Date);

		switch ($action)
		{
			case 'change':
				$changes = $this->getChanges();
				$result = ($isValidInitTime && !empty($changes));
			break;

			case 'refresh':
				$result = $isValidInitTime;
			break;
		}

		return $result;
	}

	/**
	 * ��������� ���
	 *
	 * @param $offset
	 *
	 * @return Ligacom\Feed\Result\Step
	 */
	abstract public function run($action, $offset = null);

    /**
     * @param array $tagValuesList
     * @param array $elementList
     * @param array $context
     * @param array|null $data
     * @param null $limit
     * @return array
     * @throws Main\ArgumentException
     * @throws Main\ObjectException
     * @throws Main\ObjectNotFoundException
     * @throws Main\ObjectPropertyException
     * @throws Main\SystemException
     */
	protected function writeData(
	    array $tagValuesList,
        array $elementList,
        array $context = [],
        array $data = null,
        $limit = null)
	{

		//todo сейчас этот функционал не используется - можно использовать для дополнительной обработки полей шага
       	$this->extendData($tagValuesList, $elementList, $context, $data);

        //todo описать как происходить формирование xmlElement у каждого элемента в $tagResultList
	    $tagResultList = $this->buildTagList($tagValuesList, $context);

	    //отправка письма (скорее всего удалить)
		$this->writeDataUserEvent($tagResultList, $elementList, $context, $data);

		$storageResultList = $this->writeDataStorage($tagResultList, $tagValuesList, $elementList, $context, $data, $limit);

		if (!$this->isVirtual())
		{
			$this->writeDataFile($tagResultList, $storageResultList, $context);
			$this->writeDataCopyPublic($tagResultList, $context);
		}

		$this->writeDataLog($tagResultList, $context);

		return $storageResultList;
	}

    /**
     * @param $tagValuesList
     * @param $elementList
     * @param array $context
     * @param array|null $data
     */
	protected function extendData($tagValuesList, $elementList, array $context = [], array $data = null)
	{
		$this->extendDataUserEvent($tagValuesList, $elementList, $context, $data);
	}

    /**
     * @param $tagValuesList
     * @param $elementList
     * @param array $context
     * @param array|null $data
     */
	protected function extendDataUserEvent($tagValuesList, $elementList, array $context = [], array $data = null)
	{
		$stepName = $this->getName();
		$moduleName = Feed\Config::getModuleName();
		$eventName = 'onExport' . ucfirst($stepName) . 'ExtendData';
		$eventData = [
			'TAG_VALUE_LIST' => $tagValuesList,
			'ELEMENT_LIST' => $elementList,
			'CONTEXT' => $context
		];

		if (isset($data))
		{
			$eventData += $data;
		}

		$event = new Main\Event($moduleName, $eventName, $eventData);
		$event->send();
	}

    /**
     * @param Feed\Result\XmlValue[] $tagValuesList
     * @param array $context
     * @return Feed\Result\XmlNode[]
     * @throws Main\ObjectNotFoundException
     */
	protected function buildTagList(array $tagValuesList, array $context = []) :array
	{
		$document = null;
		$isTypedTag = $this->isTypedTag();
		$result = [];

		foreach ($tagValuesList as $elementId => $tagValue)
		{
			$tagType = ($isTypedTag ? $tagValue->getType() : null);
			$tagData = $tagValue->getTagData();
			$tag = $this->getTag($tagType);

			if ($document === null)
			{
				$document = $tag->exportDocument();
			}

			$result[$elementId] = $tag->exportTag($tagData, $context, $document);
		}

		return $result;
	}

	/**
	 *
	 * @return array|null
	 */
	protected function getChanges()
	{
		$result = $this->getParameter('changes');

		return $this->filterChanges($result);
	}

	/**
	 *
	 * @param $changes
	 *
	 * @return array|null
	 */
	protected function filterChanges($changes)
	{
		$result = $changes;
		$ignoredTypeList = $this->getIgnoredTypeChanges();

		if ($result !== null && $ignoredTypeList !== null)
		{
			foreach ($result as $changeType => $entityIds)
			{
				if (isset($ignoredTypeList[$changeType]))
				{
					unset($result[$changeType]);
				}
			}
		}

		return $result;
	}

	/**
	 *
	 * @return array|null
	 */
	protected function getIgnoredTypeChanges()
	{
		return null;
	}


    /**
     * @param Feed\Result\XmlNode[] $tagResultList
     * @param $elementList
     * @param array $context
     * @param array|null $data
     */
	protected function writeDataUserEvent($tagResultList, $elementList, array $context = [], array $data = null)
	{
		$stepName = $this->getName();
		$moduleName = Feed\Config::getModuleName();
		$eventName = 'onExport' . ucfirst($stepName) . 'WriteData';
		$eventData = [
			'TAG_RESULT_LIST' => $tagResultList,
			'ELEMENT_LIST' => $elementList,
			'CONTEXT' => $context
		];

		if (isset($data))
		{
			$eventData += $data;
		}

		$event = new Main\Event($moduleName, $eventName, $eventData);
		$event->send();
	}

	/**

	 *
	 * @return Feed\Reference\Storage\Table
	 */
	protected function getStorageDataClass()
	{
		return null;
	}

	/**
	 *
	 *
	 * @return array
	 */
	protected function getStoragePrimaryList()
	{
		return [
			'SETUP_ID',
			'ELEMENT_ID'
		];
	}

	/**
	 *
	 *
	 * @return array
	 */
	protected function getStorageRuntime()
	{
		return [];
	}

	/**
	 * ������������� ���������� �������� �� ����������
	 *
	 * @param $changes
	 * @param $context
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 */
	protected function invalidateDataStorage($changes, $context)
	{
		$dataClass = $this->getStorageDataClass();
		$invalidateFilter = $this->getStorageChangesFilter($changes, $context);

		if ($dataClass && $invalidateFilter !== null)
		{
			/** @var \Bitrix\Main\Type\DateTime $initTime */
			$initTime = $this->getParameter('initTime');

			// filter

			$filter = [
				'=SETUP_ID' => $context['SETUP_ID'],
				'!=STATUS' => static::STORAGE_STATUS_DELETE
			];

			if (!empty($invalidateFilter))
			{
				$filter[] = $invalidateFilter;
			}

			// fields

			$fields = [
				'STATUS' => static::STORAGE_STATUS_INVALID
			];

			if ($initTime)
			{
				$updateTime = clone $initTime;
				$updateTime->add('-PT1S');

				$fields['TIMESTAMP_X'] = $updateTime;
			}

			$dataClass::updateBatch(
				[ 'filter' => $filter, 'runtime' => $this->getStorageRuntime() ],
				$fields
			);
		}
	}

    /**
     * @param $context
     * @throws \Exception
     */
	protected function clearDataStorage($context)
	{
		$dataClass = $this->getStorageDataClass();

		if ($dataClass)
		{
			$dataClass::deleteBatch([
				'filter' => [ '=SETUP_ID' => $context['SETUP_ID'] ]
			]);
		}
	}

    /**
     * @param $changes
     * @param $context
     * @return array
     */
	protected function getStorageChangesFilter($changes, $context)
	{
		return []; // invalidate all by default
	}

    /**
     * @param Feed\Result\XmlNode[] $tagResultList
     * @param $tagValuesList
     * @param $elementList
     * @param array $context
     * @param array|null $data
     * @param null $limit
     * @return array
     * @throws Main\ArgumentException
     * @throws Main\ObjectException
     * @throws Main\ObjectPropertyException
     * @throws Main\SystemException
     */
	protected function writeDataStorage(
	    array $tagResultList,
        $tagValuesList,
        $elementList,
        array $context = [],
        array $data = null,
        $limit = null)
	{
		$result = [];
		$dataClass = $this->getStorageDataClass();
		$useHashCollision = $this->useHashCollision();

		if ($dataClass)
		{
			$timestamp = new Main\Type\DateTime();
			$existMap = [];
			$hashList = [];
			$hashListMap = [];
			$needCheckHashList = [];
			$successCount = 0;
			$isVirtual = $this->isVirtual();

			// query exists

			$queryExistsFilter = $this->getExistDataStorageFilter($tagResultList, $tagValuesList, $elementList, $context, $data);

			$queryExists = $dataClass::getList([
				'filter' => $queryExistsFilter,
				'select' => [
					'ELEMENT_ID',
					'HASH'
				]
			]);

			while ($row = $queryExists->fetch())
			{
				$existMap[$row['ELEMENT_ID']] = $row['HASH'];
			}

			// hash list

			foreach ($tagResultList as $elementId => $tagResult)
			{
				if ($tagResult->isSuccess())
				{
					$hash = $this->getTagResultHash($tagResult, $useHashCollision);

					$hashList[$elementId] = $hash;

					if ($useHashCollision && !isset($hashListMap[$hash]))
					{
						$hashListMap[$hash] = $elementId;
					}
				}
			}

			// make update

			$fieldsList = [];

			foreach ($tagResultList as $elementId => $tagResult)
			{
				$rowId = null;
				$writeAction = null;
				$element = isset($elementList[$elementId]) ? $elementList[$elementId] : null;
				$tagValues = isset($tagValuesList[$elementId]) ? $tagValuesList[$elementId] : null;

				$fields = [
					'SETUP_ID' => $context['SETUP_ID'],
					'ELEMENT_ID' => $elementId, // not int, maybe currency
					'STATUS' => static::STORAGE_STATUS_FAIL,
					'HASH' => '',
					'TIMESTAMP_X' => $timestamp
				];

				if ($isVirtual)
				{
					$fields['CONTENTS'] = '';
				}

				$additionalData = $this->getStorageAdditionalData($tagResult, $tagValues, $element, $context, $data);

				if (!empty($additionalData))
				{
					$fields += $additionalData;
				}

				if ($tagResult->isSuccess())
				{
					$hash = $hashList[$elementId];

					if (!$useHashCollision)
					{
						$fields['STATUS'] = static::STORAGE_STATUS_SUCCESS;
						$fields['HASH'] = $hash;

						if ($isVirtual)
						{
							$fields['CONTENTS'] = $tagResult->getXmlContents();
						}
					}
					else if ($hashListMap[$hash] === $elementId) // hash is unique
					{
						$needCheckHashList[$hash] = $fields['ELEMENT_ID'];

						$fields['STATUS'] = static::STORAGE_STATUS_SUCCESS;
						$fields['HASH'] = $hash;

						if ($isVirtual)
						{
							$fields['CONTENTS'] = $tagResult->getXmlContents();
						}
					}
					else // match another hash
					{
						$fields['STATUS'] = static::STORAGE_STATUS_DUPLICATE;
						$fields['HASH'] = '';

						$tagResult->addError(new Feed\Error\XmlNode(
							Feed\Config::getLang('EXPORT_RUN_STEP_BASE_HASH_COLLISION', [
								'#ELEMENT_ID#' => $hashList[$hash]
							]),
							Feed\Error\XmlNode::XML_NODE_HASH_COLLISION
						));
					}
				}

				$fieldsList[] = $fields;
			}

			// check hash collision from already stored data

			if ($useHashCollision && !empty($needCheckHashList))
			{
				$duplicateHashList = $this->checkHashCollision($needCheckHashList, $context);

				if (!empty($duplicateHashList))
				{
					foreach ($fieldsList as &$fields)
					{
						if (
							$fields['STATUS'] === static::STORAGE_STATUS_SUCCESS
							&& isset($duplicateHashList[$fields['HASH']])
						)
						{
							$elementId = $fields['ELEMENT_ID'];
							$tagResult = $tagResultList[$elementId];

							$fields['STATUS'] = static::STORAGE_STATUS_DUPLICATE;
							$fields['HASH'] = '';

							if ($isVirtual)
							{
								$fields['CONTENTS'] = '';
							}

							$tagResult->addError(new Feed\Error\XmlNode(
								Feed\Config::getLang('EXPORT_RUN_STEP_BASE_HASH_COLLISION', [
									'#ELEMENT_ID#' => $duplicateHashList[$fields['HASH']]
								]),
								Feed\Error\XmlNode::XML_NODE_HASH_COLLISION
							));
						}
					}
					unset($fields);
				}
			}

			// write to db and build actions

			$fieldsCount = count($fieldsList);
			$writeChunkSize = $this->getWriteStorageChunkSize();

			for ($writeOffset = 0; $writeOffset < $fieldsCount; $writeOffset += $writeChunkSize)
			{
				$writeList = array_slice($fieldsList, $writeOffset, $writeChunkSize);

				// over limit check

				if ($limit !== null)
				{
					$loopSuccessCount = $successCount;

					foreach ($writeList as &$fields)
					{
						if ($fields['STATUS'] === static::STORAGE_STATUS_SUCCESS)
						{
							$loopSuccessCount++;

							if ($loopSuccessCount > $limit)
							{
								$fields['STATUS'] = static::STORAGE_STATUS_FAIL;
								$fields['HASH'] = '';

								if ($isVirtual)
								{
									$fields['CONTENTS'] = '';
								}
							}
						}
					}
					unset($fields);
				}

				// write

				$writeResult = $dataClass::addBatch($writeList, true);

				// process write result

				$isSuccessWrite = $writeResult->isSuccess();

				foreach ($writeList as $fields)
				{
					$elementId = $fields['ELEMENT_ID'];
					$prevHash = (isset($existMap[$elementId]) ? $existMap[$elementId] : '');
					$fileAction = null;

					if (
						!$isSuccessWrite // fail write to db
						&& $fields['STATUS'] === static::STORAGE_STATUS_SUCCESS // and going write to file
					)
					{
						$fields['STATUS'] = static::STORAGE_STATUS_FAIL;
						$fields['HASH'] = '';
					}

					// write action

					if ($fields['HASH'] !== $prevHash)
					{
						$prevFileAction = ($prevHash !== '' ? 'add' : 'delete');
						$newFileAction = ($fields['HASH'] !== '' ? 'add' : 'delete');

						if ($prevFileAction !== $newFileAction)
						{
							$fileAction = $newFileAction;
						}
						else if ($newFileAction === 'add')
						{
							$fileAction = 'update';
						}
					}

					if ($fields['STATUS'] === static::STORAGE_STATUS_SUCCESS)
					{
						$successCount++;
					}

					$result[$elementId] = [
						'ID' => $elementId,
						'STATUS' => $fields['STATUS'],
						'ACTION' => $fileAction
					];
				}
			}
		}

		return $result;
	}

	/**
	 * ������ ������ ��� ������ � ���������� ��������
	 *
	 * @return int
	 */
	protected function getWriteStorageChunkSize()
	{
		return (int)Feed\Config::getOption('export_write_storage_chunk_size') ?: 50;
	}

	/**
	 * ������ �� ��� ����������� ����������� ��� ��������� ���������
	 *
	 * @param $tagResultList
	 * @param $tagValuesList
	 * @param $elementList
	 * @param array $context
	 * @param array $data
	 *
	 * @return array
	 */
	protected function getExistDataStorageFilter($tagResultList, $tagValuesList, $elementList, array $context, array $data = null)
	{
		return [
			'=SETUP_ID' => $context['SETUP_ID'],
			'=ELEMENT_ID' => array_keys($tagResultList)
		];
	}

	/**
	 * ���� �� ������� ����������� �������� ������� ����
	 *
	 * @param array|null $context
	 *
	 * @return bool
	 */
	protected function hasDataStorageSuccess($context = null)
	{
		$dataClass = $this->getStorageDataClass();
		$result = false;

		if ($dataClass)
		{
			if ($context === null) { $context = $this->getContext(); }

			$readyFilter = $this->getStorageReadyFilter($context, true);
			$readyFilter['=STATUS'] = static::STORAGE_STATUS_SUCCESS;

			$query = $dataClass::getList([
				'filter' => $readyFilter,
				'limit' => 1
			]);

			if ($row = $query->fetch())
			{
				$result = true;
			}
		}

		return $result;
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
	 * �������������� ���������� ��� ���������� � ������� ����������� ��������
	 *
	 * @param $tagResult Feed\Result\XmlNode
	 * @param $tagValues Ligacom\Feed\Result\XmlValue
	 * @param $element array|null
	 * @param $context array
	 * @param $data array
	 *
	 * @return array
	 */
	protected function getStorageAdditionalData($tagResult, $tagValues, $element, $context, $data)
	{
		return null;
	}

	/**
	 * ��������� ���������� ����� ��� ��������
	 *
	 * @return bool
	 */
	protected function useHashCollision()
	{
		return false;
	}

	/**
	 * ��������� ������� ����������� ��������� � ���������� ������
	 *
	 * @param $hashList
	 * @param $context
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 */
	protected function checkHashCollision($hashList, $context)
	{
		$result = [];
		$dataClass = $this->getStorageDataClass();

		if ($dataClass && !empty($hashList))
		{
			$filter = [
				'=SETUP_ID' => $context['SETUP_ID'],
				'=STATUS' => static::STORAGE_STATUS_SUCCESS,
				'=HASH' => array_keys($hashList)
			];

			switch ($this->getRunAction())
			{
				case 'refresh':
					$filter['>=TIMESTAMP_X'] = $this->getParameter('initTime');
				break;
			}

			$query = $dataClass::getList([
				'filter' => $filter,
				'select' => [
					'ELEMENT_ID',
					'HASH'
				]
			]);

			while ($row = $query->fetch())
			{
				$hash = $row['HASH'];

				if (isset($hashList[$hash]) && $hashList[$hash] != $row['ELEMENT_ID'])
				{
					$result[$hash] = $row['ELEMENT_ID'];
				}
			}
		}

		return $result;
	}

	/**
	 * ��� ����������
	 *
	 * @param $tagResult Feed\Result\XmlNode
	 * @param $useHashCollision bool
	 *
	 * @return string
	 */
	protected function getTagResultHash($tagResult, $useHashCollision = false)
	{
		$result = '';
		$xmlContents = $tagResult->getXmlContents();

		if ($xmlContents !== null)
		{
			if ($useHashCollision) // remove id attr for check tag contents
			{
				$xmlContents = preg_replace('/^(<[^ ]+) id="[^"]*?"/', '$1', $xmlContents);
			}

			$result = md5($xmlContents);
		}

		return $result;
	}

	/**
	 * ���������� ��������� � ���� ��������
	 *
	 * @param $tagResultList Feed\Result\XmlNode[]
	 * @param $storageResultList array
	 * @param $context array
	 */
	protected function writeDataFile($tagResultList, $storageResultList, $context)
	{
		$writer = $this->getWriter();
		$isOnlyDelete = true;
		$actionDataList = [];

		foreach ($storageResultList as $elementId => $storageResult)
		{
			$actionType = null;
			$actionContents = null;

			switch ($storageResult['ACTION'])
			{
				case 'add':
				case 'update':
					$isOnlyDelete = false;

					$actionType = $storageResult['ACTION'];
					$actionContents = $tagResultList[$elementId]->getXmlContents();
				break;

				case 'delete':
					$actionType = 'update';
					$actionContents = '';
				break;
			}

			if (isset($actionType))
			{
				if (!isset($actionDataList[$actionType]))
				{
					$actionDataList[$actionType] = [];
				}

				$actionDataList[$actionType][$elementId] = $actionContents;
			}
		}

		foreach ($actionDataList as $action => $actionData)
		{
			switch ($action)
			{
				case 'add':
					$tagParentName = $this->getTagParentName();

					$writeResultList = $writer->writeTagList($actionData, $tagParentName);

					if (empty($writeResultList) && $this->isAllowDeleteParent()) // failed write to file, then hasn't parent tag
					{
						$parentPath = $this->getTagPath();
						$chainContents = '<' . $tagParentName . '>' . implode('', $actionData) . '</' . $tagParentName . '>';

						foreach ($parentPath as $parentName => $parentPosition)
						{
							$parentWriteResult = $writer->writeTag($chainContents, $parentName, $parentPosition);

							if ($parentWriteResult)
							{
								break;
							}

							if (
								$parentPosition === Feed\Export\Run\Writer\Base::POSITION_APPEND
								|| $parentPosition === Feed\Export\Run\Writer\Base::POSITION_PREPEND
							)
							{
								$chainContents = '<' . $parentName . '>' . $chainContents . '</' . $parentName . '>';
							}
						}
					}
				break;

				case 'update':
					$tag = $this->getTag();
					$tagName = $tag->getName();
					$tagParentName = $this->getTagParentName();
					$isTagSelfClosed = $tag->isSelfClosed();
					$runAction = $this->getRunAction();

					$writer->updateTagList($tagName, $actionData, 'id', $isTagSelfClosed);

					if ($isOnlyDelete && ($runAction === 'change' || $runAction === 'refresh'))
					{
						$isNeedDeleteParent = ($tagParentName !== null && $this->isAllowDeleteParent() && !$this->hasDataStorageSuccess($context));

						if ($isNeedDeleteParent)
						{
							$writer->updateTag($tagParentName, null, '');
						}
					}
				break;
			}
		}
	}

    /**
     * @param $tagResultList
     * @param $context
     */
	protected function writeDataCopyPublic($tagResultList, $context)
	{
		if (
			$this->getRunAction() === 'change'
			&& ($writer = $this->getPublicWriter())
		)
		{
			$updateList = [];
			$isAllowDelete = $this->isAllowPublicDelete();

			foreach ($tagResultList as $elementId => $tagResult)
			{
				if ($tagResult->isSuccess())
				{
					$updateList[$elementId] = $tagResult->getXmlContents();
				}
				else if ($isAllowDelete)
				{
					$updateList[$elementId] = '';
				}
			}

			if (!empty($updateList))
			{
				$tag = $this->getTag();
				$tagName = $tag->getName();
				$isTagSelfClosed = $tag->isSelfClosed();

				$writer->lock(true);

				// update

				$updateResult = $writer->updateTagList($tagName, $updateList, 'id', $isTagSelfClosed);

				// add

				$addList = [];

				foreach ($updateList as $elementId => $contents)
				{
					if ($contents && !isset($updateResult[$elementId]))
					{
						$addList[$elementId] = $contents;
					}
				}

				if (!empty($addList))
				{
					$parentName = $this->getTagParentName();

					$writer->writeTagList($addList, $parentName);
				}

				$writer->unlock();
			}
		}
	}

    /**
     * @return bool
     */
	protected function isAllowDeleteParent()
	{
		return false;
	}

    /**
     * @return bool
     */
	protected function isAllowPublicDelete()
	{
		return false;
	}

    /**
     * @param $context
     * @throws \Exception
     */
	protected function clearDataLog($context)
	{
		$entityType = $this->getDataLogEntityType();

		if ($entityType)
		{
			Feed\Logger\Table::deleteBatch([
				'filter' => [
					'=ENTITY_TYPE' => $entityType,
					'=ENTITY_PARENT' => $context['SETUP_ID'],
				]
			]);
		}
	}

    /**
     * @param $tagResultList
     * @param $context
     */
	protected function writeDataLog($tagResultList, $context)
	{
		$entityType = $this->getDataLogEntityType();

		if ($entityType && !empty($tagResultList))
		{
			$runAction = $this->getRunAction();

			$logger = new Feed\Logger\Logger();
			$logger->allowBatch();

			if ($runAction === 'change' || $runAction === 'refresh')
			{
				$logger->allowCheckExists();
				$logger->allowRelease();
			}

			foreach ($tagResultList as $elementId => $tagResult)
			{
				$logContext = [
					'ENTITY_TYPE' => $entityType,
					'ENTITY_PARENT' => $context['SETUP_ID'],
					'ENTITY_ID' => $elementId
				];
				$errorGroupList = [
					Feed\Psr\Log\LogLevel::CRITICAL => $tagResult->getErrors(),
					Feed\Psr\Log\LogLevel::WARNING => $tagResult->getWarnings()
				];

				foreach ($errorGroupList as $logLevel => $errorGroup)
				{
					/** @var Feed\Error\Base $error */
					foreach ($errorGroup as $error)
					{
						$errorContext = $logContext;
						$message = $error->getMessage();

						if ($messageCode = $error->getCode())
						{
							$errorContext['ERROR_CODE'] = $messageCode;
						}

						$logger->log($logLevel, $message, $errorContext);
					}
				}

				$logger->registerElement($logContext['ENTITY_TYPE'], $logContext['ENTITY_PARENT'], $logContext['ENTITY_ID']);
			}

			$logger->flush();
		}
	}

    /**
     * @return null
     */
	protected function getDataLogEntityType()
	{
		return null;
	}

    /**
     * @return array
     */
	protected function getDataLogEntityReference()
	{
		return [
			'=this.ENTITY_PARENT' => 'ref.SETUP_ID',
			'=this.ENTITY_ID' => 'ref.ELEMENT_ID',
		];
	}

    /**
     * @throws Main\ArgumentException
     */
	public function removeInvalid()
	{
		$context = $this->getContext();
		$filter = [
			'=SETUP_ID' => $context['SETUP_ID'],
			'=STATUS' => static::STORAGE_STATUS_INVALID,
			'<TIMESTAMP_X' => $this->getParameter('initTime')
		];

		$this->removeByFilter($filter, $context);
	}

    /**
     * @throws Main\ArgumentException
     */
	public function removeOld()
	{
		$context = $this->getContext();
		$filter = [
			'=SETUP_ID' => $context['SETUP_ID'],
			'!=STATUS' => static::STORAGE_STATUS_DELETE,
			'<TIMESTAMP_X' => $this->getParameter('initTime')
		];

		$this->removeByFilter($filter, $context);
	}

    /**
     * @param $filter
     * @param $context
     * @throws Main\ArgumentException
     * @throws Main\ObjectException
     * @throws Main\ObjectPropertyException
     * @throws Main\SystemException
     */
	protected function removeByFilter($filter, $context)
	{
		$dataClass = $this->getStorageDataClass();
		$isVirtual = $this->isVirtual();
		$logEntityType = $this->getDataLogEntityType();
		$hasUpdateStorage = false;
		$writeList = [];

		// remove from storage and prepare file array

		if ($dataClass)
		{
			$timestamp = new Main\Type\DateTime();
			$storagePrimaryList = $this->getStoragePrimaryList();
			$updateFields = [
				'STATUS' => static::STORAGE_STATUS_DELETE,
				'HASH' => '',
				'TIMESTAMP_X' => $timestamp
			];

			if ($isVirtual)
			{
				$updateFields['CONTENTS'] = '';
			}
			else // get ids exist in file
			{
				$query = $dataClass::getList([
					'filter' => $filter + [ '!=HASH' => false ],
					'select' => $storagePrimaryList + [ 'HASH' ]
				]);

				while ($item = $query->fetch())
				{
					$writeList[$item['ELEMENT_ID']] = '';
				}
			}

			$updateResult = $dataClass::updateBatch([ 'filter' => $filter ], $updateFields);

			if ($updateResult->isSuccess() && $updateResult->getAffectedRowsCount() > 0)
			{
				$hasUpdateStorage = true;
			}
		}

		// log

		if ($logEntityType && $hasUpdateStorage)
		{
			Feed\Logger\Table::deleteBatch([
				'filter' => [
					'=ENTITY_TYPE' => $logEntityType,
					'=ENTITY_PARENT' => $context['SETUP_ID'],
					'=RUN_STORAGE.STATUS' => static::STORAGE_STATUS_DELETE
				],
				'runtime' => [
					new Main\Entity\ReferenceField(
						'RUN_STORAGE',
						$dataClass,
						$this->getDataLogEntityReference()
					)
				]
			]);
		}

		// write to file

		if (!empty($writeList))
		{
			$tag = $this->getTag();
			$tagName = $tag->getName();
			$isTagSelfClosed = $tag->isSelfClosed();
			$writer = $this->getWriter();
			$parentName = $this->getTagParentName();
			$isNeedDeleteParent = ($parentName !== null && $this->isAllowDeleteParent() && !$this->hasDataStorageSuccess($context));

			$writer->updateTagList($tagName, $writeList, 'id', $isTagSelfClosed);

			if ($isNeedDeleteParent)
			{
				$writer->updateTag($parentName, null, '');
			}

			// remove from public

			if ($this->getRunAction() === 'change' && $this->isAllowPublicDelete())
			{
				$publicWriter = $this->getPublicWriter();

				if ($publicWriter)
				{
					$publicWriter->updateTagList($tagName, $writeList, 'id', $isTagSelfClosed);

					if ($isNeedDeleteParent)
					{
						$publicWriter->updateTag($parentName, null, '');
					}
				}
			}
		}
	}

    /**
     * @return Feed\Export\Run\Processor
     */
	protected function getProcessor()
	{
		return $this->processor;
	}

    /**
     * @return Feed\Export\Setup\Model
     */
	protected function getSetup()
	{
		return $this->getProcessor()->getSetup();
	}

    /**
     * @return Feed\Export\Run\Writer\Base
     */
	protected function getWriter()
	{
		return $this->getProcessor()->getWriter();
	}

    /**
     * @return Feed\Export\Run\Writer\Base|null
     */
	protected function getPublicWriter()
	{
		return $this->getProcessor()->getPublicWriter();
	}

    /**
     * @param $name
     * @return mixed|null
     */
	protected function getParameter($name)
	{
		return $this->getProcessor()->getParameter($name);
	}

    /**
     * @return array
     */
	protected function getContext()
	{
		return $this->getSetup()->getContext();
	}

	protected function getFormat()
	{
		return $this->getSetup()->getFormat();
	}

    /**
     * @return bool
     */
	public function isTypedTag()
	{
		return false;
	}

    /**
     * @param null $type
     * @return Feed\Export\Xml\Tag\Base|null
     */
	public function getTag($type = null) :?Feed\Export\Xml\Tag\Base
	{
		$result = null;

		if ($type !== null)
		{
			if (isset($this->typedTagList[$type]))
			{
				$result = $this->typedTagList[$type];
			}
			else
			{
				$format = $this->getFormat();
				$result = $this->getFormatTag($format, $type);

				$this->typedTagList[$type] = $result;
			}
		}
		else
		{
			if ($this->tag !== null)
			{
				$result = $this->tag;
			}
			else
			{
				$format = $this->getFormat();
				$result = $this->getFormatTag($format);

				$this->tag = $result;
			}
		}

		return $result;
	}

    /**
     * @return string|null
     */
	public function getTagParentName()
	{
		if (!isset($this->tagParentName))
		{
			$format = $this->getFormat();

			$this->tagParentName = $this->getFormatTagParentName($format);
		}

		return $this->tagParentName;
	}

    /**
     * @return array|null
     * @throws Main\SystemException
     */
	public function getTagPath()
	{
		if ($this->tagPath === null)
		{
			$format = $this->getFormat();
			$parentName = $this->getTagParentName();
			$rootTag = $format->getRoot();
			$path = $this->findTagPath($rootTag, $parentName);

			if ($path === null)
			{
				throw new Main\SystemException('not found tag path for ' . $parentName);
			}

			$this->tagPath = $path;
		}

		return $this->tagPath;
	}

    /**
     * @param Feed\Export\Xml\Tag\Base $tag
     * @param $findName
     * @return null
     */
	protected function findTagPath(Feed\Export\Xml\Tag\Base $tag, $findName)
	{
		$result = null;
		$afterTagNameList = [];

		/** @var Feed\Export\Xml\Tag\Base $child */
		foreach (array_reverse($tag->getChildren()) as $child) // because gifts require promos, categories and currencies requires offers
		{
			$childName = $child->getName();
			$childResult = null;
			$isFoundSelf = false;

			if ($childName === $findName)
			{
				$isFoundSelf = true;
			}
			else
			{
				$childResult = $this->findTagPath($child, $findName);
			}

			if ($isFoundSelf || $childResult !== null)
			{
				if ($isFoundSelf)
				{
					foreach (array_reverse($afterTagNameList) as $afterTagName)
					{
						$result[$afterTagName] = Feed\Export\Run\Writer\Base::POSITION_BEFORE;
					}
				}
				else
				{
					foreach ($childResult as $childName => $childPosition)
					{
						$result[$childName] = $childPosition;
					}
				}

				$result[$tag->getName()] = Feed\Export\Run\Writer\Base::POSITION_APPEND;

				break;
			}

			$afterTagNameList[] = $childName;
		}

		return $result;
	}

    /**
     * @param Feed\Export\Xml\Format\Reference\Base $format
     * @param null $type
     * @return null
     */
	public function getFormatTag(Feed\Export\Xml\Format\Reference\Base $format, $type = null)
	{
		return null;
	}

    /**
     * @param Feed\Export\Xml\Format\Reference\Base $format
     * @return null
     */
	public function getFormatTagParentName(Feed\Export\Xml\Format\Reference\Base $format)
	{
		return null;
	}

    /**
     * @param $tagDescriptionList
     * @param $sourceValuesList
     * @param $context
     * @return array
     */
	protected function buildTagValuesList($tagDescriptionList, $sourceValuesList, $context)
	{
		$result = [];

		foreach ($sourceValuesList as $elementId => $sourceValues)
		{
			$result[$elementId] = $this->buildTagValues($elementId, $tagDescriptionList, $sourceValues, $context);
		}

		return $result;
	}

	/**
	 * @param $elementId
	 * @param $tagDescriptionList
	 * @param $sourceValues
	 * @param $context
	 *
	 * @return Ligacom\Feed\Result\XmlValue
	 */
	protected function buildTagValues($elementId, $tagDescriptionList, $sourceValues, $context)
	{
		$result = new Feed\Result\XmlValue();

		if (isset($sourceValues['TYPE']) && $this->isTypedTag())
		{
			$result->setType($sourceValues['TYPE']);
		}

		foreach ($tagDescriptionList as $tagDescription)
		{
			$tagName = $tagDescription['TAG'];

			// get values list

			$tagValues = [];

			if (isset($tagDescription['VALUE']))
			{
				$tagValue = $this->getSourceValue($tagDescription['VALUE'], $sourceValues);

				if (is_array($tagValue))
				{
					$tagValues = $tagValue;
				}
				else
				{
					$tagValues[] = $tagValue;
				}
			}
			else
			{
				$tagValues[] = null;
			}

			// settings

			$tagSettings = isset($tagDescription['SETTINGS']) ? $tagDescription['SETTINGS'] : null;

			if ($tagSettings !== null && is_array($tagSettings))
			{
				foreach ($tagSettings as $settingName => $setting)
				{
					if (isset($setting['TYPE'], $setting['FIELD']))
					{
						if ($setting['TYPE'] === Feed\Export\Entity\Manager::TYPE_TEXT)
						{
							$tagSettings[$settingName] = $setting['FIELD'];
						}
						else
						{
							$tagSettings[$settingName] = $this->getSourceValue($setting, $sourceValues);
						}
					}
				}
			}

			// export values

			foreach ($tagValues as $tagValueIndex => $tagValue)
			{
				$isEmptyTagValue = $this->isEmptyXmlValue($tagValue); // is empty
				$tagAttributeList = [];

				if (isset($tagDescription['ATTRIBUTES']))
				{
					foreach ($tagDescription['ATTRIBUTES'] as $attributeName => $attributeSourceMap)
					{
						$attributeValue = $this->getSourceValue($attributeSourceMap, $sourceValues);

						if (is_array($attributeValue))
						{
							$attributeValue = isset($attributeValue[$tagValueIndex]) ? $attributeValue[$tagValueIndex] : null;
						}

						$tagAttributeList[$attributeName] = $attributeValue;

						if (!$this->isEmptyXmlValue($attributeValue)) // is not empty
						{
							$isEmptyTagValue = false;
						}
					}
				}

				if (!$isEmptyTagValue && !$result->hasTag($tagName, $tagValue, $tagAttributeList))
				{
					$result->addTag($tagName, $tagValue, $tagAttributeList, $tagSettings);
				}
			}
		}

		return $result;
	}

	protected function getSourceValue($sourceMap, $sourceValues)
	{
		$result = null;

		if (isset($sourceMap['VALUE']))
		{
			$result = $sourceMap['VALUE'];
		}
		else if (isset($sourceValues[$sourceMap['TYPE']][$sourceMap['FIELD']]))
		{
			$result = $sourceValues[$sourceMap['TYPE']][$sourceMap['FIELD']];
		}

		return $result;
	}

	protected function isEmptyXmlValue($value)
	{
		if ($value === null)
		{
			$result = true;
		}
		else if (is_scalar($value))
		{
			$result = (trim($value) === '');
		}
		else
		{
			$result = empty($value);
		}

		return $result;
	}

	protected function applyValueConflict($elementValue, $conflictAction)
	{
		switch ($conflictAction['TYPE'])
		{
			case 'INCREMENT':
				$result = $elementValue + $conflictAction['VALUE'];
			break;

			default:
				$result = $elementValue;
			break;
		}

		return $result;
	}
}