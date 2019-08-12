<?php

namespace Ligacom\Feed\Export\Xml\Attribute;

use Ligacom\Feed;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

class VolumeUnit extends Base
{
	public function getDefaultParameters()
	{
		return [
			'id' => 'volume_unit',
			'name' => 'unit',
		];
	}

	public function getSourceRecommendation(array $context = [])
	{
		$langKey = $this->getLangKey();

		return [
			[
				'TYPE' => Feed\Export\Entity\Manager::TYPE_TEXT,
				'VALUE' => Feed\Config::getLang($langKey . '_RECOMMENDATION_MILLILITER')
			],
			[
				'TYPE' => Feed\Export\Entity\Manager::TYPE_TEXT,
				'VALUE' => Feed\Config::getLang($langKey . '_RECOMMENDATION_LITER')
			]
		];
	}
}