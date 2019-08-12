<?php

namespace Ligacom\Feed\Export\Xml\Attribute;

use Ligacom\Feed;

class Type extends Base
{
	public function getDefaultParameters()
	{
		return [
			'name' => 'type'
		];
	}

	public function isDefined()
	{
		return true;
	}

	public function getDefaultValue(array $context = [], $siblingsValues = null)
	{
		return $context['EXPORT_FORMAT_TYPE'];
	}
}