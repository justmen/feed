<?php

namespace Ligacom\Feed\Export\IblockLink;

use Bitrix\Main;
use Ligacom\Feed;

Main\Localization\Loc::loadMessages(__FILE__);

class Table extends Feed\Reference\Storage\Table
{
	public static function getTableName()
	{
		return 'ligacom_feed_export_iblocklink';
	}

	public static function createIndexes(Main\DB\Connection $connection)
	{
		$tableName = static::getTableName();

		$connection->createIndex($tableName, 'IX_' . $tableName . '_0', [ 'SETUP_ID' ]);
	}

	public static function getUfId()
	{
		return 'LIGACOM_FEED_EXPORT_IBLOCKLINK';
	}

	public static function getMap()
	{
		return [
			new Main\Entity\IntegerField('ID', [
				'autocomplete' => true,
				'primary' => true
			]),
			new Main\Entity\IntegerField('SETUP_ID', [
				'required' => true
			]),
			new Main\Entity\ReferenceField('SETUP', Feed\Export\Setup\Table::getClassName(), [
				'=this.SETUP_ID' => 'ref.ID'
			]),
			new Main\Entity\IntegerField('IBLOCK_ID', [
				'required' => true
			]),
			new Main\Entity\ReferenceField('IBLOCK_LINK', 'Bitrix\Iblock\Iblock', [
				'=this.IBLOCK_ID' => 'ref.ID'
			]),

			new Main\Entity\BooleanField('EXPORT_ALL', [
				'values' => [ '0', '1' ],
				'default_value' => '1',
			]),

			new Main\Entity\ReferenceField('FILTER', Feed\Export\Filter\Table::getClassName(), [
				'=ref.ENTITY_TYPE' => [ '?', Feed\Export\Filter\Table::ENTITY_TYPE_IBLOCK_LINK ],
				'=ref.ENTITY_ID' => 'this.ID'
			]),
			new Main\Entity\ReferenceField('PARAM', Feed\Export\Param\Table::getClassName(), [
				'=this.ID' => 'ref.IBLOCK_LINK_ID'
			]),
		];
	}

	public static function getReference($primary = null)
	{
		return [
			'FILTER' => [
				'TABLE' => Feed\Export\Filter\Table::getClassName(),
				'LINK_FIELD' => 'ENTITY_ID',
				'LINK' => [
					'ENTITY_TYPE' => Feed\Export\Filter\Table::ENTITY_TYPE_IBLOCK_LINK,
					'ENTITY_ID' => $primary
				],
				'ORDER' => [
					'SORT' => 'asc',
					'ID' => 'asc'
				]
			],
			'PARAM' => [
				'TABLE' => Feed\Export\Param\Table::getClassName(),
				'LINK_FIELD' => 'IBLOCK_LINK_ID',
				'LINK' => [
					'IBLOCK_LINK_ID' => $primary
				]
			],

		];
	}
}