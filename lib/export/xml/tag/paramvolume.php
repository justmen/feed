<?php

namespace Ligacom\Feed\Export\Xml\Tag;

use Ligacom\Feed;

class ParamVolume extends Param
{
	public function getDefaultParameters()
	{
		return [
			'id' => 'param_volume',
			'value_type' => Feed\Type\Manager::TYPE_NUMBER,
			'value_precision' => 3
		] + parent::getDefaultParameters();
	}
}
