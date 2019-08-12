<?php

namespace Ligacom\Feed\Export\Param;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Ligacom\Feed;

Loc::loadMessages(__FILE__);

class Model extends Feed\Reference\Storage\Model
{
	/** @var Feed\Export\ParamValue\Collection */
	protected $valueCollection;

	/**
	 * �������� ������ �������
	 *
	 * @return Table
	 */
	public static function getDataClass()
	{
		return Table::getClassName();
	}

	public function getSettings()
	{
		$fieldValue = $this->getField('SETTINGS');

		return is_array($fieldValue) ? $fieldValue : null;
	}

	/**
	 * @return Feed\Export\ParamValue\Collection
	 */
	public function getValueCollection()
	{
		return $this->getChildCollection('PARAM_VALUE');
	}

	protected function getChildCollectionReference($fieldKey)
	{
		$result = null;

		switch ($fieldKey)
		{
			case 'PARAM_VALUE':
				$result = Feed\Export\ParamValue\Collection::getClassName();
			break;
		}

		return $result;
	}
}