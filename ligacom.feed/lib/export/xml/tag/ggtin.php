<?php


namespace Ligacom\Feed\Export\Xml\Tag;

use Ligacom\Feed;

class GGtin extends Base
{
    public function getDefaultParameters()
    {
        return [
            'id' => 'g:gtin',
            'name' => 'xmlns:g:gtin',
            'value_type' => Feed\Type\Manager::TYPE_STRING
        ];
    }
}