<?php


namespace Ligacom\Feed\Export\Xml\Tag;

use Ligacom\Feed;

class GMpn extends Base
{
    public function getDefaultParameters()
    {
        return [
            'id' => 'g:mpn',
            'name' => 'xmlns:g:mpn',
            'value_type' => Feed\Type\Manager::TYPE_STRING
        ];
    }
}