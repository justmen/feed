<?php

namespace Ligacom\Feed\Migration;

use Bitrix\Main;
use Ligacom\Feed;

class Controller
{
	public static function canRestore($exception)
	{
		return static::callEntity('canRestore', [$exception]);
	}

	public static function check()
	{
		return static::callEntity('check');
	}

	public static function reset()
	{
		return static::callEntity('reset');
	}

	protected static function callEntity($method, $arguments = null)
	{
		$entities = static::getEntityList();
		$result = null;

		foreach ($entities as $className)
		{
			$callResult = null;

			if (!method_exists($className, $method))
			{
				// nothing
			}
			else if ($arguments !== null)
			{
				$callResult = call_user_func_array([$className, $method], $arguments);
			}
			else
			{
				$callResult = $className::$method();
			}

			if ($result === null || $callResult)
			{
				$result = $callResult;
			}
		}

		return $result;
	}

	protected static function getEntityList()
	{
		return [
			'Ligacom\Feed\Migration\Storage',
			'Ligacom\Feed\Migration\Event',
		];
	}
}