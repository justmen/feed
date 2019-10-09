<?php

namespace Ligacom\Feed\Type;

use Ligacom\Feed;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

class DateType extends AbstractType
{
	public function validate($value, array $context = [], Feed\Export\Xml\Reference\Node $node = null, Feed\Result\XmlNode $nodeResult = null)
	{
		$dateTime = $this->createDateFromValue($value);
		$result = true;

		if ($dateTime === null)
		{
			$result = false;

			if ($nodeResult)
			{
				$nodeResult->registerError(Feed\Config::getLang('TYPE_DATE_ERROR_INVALID'));
			}
		}

        return $result;
	}

	public function format($value, array $context = [], Feed\Export\Xml\Reference\Node $node = null, Feed\Result\XmlNode $nodeResult = null)
	{
		$dateTime = $this->createDateFromValue($value);
		$result = '';

		if ($dateTime)
		{
			$format = $this->getDateFormat($node);

			$result = $dateTime->format($format);
		}

		return $result;
	}

	protected function getDateFormat(Feed\Export\Xml\Reference\Node $node = null)
	{
		$result = 'Y-m-d H:i:s';

		if ($node)
		{
			$nodeFormat = $node->getParameter('date_format');

			if ($nodeFormat !== null)
			{
				$result = $nodeFormat;
			}
		}

		return $result;
	}

	protected function createDateFromValue($value)
	{
		$result = null;

		if ($value instanceof Main\Type\Date)
		{
			$result = $value;
		}
		else if ($value instanceof \DateTime)
		{
			$result = Main\Type\DateTime::createFromPhp($value);
		}
		else if (is_numeric($value)) // is timestamp
		{
			$result = Main\Type\DateTime::createFromTimestamp($value);
		}
		else if (is_string($value))
		{
			try
			{
				$result = new Main\Type\DateTime($value);
			}
			catch (Main\ObjectException $exception)
			{
				// invalid date
			}
		}

		return $result;
	}
}