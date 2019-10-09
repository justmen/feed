<?php

namespace Ligacom\Feed\Export\Xml\Tag;

use Ligacom\Feed;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

class GAdsRedirect extends Base
{
	public function getDefaultParameters()
	{
		return [
			'name' => 'xmlns:g:ads_redirect',
			'value_type' => Feed\Type\Manager::TYPE_URL
		];
	}
}
