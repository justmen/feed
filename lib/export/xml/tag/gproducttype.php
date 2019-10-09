<?php

namespace Ligacom\Feed\Export\Xml\Tag;

use Ligacom\Feed;

class GProductType extends Base
{
	public function getDefaultParameters()
	{
		return [
			'id' => 'g:product_type',
			'name' => 'xmlns:g:product_type',
			'value_type' => Feed\Type\Manager::TYPE_NUMBER,
			'value_positive' => true
		];
	}

}