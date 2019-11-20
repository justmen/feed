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
     * @return Feed\Reference\Storage\Table|string
     */
	public static function getDataClass()
	{
		return Table::getClassName()
            ;
	}

	public function getSettings()
	{
		$fieldValue = $this->getField('SETTINGS');

		return is_array($fieldValue) ? $fieldValue : null;
	}

    /**
     * @return Feed\Reference\Storage\Collection
     * @throws Main\SystemException
     */
	public function getValueCollection() :Feed\Reference\Storage\Collection
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