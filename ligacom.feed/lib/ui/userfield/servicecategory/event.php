<?php

namespace Ligacom\Feed\Ui\UserField\ServiceCategory;

use Ligacom\Feed;
use Bitrix\Main;

class Event extends Feed\Reference\Event\Regular
{
	public static function getHandlers()
	{
		return [
			[
				'module' => 'main',
				'event' => 'OnUserTypeBuildList'
			],
			[
				'module' => 'iblock',
				'event' => 'OnIBlockPropertyBuildList'
			]
		];
	}

	public static function OnUserTypeBuildList()
	{
		return Field::GetUserTypeDescription();
	}

	public static function OnIBlockPropertyBuildList()
	{
		return Property::GetUserTypeDescription();
	}
}