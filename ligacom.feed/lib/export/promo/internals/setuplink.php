<?php

namespace Ligacom\Feed\Export\Promo\Internals;

use Bitrix\Main;
use Ligacom\Feed;

class SetupLinkTable extends Feed\Reference\Storage\Table
{
	public static function getTableName()
	{
		return 'ligacom_feed_export_promo_setup_link';
	}

	public static function getUfId()
	{
		return 'LIGACOM_FEED_EXPORT_PROMO_SETUP_LINK';
	}

	public static function getMap()
	{
		return [
			new Main\Entity\IntegerField('ID', [
				'autocomplete' => true,
				'primary' => true
			]),

			new Main\Entity\IntegerField('PROMO_ID'),
			new Main\Entity\ReferenceField('PROMO', Feed\Export\Promo\Table::getClassName(), [
				'=this.PROMO_ID' => 'ref.ID'
			]),

			new Main\Entity\IntegerField('SETUP_ID'),
			new Main\Entity\ReferenceField('SETUP', Feed\Export\Setup\Table::getClassName(), [
				'=this.SETUP_ID' => 'ref.ID'
			])
		];
	}
}