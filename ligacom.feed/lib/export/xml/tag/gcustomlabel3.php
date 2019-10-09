<?php

namespace Ligacom\Feed\Export\Xml\Tag;

use Ligacom\Feed;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

class GCustomLabel3 extends Base
{
	public function getDefaultParameters()
	{
		return [
			'id' => 'g:custom_label_3',
			'name' => 'xmlns:g:custom_label_3',
			'value_type' => Feed\Type\Manager::TYPE_STRING
		];
	}
}
