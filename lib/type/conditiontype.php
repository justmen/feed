<?php

namespace Ligacom\Feed\Type;

use Ligacom\Feed;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

class ConditionType extends StringType
{
	const TYPE_NEW = 'new';
	const TYPE_LIKE_NEW = 'likenew';
	const TYPE_USED = 'used';

	public function validate($value, array $context = [], Feed\Export\Xml\Reference\Node $node = null, Feed\Result\XmlNode $nodeResult = null)
	{
		if ($value === static::TYPE_LIKE_NEW || $value === static::TYPE_USED)
		{
			$result = true;
		}
		else if ($value === static::TYPE_NEW)
		{
			$result = false;

			if ($nodeResult)
			{
				$nodeResult->invalidate();
			}
		}
		else
		{
			$result = false;

			if ($nodeResult)
			{
				$nodeResult->registerError(Feed\Config::getLang('TYPE_CONDITION_ERROR_INVALID', [
					'#VALUE#' => $this->truncateText($value, 10),
					'#AVAILABLE#' => implode(', ', [
						static::TYPE_NEW,
						static::TYPE_LIKE_NEW,
						static::TYPE_USED,
					])
				]));
			}
		}

		return $result;
	}

	public function format($value, array $context = [], Feed\Export\Xml\Reference\Node $node = null, Feed\Result\XmlNode $nodeResult = null)
	{
		return $value;
	}
}