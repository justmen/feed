<?php

namespace Ligacom\Feed\Export\Xml\Tag;

use Ligacom\Feed;

class ShopUrl extends Base
{
	public function getDefaultParameters()
	{
		return [
			'name' => 'url',
			'value_type' => Feed\Type\Manager::TYPE_URL
		];
	}

	public function isDefined()
	{
		return true;
	}

	public function getDefaultValue(array $context = [], $siblingsValues = null)
	{
		return $context['DOMAIN_URL'];
	}
}
