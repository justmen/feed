<?php


namespace Ligacom\Feed\Export\Xml\Tag;

use Ligacom\Feed;

class GIdentifierExists extends Base
{
    public function getDefaultParameters()
    {
        return [
            'id' => 'g:identifier_exists',
            'name' => 'xmlns:g:identifier_exists',
            'value_type' => Feed\Type\Manager::TYPE_BOOLEAN
        ];
    }

    public function isDefined()
    {
        return true;
    }
}