<?php

namespace Ligacom\Feed\Ui\UserField\ConditionType;

use Ligacom\Feed;
use Bitrix\Main;

class Event extends Feed\Reference\Event\Regular
{
	public static function getHandlers()
	{
		return [
			[
				'module' => 'iblock',
				'event' => 'OnIBlockPropertyBuildList'
			]
		];
	}

	public static function OnIBlockPropertyBuildList()
	{
		return Property::GetUserTypeDescription();
	}
}