<?php

namespace Ligacom\Feed\Type;

use Ligacom\Feed;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

class NumberType extends AbstractType
{
	public function validate($value, array $context = [], Feed\Export\Xml\Reference\Node $node = null, Feed\Result\XmlNode $nodeResult = null)
	{
		$result = true;

		if (!is_numeric($value))
		{
			$result = false;

			if ($nodeResult)
			{
				$nodeResult->registerError(Feed\Config::getLang('TYPE_NUMBER_ERROR_NOT_NUMERIC'));
			}
		}
		else if ((float)$value < 0)
		{
			$result = false;

			if ($nodeResult)
			{
				$nodeResult->registerError(Feed\Config::getLang('TYPE_NUMBER_ERROR_NEGATIVE'));
			}
		}
		else if ($node && $node->getParameter('value_positive') === true)
		{
			$precision = $this->getPrecision($node);
			$minimalValue = ($precision > 0 ? pow(0.1, $precision) * 0.5 : 0.5);

			if ((float)$value < $minimalValue)
			{
				$nodeResult->registerError(Feed\Config::getLang('TYPE_NUMBER_ERROR_NON_POSITIVE'));
			}
		}

		return $result;
	}

	public function format($value, array $context = [], Feed\Export\Xml\Reference\Node $node = null, Feed\Result\XmlNode $nodeResult = null)
	{
		$precision = $this->getPrecision($node);

		return round($value, $precision);
	}

	protected function getPrecision(Feed\Export\Xml\Reference\Node $node = null)
	{
		$precision = 2;

		if ($node)
		{
			$nodePrecision = $node->getParameter('value_precision');

			if ($nodePrecision !== null)
			{
				$precision = (int)$nodePrecision;
			}
		}

		return $precision;
	}
}