<?php

namespace Ligacom\Feed\Export\Xml\Tag;

use Ligacom\Feed;

class Expiry extends Base
{
	public function getDefaultParameters()
	{
		return [
			'name' => 'expiry',
			'value_type' => Feed\Type\Manager::TYPE_DATEPERIOD,
			'date_format' => 'Y-m-d\TH:i'
		];
	}
}