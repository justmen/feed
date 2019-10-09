<?php

namespace Ligacom\Feed\Export\Xml\Tag;

use Bitrix\Main;
use Ligacom\Feed;
use Bitrix\Iblock;

class GSize extends Base
{
	public function getDefaultParameters()
	{
		return [
			'id' => 'g:size',
			'name' => 'xmlns:g:size',
            'value_type' => Feed\Type\Manager::TYPE_STRING
		];
		//todo добавить подсказку
	}
}