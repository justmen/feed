<?php

namespace Ligacom\Feed\Export\Entity\Text;

use Bitrix\Main;
use Ligacom\Feed;

Main\Localization\Loc::loadMessages(__FILE__);

class Source extends Feed\Export\Entity\Reference\Source
{
	public function getElementListValues($elementList, $parentList, $selectFields, $queryContext, $sourceValues)
	{
		return [];
	}

	public function getFields(array $context = [])
	{
		return null;
	}

	public function isVariable()
	{
		return true;
	}

	protected function getLangPrefix()
	{
		return 'TEXT_';
	}
}