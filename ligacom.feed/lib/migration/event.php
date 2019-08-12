<?php

namespace Ligacom\Feed\Migration;

use Bitrix\Main;
use Ligacom\Feed;

class Event
{
	public static function canRestore($exception)
	{
		return false;
	}

	public static function check()
	{
		$result = !Version::check('event');

		if ($result)
		{
			Version::update('event');

			static::reset();
		}

		return $result;
	}

	public static function reset()
	{
		$connection = Main\Application::getConnection();
		$trackTableName = Feed\Export\Track\Table::getTableName();

		$connection->truncateTable($trackTableName);

		Feed\Reference\Event\Controller::deleteAll();
		Feed\Reference\Event\Controller::updateRegular();

		$setupList = Feed\Export\Setup\Model::loadList([
			'filter' => [ '=AUTOUPDATE' => '1' ]
		]);

		/** @var Feed\Export\Setup\Model $setup */
		foreach ($setupList as $setup)
		{
			$setup->handleChanges(true);
		}
	}
}