<?php

namespace Ligacom\Feed\Export\FilterCondition;

use Bitrix\Main;
use Ligacom\Feed;

class Model extends Feed\Reference\Storage\Model
{
	public static function getDataClass()
	{
		return Table::getClassName();
	}

	public function isValid()
	{
		$dataClass = static::getDataClass();
		$fields = $this->getFields();

		return $dataClass::isValidData($fields);
	}

	/**
	 * @return string
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getQueryCompare()
	{
		$compareField = $this->getField('COMPARE');
		$compareType = Feed\Export\Entity\Data::getCompare($compareField);
		$compareValue = $this->getQueryValue();
		$optimizedType = Feed\Export\Entity\Data::optimizeCompareQuery($compareType, $compareValue);

		if ($optimizedType !== null)
		{
			$result = $optimizedType['QUERY'];
		}
		else
		{
			$result = $compareType['QUERY'];
		}

		return $result;
	}

	public function getQueryField()
	{
		$field = $this->getField('FIELD');
		$dotPosition = strpos($field, '.');

		if ($dotPosition === false)
		{
			throw new Main\SystemException('not valid field format');
		}

		return substr($field, $dotPosition + 1);
	}

	public function getSourceName()
	{
		$field = $this->getField('FIELD');
		$dotPosition = strpos($field, '.');

		if ($dotPosition === false)
		{
			throw new Main\SystemException('not valid field format');
		}

		return substr($field, 0, $dotPosition);
	}

	public function getQueryValue()
	{
		$value = $this->getField('VALUE');

		if (is_array($value) && count($value) === 1)
		{
			$value = reset($value);
		}

		if ($value === Feed\Export\Entity\Data::SPECIAL_VALUE_EMPTY)
		{
			$value = false;
		}

		return $value;
	}
}
