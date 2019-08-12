<?php

namespace Ligacom\Feed\Export\ParamValue;

use Ligacom\Feed;

class Collection extends Feed\Reference\Storage\Collection
{
	public static function getItemReference()
	{
		return Model::getClassName();
	}
}