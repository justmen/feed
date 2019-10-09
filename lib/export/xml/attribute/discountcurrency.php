<?php

namespace Ligacom\Feed\Export\Xml\Attribute;

use Ligacom\Feed;

class DiscountCurrency extends Base
{
    public function getDefaultParameters()
    {
        return [
            'name' => 'currency',
        ];
    }

    public function validate($value, array $context, $siblingsValues = null, Feed\Result\XmlNode $nodeResult = null, $settings = null)
    {
        $result = false;

        if (!isset($siblingsValues['unit']) || $siblingsValues['unit'] === DiscountUnit::UNIT_CURRENCY) // is need export
        {
            $result = parent::validate($value, $context, $siblingsValues, $nodeResult, $settings);
        }

        return $result;
    }
}