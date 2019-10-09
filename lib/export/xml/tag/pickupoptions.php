<?php

namespace Ligacom\Feed\Export\Xml\Tag;

use Ligacom\Feed;

class PickupOptions extends DeliveryOptions
{
	public function getDefaultParameters()
	{
		return [
			'name' => 'pickup-options',
			'value_type' => Feed\Type\Manager::TYPE_DELIVERY_OPTIONS
		];
	}

	public function getDefaultValue(array $context = [], $siblingsValues = null)
	{
		return !empty($context['DELIVERY_OPTIONS']['pickup']) ? $context['DELIVERY_OPTIONS']['pickup'] : null;
	}
}