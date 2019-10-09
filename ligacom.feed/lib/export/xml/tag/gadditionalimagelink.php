<?php

namespace Ligacom\Feed\Export\Xml\Tag;

use Ligacom\Feed;

class GAdditionalImageLink extends Base
{
    public function getDefaultParameters()
    {
        return [
            'id' => 'g:additional_image_link',
            'name' => 'xmlns:g:additional_image_link',
            'value_type' => Feed\Type\Manager::TYPE_FILE,
            'max_count' => 10
        ];
    }

    public function getSourceRecommendation(array $context = [])
    {
        $result = [
            [
                'TYPE' => Feed\Export\Entity\Manager::TYPE_IBLOCK_ELEMENT_FIELD,
                'FIELD' => 'DETAIL_PICTURE',
            ],
            [
                'TYPE' => Feed\Export\Entity\Manager::TYPE_IBLOCK_ELEMENT_FIELD,
                'FIELD' => 'PREVIEW_PICTURE',
            ]
        ];

        if (isset($context['OFFER_IBLOCK_ID']))
        {
            $result[] = [
                'TYPE' => Feed\Export\Entity\Manager::TYPE_IBLOCK_OFFER_FIELD,
                'FIELD' => 'DETAIL_PICTURE',
            ];

            $result[] = [
                'TYPE' => Feed\Export\Entity\Manager::TYPE_IBLOCK_OFFER_FIELD,
                'FIELD' => 'PREVIEW_PICTURE',
            ];
        }

        return $result;
    }
}
