<?php

namespace Ligacom\Feed\Ui\Update\Check;

use Ligacom\Feed;
use Bitrix\Main;

class Event
{
	public static function listenModuleUpdate($dir = true)
	{
		$params = [
			'module' => 'main',
			'event' => 'OnModuleUpdate',
			'method' => 'callUiUpdateCheck',
			'arguments' => [ 'OnModuleUpdate' ]
		];

		return $dir ? Feed\EventManager::register($params) : Feed\EventManager::unregister($params);
	}

	public static function OnModuleUpdate($readyModules)
	{
		if (is_array($readyModules) && in_array(Feed\Config::getModuleName(), $readyModules))
		{
			\CAdminNotify::DeleteByTag(Agent::NOTIFY_TAG);

			static::listenModuleUpdate(false);
		}
	}
}