<?php

namespace Ligacom\Feed\Migration;

use Bitrix\Main;
use Ligacom\Feed;

class Storage
{
	public static function canRestore($exception)
	{
		return ($exception instanceof Main\DB\SqlException);
	}

	public static function check()
	{
		$result = !Version::check('storage');

		if ($result)
		{
			Version::update('storage');

			static::reset();
		}

		return $result;
	}

	public static function reset()
	{
		Feed\Reference\Storage\Controller::createTable();
	}
}