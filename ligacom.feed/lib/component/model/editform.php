<?php

namespace Ligacom\Feed\Component\Model;

use Bitrix\Main;
use Ligacom\Feed;

class EditForm extends Feed\Component\Data\EditForm
{
	public function prepareComponentParams($params)
	{
		$params['MODEL_CLASS_NAME'] = trim($params['MODEL_CLASS_NAME']);

		return $params;
	}

	public function getRequiredParams()
	{
		return [
			'MODEL_CLASS_NAME'
		];
	}

	/**
	 * @return Feed\Reference\Storage\Model
	 */
	protected function getModelClass()
	{
		return $this->getComponentParam('MODEL_CLASS_NAME');
	}

	/**
	 * @return Feed\Reference\Storage\Table
	 */
	protected function getDataClass()
	{
        $modelClass = $this->getModelClass();

        return $modelClass::getDataClass();
	}
}