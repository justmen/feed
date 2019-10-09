<?php


namespace Ligacom\Feed\Export\Xml\Tag;

use Ligacom\Feed;

class GAvailability extends Base
{
    public function getDefaultParameters()
    {
        return [
            'id' => 'g:availability',
            'name' => 'xmlns:g:availability',
            'value_type' => Feed\Type\Manager::TYPE_STRING
        ];
    }
}