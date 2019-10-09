<?php

namespace Ligacom\Feed\Export\Xml\Tag;

use Ligacom\Feed;

class GIsBundle extends Base
{
	public function getDefaultParameters()
	{
		return [
			'id' => 'g:is_bundle',
			'name' => 'xmlns:g:is_bundle',
			'value_type' => Feed\Type\Manager::TYPE_NUMBER
		];
	}
}