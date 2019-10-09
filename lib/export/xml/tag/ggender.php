<?php

namespace Ligacom\Feed\Export\Xml\Tag;

use Bitrix\Main;
use Ligacom\Feed;
use Bitrix\Iblock;

class GGender extends Base
{
	public function getDefaultParameters()
	{
		return [
			'id' => 'g:gender',
			'name' => 'xmlns:g:gender',
            'value_type' => Feed\Type\Manager::TYPE_STRING
		];
		//todo добавить подсказку
	}
}