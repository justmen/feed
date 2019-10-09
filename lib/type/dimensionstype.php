<?php

namespace Ligacom\Feed\Type;

use Ligacom\Feed;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

class DimensionsType extends AbstractType
{
	protected $splitCacheValue;
	protected $splitCacheResult;

	public function validate($value, array $context = [], Feed\Export\Xml\Reference\Node $node = null, Feed\Result\XmlNode $nodeResult = null)
	{
		$splitValue = $this->splitValue($value);
		$errorMessage = null;
		$result = true;

		if ($splitValue === null)
		{
			$errorMessage = 'INVALID';
		}
		else
		{
			foreach ($splitValue as $dimension)
			{
				if ($dimension <= 0)
				{
					$errorMessage = 'NOT_POSITIVE';
				}
			}
		}

		if ($errorMessage !== null)
		{
			$result = false;

			if ($nodeResult)
			{
				$nodeResult->registerError(Feed\Config::getLang('TYPE_DIMENSIONS_ERROR_' . $errorMessage));
			}
		}

		return $result;
	}

	public function format($value, array $context = [], Feed\Export\Xml\Reference\Node $node = null, Feed\Result\XmlNode $nodeResult = null)
	{
		$splitValue = $this->splitValue($value);

		return implode('/', $splitValue);
	}

	protected function splitValue($value)
	{
		if ($value === $this->splitCacheValue)
		{
			$result = $this->splitCacheResult;
		}
		else
		{
			$result = null;
			$value = trim($value);

			if (preg_match('/^(\d+(?:\.\d+)?)[^0-9.]{1,3}(\d+(?:\.\d+)?)[^0-9.]{1,3}(\d+(?:\.\d+)?)$/', $value, $matches))
			{
				$precision = 3;
				$result = [];

				for ($i = 1; $i <= 3; $i++)
				{
					$match = $matches[$i];
					$matchValue = round($match, $precision);

					if ($matchValue === 0.0 && ceil($match) === 1.0) // round and ceil is float
					{
						$matchValue = pow(0.1, $precision);
					}

					$result[] = $matchValue;
				}
			}

			$this->splitCacheValue = $value;
			$this->splitCacheResult = $result;
		}

		return $result;
	}
}