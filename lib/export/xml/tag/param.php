<?php

namespace Ligacom\Feed\Export\Xml\Tag;

use Ligacom\Feed;

class Param extends Base
{
	public function getDefaultParameters()
	{
		return [
			'name' => 'param'
		];
	}

	public function getDefaultSource(array $context = [])
	{
		return Feed\Export\Entity\Manager::TYPE_IBLOCK_ELEMENT_PROPERTY;
	}
}
