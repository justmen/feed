<?php

namespace Ligacom\Feed\Export\Xml\Tag;

use Bitrix\Main;
use Ligacom\Feed;
use Bitrix\Iblock;

class GMultipack extends Base
{
	public function getDefaultParameters()
	{
		return [
			'name' => 'xmlns:g:multipack'
		];
	}
}