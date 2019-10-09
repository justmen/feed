<?php

namespace Ligacom\Feed\Export\Xml\Tag;

use Bitrix\Main;
use Ligacom\Feed;
use Bitrix\Iblock;

class GPattern extends Base
{
	public function getDefaultParameters()
	{
		return [
			'id' => 'g:pattern',
			'name' => 'xmlns:g:pattern',
            'value_type' => Feed\Type\Manager::TYPE_STRING
		];
		//todo добавить подсказку
	}
}