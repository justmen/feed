<?php

namespace Ligacom\Feed\Export\Xml\Tag;

use Ligacom\Feed;

class GExpirationDate extends Base
{
	public function getDefaultParameters()
	{
		return [
			'id' => 'g:expiration_date',
			'name' => 'xmlns:g:expiration_date',
			'value_type' => Feed\Type\Manager::TYPE_DATEPERIOD,
			'date_format' => 'Y-m-d\TH:i'
		];
	}
}