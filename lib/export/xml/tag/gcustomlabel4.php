<?php

namespace Ligacom\Feed\Export\Xml\Tag;

use Ligacom\Feed;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

class GCustomLabel4 extends Base
{
	public function getDefaultParameters()
	{
		return [
			'id' => 'g:custom_label_4',
			'name' => 'xmlns:g:custom_label_4',
			'value_type' => Feed\Type\Manager::TYPE_STRING
		];
	}
}
