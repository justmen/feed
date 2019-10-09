<?php


namespace Ligacom\Feed\Export\Xml\Tag;

use Ligacom\Feed;

class GAvailabilityDate extends Base
{
    public function getDefaultParameters()
    {
        return [
            'id' => 'g:availability_date',
            'name' => 'xmlns:g:availability_date',
            'value_type' => Feed\Type\Manager::TYPE_STRING
        ];
    }
}