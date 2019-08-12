<?php

namespace Ligacom\Feed\Export\Xml\Format\BeruRu;

use \Ligacom\Feed\Export\Xml;

class VendorModel extends Xml\Format\YandexMarket\VendorModel
{
	public function getOffer()
	{
		$tag = parent::getOffer();

		$tag->addChild(new Xml\Tag\Vat(), 4);

		$tag->addChildren([
			new Xml\Tag\Base(['name' => 'shop-sku', 'required' => true]),
			new Xml\Tag\Base(['name' => 'market-sku']),
			new Xml\Tag\Base(['name' => 'disabled', 'value_type' => 'boolean'])
		]);

		$conditionTag = $tag->getChild('condition');

		if ($conditionTag)
		{
			$tag->removeChild($conditionTag);
		}

		return $tag;
	}
}