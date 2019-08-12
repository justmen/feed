<?php

namespace Ligacom\Feed\Export\Setup;

use Bitrix\Main;
use Ligacom\Feed;

Main\Localization\Loc::loadMessages(__FILE__);

class Table extends Feed\Reference\Storage\Table
{
	public static function getTableName()
	{
		return 'ligacom_feed_export_setup';
	}

	public static function getUfId()
	{
		return 'LIGACOM_FEED_EXPORT_SETUP';
	}

	public static function getMap()
	{
		return [
			new Main\Entity\IntegerField('ID', [
				'autocomplete' => true,
				'primary' => true
			]),
			new Main\Entity\StringField('NAME', [
				'required' => true
			]),
			new Main\Entity\StringField('DOMAIN', [
				'required' => true
			]),
			new Main\Entity\BooleanField('HTTPS', [
				'values' => ['0', '1']
			]),
			new Main\Entity\BooleanField('ENABLE_AUTO_DISCOUNTS', [
				'values' => ['0', '1'],
				'default_value' => '1'
			]),
			new Main\Entity\BooleanField('AUTOUPDATE', [
				'values' => ['0', '1'],
				'default_value' => '1'
			]),
			new Main\Entity\IntegerField('REFRESH_PERIOD'),
			new Main\Entity\StringField('EXPORT_SERVICE', [
				'required' => true,
				'size' => 20
			]),
			new Main\Entity\StringField('EXPORT_FORMAT', [
				'required' => true,
				'size' => 20
			]),
			new Main\Entity\StringField('FILE_NAME', [
				'required' => true,
				'format' => '/^[0-9A-Za-z-_.]+$/',
				'size' => 20
			]),
			new Main\Entity\StringField('SALES_NOTES', [
				'size' => 50
			]),
			new Main\Entity\ReferenceField('IBLOCK_LINK', Feed\Export\IblockLink\Table::getClassName(), [
				'=this.ID' => 'ref.SETUP_ID'
			]),
			new Main\Entity\ReferenceField('IBLOCK', 'Bitrix\Iblock\Iblock', [
				'=this.IBLOCK_LINK.IBLOCK_ID' => 'ref.ID',
			]),
			new Main\Entity\ReferenceField('DELIVERY', Feed\Export\Delivery\Table::getClassName(), [
				'=this.ID' => 'ref.ENTITY_ID',
				'=ref.ENTITY_TYPE' => ['?', Feed\Export\Delivery\Table::ENTITY_TYPE_SETUP],
			]),
			new Main\Entity\TextField('SHOP_DATA', [
				'serialized' => true
			]),
            new Main\Entity\ReferenceField('PROMO_LINK', Feed\Export\Promo\Internals\SetupLinkTable::getClassName(), [
                '=this.ID' => 'ref.SETUP_ID'
            ])
		];
	}

	public static function migrate(Main\DB\Connection $connection)
	{
		$sqlHelper = $connection->getSqlHelper();
		$tableName = static::getTableName();
		$tableFields = $connection->getTableFields($tableName);

		if (!isset($tableFields['SHOP_DATA']))
		{
			$connection->queryExecute(
				'ALTER TABLE ' . $sqlHelper->quote($tableName)
				. ' ADD COLUMN ' . $sqlHelper->quote('SHOP_DATA') . ' text NOT NULL'
			);
		}
	}

	public static function getMapDescription()
	{
		global $USER_FIELD_MANAGER;

		$result = parent::getMapDescription();

		// iblock

		if (isset($result['IBLOCK']))
		{
			$result['IBLOCK']['MANDATORY'] = 'Y';
			$result['IBLOCK']['MULTIPLE'] = 'Y';
			$result['IBLOCK']['USER_TYPE']['CLASS_NAME'] = 'Ligacom\Feed\Ui\UserField\IblockType';
		}

		// file name

		if (isset($result['FILE_NAME']))
		{
			$result['FILE_NAME']['USER_TYPE']['CLASS_NAME'] = 'Ligacom\Feed\Ui\UserField\ExportFileType';
		}

		// refresh period

		if (isset($result['REFRESH_PERIOD']))
		{
			$result['REFRESH_PERIOD']['EDIT_IN_LIST'] = (Feed\Utils::isAgentUseCron() ? 'Y' : 'N');
			$result['REFRESH_PERIOD']['USER_TYPE'] = $USER_FIELD_MANAGER->GetUserType('enumeration');
			$result['REFRESH_PERIOD']['USER_TYPE']['CLASS_NAME'] = 'Ligacom\Feed\Ui\UserField\EnumerationType';
			$result['REFRESH_PERIOD']['VALUES'] = [];
			$refreshPeriodVariants = [
				604800, // week
				259200, // three days
				86400, // one day
				43200, // half day
				21600, // six hours
				10800, // three hours
				7200, // two hours
				3600 // one hour
			];

			foreach ($refreshPeriodVariants as $refreshPeriodVariant)
			{
				$result['REFRESH_PERIOD']['VALUES'][] = [
					'ID' => $refreshPeriodVariant,
					'VALUE' => static::getFieldEnumTitle('REFRESH_PERIOD', $refreshPeriodVariant)
				];
			}
		}

		// export service

		if (isset($result['EXPORT_SERVICE']))
		{
			$result['EXPORT_SERVICE']['USER_TYPE'] = $USER_FIELD_MANAGER->GetUserType('enumeration');
			$result['EXPORT_SERVICE']['USER_TYPE']['CLASS_NAME'] = 'Ligacom\Feed\Ui\UserField\EnumerationType';
			$result['EXPORT_SERVICE']['VALUES'] = [];

			$serviceList = Feed\Export\Xml\Format\Manager::getServiceList();

			foreach ($serviceList as $service)
			{
				$result['EXPORT_SERVICE']['VALUES'][] = [
					'ID' => $service,
					'VALUE' => Feed\Export\Xml\Format\Manager::getServiceTitle($service)
				];
			}
		}

		// export format

		if (isset($result['EXPORT_FORMAT']))
		{
			$result['EXPORT_FORMAT']['USER_TYPE'] = $USER_FIELD_MANAGER->GetUserType('enumeration');
			$result['EXPORT_FORMAT']['USER_TYPE']['CLASS_NAME'] = 'Ligacom\Feed\Ui\UserField\EnumerationType';
			$result['EXPORT_FORMAT']['VALUES'] = [];

			$serviceList = Feed\Export\Xml\Format\Manager::getServiceList();
			$usedTypeList = [];

			foreach ($serviceList as $service)
			{
				$serviceTypeList = Feed\Export\Xml\Format\Manager::getTypeList($service);

				foreach ($serviceTypeList as $type)
				{
					if (!isset($usedTypeList[$type]))
					{
						$usedTypeList[$type] = true;

						$result['EXPORT_FORMAT']['VALUES'][] = [
							'ID' => $type,
							'VALUE' => $type
						];
					}
				}
			}
		}

		return $result;
	}

