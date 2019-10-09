<?php

namespace Ligacom\Feed\Export\Xml\Tag;

use Ligacom\Feed;

class GGoogleProductCategory  extends Base
{
	public function getDefaultParameters()
	{
		return [
			'id' => 'g:google_product_category',
			'name' => 'xmlns:g:google_product_category',
			'value_type' => Feed\Type\Manager::TYPE_CATEGORY
		];
	}
}