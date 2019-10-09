<?php

namespace Ligacom\Feed\Export\Xml\Tag;

use Bitrix\Main;
use Ligacom\Feed;
use Bitrix\Iblock;

class GMaterial extends Base
{
	public function getDefaultParameters()
	{
		return [
			'id' => 'g:material',
			'name' => 'xmlns:g:material',
            'value_type' => Feed\Type\Manager::TYPE_STRING
		];
		//todo добавить подсказку
	}
}