	/**
	 * ���� = ���� �����
	 * �������� = ������� FILTER => ������, LINK => ���� ��� �����
	 *
	 * @param int|int[]|null $primary
	 *
	 * @return array
	 */
	public static function getReference($primary = null)
	{
		return [
			'IBLOCK_LINK' => [
				'TABLE' => Feed\Export\IblockLink\Table::getClassName(),
				'LINK_FIELD' => 'SETUP_ID',
				'LINK' => [
					'SETUP_ID' => $primary,
				],
			],
			'DELIVERY' => [
				'TABLE' => Feed\Export\Delivery\Table::getClassName(),
				'LINK_FIELD' => 'ENTITY_ID',
				'LINK' => [
					'ENTITY_TYPE' => Feed\Export\Delivery\Table::ENTITY_TYPE_SETUP,
					'ENTITY_ID' => $primary,
				],
			]
		];
	}

	public static function loadExternalReference($setupIds, $select = null, $isCopy = false)
	{
		$result = parent::loadExternalReference($setupIds, $select, $isCopy);

		if (!empty($setupIds))
		{
			$referenceMap = [
				'IBLOCK' => 'loadExternalReferenceIblock',
			];

			foreach ($referenceMap as $field => $method)
			{
				if (empty($select) || in_array($field, $select))
				{
					$referenceDataList = static::$method($setupIds);

					foreach ($referenceDataList as $setupId => $referenceValue)
					{
						if (!isset($result[$setupId]))
						{
							$result[$setupId] = [];
						}

						$result[$setupId][$field] = $referenceValue;
					}
				}
			}
		}

		return $result;
	}

	protected static function loadExternalReferenceIblock($setupIds)
	{
		$result = [];

		// load row data

		$query = Feed\Export\IblockLink\Table::getList([
			'filter' => [
				'=SETUP_ID' => $setupIds,
			],
			'select' => [
				'ID',
				'IBLOCK_ID',
				'SETUP_ID',
			],
		]);

		while ($row = $query->fetch())
		{
			if (!isset($result[$row['SETUP_ID']]))
			{
				$result[$row['SETUP_ID']] = [];
			}

			$result[$row['SETUP_ID']][$row['ID']] = $row['IBLOCK_ID'];
		}

		return $result;
	}

	public static function saveExtractReference(array &$data)
	{
		$result = parent::saveExtractReference($data);

		if (array_key_exists('IBLOCK', $data))
		{
			unset($data['IBLOCK']);
		}

		return $result;
	}

	public static function deleteReference($primary)
	{
		parent::deleteReference($primary);

		// run storage

		$runDataClassList = [
			Feed\Export\Run\Storage\CategoryTable::getClassName(),
			Feed\Export\Run\Storage\CurrencyTable::getClassName(),
			Feed\Export\Run\Storage\OfferTable::getClassName(),
			Feed\Export\Run\Storage\PromoProductTable::getClassName(),
			Feed\Export\Run\Storage\PromoGiftTable::getClassName(),
			Feed\Export\Run\Storage\GiftTable::getClassName(),
			Feed\Export\Run\Storage\PromoTable::getClassName(),
		];

		foreach ($runDataClassList as $runDataClass)
		{
			$runDataClass::deleteBatch([
				'filter' => [
					'=SETUP_ID' => $primary
				]
			]);
		}

		// changes

		Feed\Export\Run\Storage\ChangesTable::deleteBatch([
			'filter' => [
				'=SETUP_ID' => $primary
			]
		]);

		// clear log

		Feed\Logger\Table::deleteBatch([
			'filter' => [
				'=ENTITY_PARENT' => $primary
			]
		]);
	}

	protected static function onBeforeRemove($primary)
	{
	    /** @var Model $model */
		$model = Model::loadById($primary);

		$model->onBeforeRemove();
	}

	protected static function onAfterSave($primary)
	{
        /** @var Model $model */
		$model = Model::loadById($primary);

		$model->onAfterSave();
	}
}
