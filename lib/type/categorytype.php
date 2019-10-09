<?php

namespace Ligacom\Feed\Type;

use Ligacom\Feed;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

class CategoryType extends NumberType
{
	public function validate($value, array $context = [], Feed\Export\Xml\Reference\Node $node = null, Feed\Result\XmlNode $nodeResult = null)
	{
		$value = (int)$value;
		$result = true;

		if ($value <= 0)
		{
			$result = false;

			if ($nodeResult)
			{
				$nodeResult->registerError(Feed\Config::getLang('TYPE_CATEGORY_ERROR_ID'));
			}
		}

		return $result;
	}

	public function format($value, array $context = [], Feed\Export\Xml\Reference\Node $node = null, Feed\Result\XmlNode $nodeResult = null)
	{
		return (int)$value;
	}
}