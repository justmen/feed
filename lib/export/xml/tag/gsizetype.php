<?php

namespace Ligacom\Feed\Export\Xml\Tag;

use Bitrix\Main;
use Ligacom\Feed;
use Bitrix\Iblock;

class GSizeType extends Base
{
	public function getDefaultParameters()
	{
		return [
			'id' => 'g:size_type',
			'name' => 'xmlns:g:size_type',
            'value_type' => Feed\Type\Manager::TYPE_STRING
		];
		//todo добавить подсказку
	}
}