<?php

namespace Ligacom\Feed\Export\Xml\Tag;

use Ligacom\Feed;

class GitemGroupId extends Base
{
	public function getDefaultParameters()
	{
		return [
			'id' => 'g:item_group_id',
			'name' => 'xmlns:g:item_group_id',
			'value_type' => Feed\Type\Manager::TYPE_STRING
		];
	}

	public function getSourceRecommendation(array $context = [])
	{
		$result = [];


			$result[] = [
				'TYPE' => Feed\Export\Entity\Manager::TYPE_IBLOCK_ELEMENT_FIELD,
				'FIELD' => 'ID'
			];
        if (isset($context['OFFER_IBLOCK_ID'])) {
            $result[] = [
                'TYPE' => Feed\Export\Entity\Manager::TYPE_IBLOCK_OFFER_FIELD,
                'FIELD' => 'ID'
            ];
        }




		return $result;
	}

    public function getDefaultValue(array $context = [], $siblingsValues = null)
    {
        return  Feed\Export\Entity\Manager::TYPE_IBLOCK_ELEMENT_FIELD;
    }
}