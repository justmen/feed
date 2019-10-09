<?php

namespace Ligacom\Feed\Export\ParamValue;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Ligacom\Feed;

Loc::loadMessages(__FILE__);

class Model extends Feed\Reference\Storage\Model
{
	/**
	 * �������� ������ �������
	 *
	 * @return Table
	 */
	public static function getDataClass()
	{
		return Table::getClassName();
	}

	public function isAttribute()
	{
		return $this->getField('XML_TYPE') === Table::XML_TYPE_ATTRIBUTE;
	}

	public function getAttributeName()
	{
		return $this->getField('XML_ATTRIBUTE_NAME');
	}

	public function getSourceType()
	{
		$result = $this->getField('SOURCE_TYPE');

		if ($result === Table::SOURCE_TYPE_RECOMMENDATION)
		{
			$sourceField = $this->getField('SOURCE_FIELD');
			$sourceFieldParts = explode('|', $sourceField);

			$result = $sourceFieldParts[0];
		}

		return $result;
	}

	public function getSourceField()
	{
		$sourceType = $this->getField('SOURCE_TYPE');
		$result = $this->getField('SOURCE_FIELD');

		if ($sourceType === Table::SOURCE_TYPE_RECOMMENDATION)
		{
			$sourceFieldParts = explode('|', $result);

			$result = $sourceFieldParts[1];
		}

		return $result;
	}
}