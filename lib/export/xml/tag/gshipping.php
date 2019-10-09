<?php

namespace Ligacom\Feed\Export\Xml\Tag;

use Ligacom\Feed;
use Bitrix\Main;
use Bitrix\Iblock;

Main\Localization\Loc::loadMessages(__FILE__);

class GShipping extends Base
{
	public function getDefaultParameters()
	{
		return [
			'name' => 'xmlns:g:shipping'
		];
	}
}