<?php

namespace Ligacom\Feed\Export\Xml\Tag;

use Ligacom\Feed;

class GAgeGroup extends Base
{

	public function getDefaultParameters()
	{
		return [
			'id' => 'g:age_group',
			'name' => 'xmlns:g:age_group',
			'value_type' => Feed\Type\Manager::TYPE_STRING
		];
	}
}