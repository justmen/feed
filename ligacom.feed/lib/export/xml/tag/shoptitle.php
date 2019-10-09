<?php

namespace Ligacom\Feed\Export\Xml\Tag;

class ShopTitle extends Base
{
	public function getDefaultParameters()
	{
		return [
			'name' => 'title'
		];
	}

/*	public function isDefined()
	{
		return true;
	}

	public function getDefaultValue(array $context = [], $siblingsValues = null)
	{
		return \COption::GetOptionString('main', 'site_name');
	}*/
}
