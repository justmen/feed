<?php

namespace Ligacom\Feed\Export\Xml\Attribute;

use Ligacom\Feed;

class Id extends Base
{
	public function getDefaultParameters()
	{
		return [
			'name' => 'id'
		];
	}

	public function isDefined()
	{
		return true;
	}

	public function getDefinedSource(array $context = [])
	{
		$result = null;

		if (isset($context['OFFER_IBLOCK_ID']))
		{
			$result = [
				'TYPE' => Feed\Export\Entity\Manager::TYPE_IBLOCK_OFFER_FIELD,
				'FIELD' => 'ID'
			];
		}
		else
		{
			$result = [
				'TYPE' => Feed\Export\Entity\Manager::TYPE_IBLOCK_ELEMENT_FIELD,
				'FIELD' => 'ID'
			];
		}

		return $result;
	}
}