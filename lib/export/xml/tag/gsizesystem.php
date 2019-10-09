<?php

namespace Ligacom\Feed\Export\Xml\Tag;

use Bitrix\Main;
use Ligacom\Feed;
use Bitrix\Iblock;

class GSizeSystem extends Base
{
	public function getDefaultParameters()
	{
		return [
			'id' => 'g:size_system',
			'name' => 'xmlns:g:size_system',
            'value_type' => Feed\Type\Manager::TYPE_STRING
		];
		//todo добавить подсказку
	}
}