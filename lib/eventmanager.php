<?php

namespace Ligacom\Feed;

use Bitrix\Main;
use Ligacom\Feed;

class EventManager extends Feed\Reference\Event\Base
{
	public static function callMethod($className, $method)
	{
		$arguments = array_slice(func_get_args(), 2);
		$result = null;

		if (!empty($arguments))
		{
			$result = call_user_func_array([ $className, $method ], $arguments);
		}
		else
		{
			$result = $className::$method();
		}

		return $result;
	}

	public static function callExportSource($type, $method)
	{
		$arguments = array_slice(func_get_args(), 2);

		try
		{
			$event = Feed\Export\Entity\Manager::getEvent($type);

			call_user_func_array([$event, $method], $arguments);
		}
		catch (\Throwable $exception)
		{
			if (!Feed\Migration\Controller::check() && !static::isLocalError($exception))
			{
				throw $exception;
			}
		}
		catch (\Exception $exception)
		{
			if (!Feed\Migration\Controller::check() && !static::isLocalError($exception))
			{
				throw $exception;
			}
		}
	}

	public static function callUiUpdateCheck($method)
	{
		$arguments = array_slice(func_get_args(), 1);

		call_user_func_array(
			[ 'Feed\Ui\Update\Check\Event', $method ],
			$arguments
		);
	}

	protected static function isLocalError($exception)
	{
		return ($exception instanceof Main\SystemException);
	}
}