<?php

namespace Ligacom\Feed\Export\Xml\Attribute;

use Ligacom\Feed;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

class VolumeName extends Base
{
	public function getDefaultParameters()
	{
		return [
			'id' => 'volume_name',
			'name' => 'name',
		];
	}

	public function isDefined()
	{
		return true;
	}

	public function getDefinedSource(array $context = [])
	{
		$langKey = $this->getLangKey();

		return [
			'TYPE' => Feed\Export\Entity\Manager::TYPE_TEXT,
			'VALUE' => Feed\Config::getLang($langKey . '_DEFINED_VALUE')
		];
	}
}