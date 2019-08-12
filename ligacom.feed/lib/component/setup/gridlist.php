<?php

namespace Ligacom\Feed\Component\Setup;

use Bitrix\Main;
use Ligacom\Feed;

class GridList extends Feed\Component\Model\GridList
{
	protected function getReferenceFields()
	{
		$result = parent::getReferenceFields();
		$result['IBLOCK'] = [];

		return $result;
	}
}