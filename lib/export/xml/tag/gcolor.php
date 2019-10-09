<?php

namespace Ligacom\Feed\Export\Xml\Tag;

use Bitrix\Main;
use Ligacom\Feed;
use Bitrix\Iblock;

class GColor extends Base
{
	public function getDefaultParameters()
	{
		return [
			'id' => 'g:color',
			'name' => 'xmlns:g:color',
            'value_type' => Feed\Type\Manager::TYPE_STRING
		];
		//todo проверка значений на символы или числа, добавить подсказку
	}
}