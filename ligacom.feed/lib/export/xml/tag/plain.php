<?php

namespace Ligacom\Feed\Export\Xml\Tag;

use Ligacom\Feed;

class Plain extends Base
{
    public function validate($value, array $context, $siblingsValues = null, Feed\Result\XmlNode $nodeResult = null, $settings = null)
    {
        $result = true;

        if ($value === null || $value === '')
        {
            $result = false;

            if ($nodeResult)
            {
                if ($this->isRequired)
                {
                    $nodeResult->registerError(
                        Feed\Config::getLang('XML_NODE_VALIDATE_EMPTY'),
                        Feed\Error\XmlNode::XML_NODE_VALIDATE_EMPTY
                    );
                }
                else
                {
                    $nodeResult->invalidate();
                }
            }
        }

        return $result;
    }

    public function exportNode($value, array $context, \SimpleXMLElement $parent, Feed\Result\XmlNode $nodeResult = null, $settings = null)
    {
        $tagName = Feed\Result\XmlNode::PLAIN_TAG_NAME;
        $valueExport = $value;

        if ($nodeResult !== null)
        {
            $valueExport = $nodeResult->addReplace($value);

            $nodeResult->registerPlain();
        }

        return $parent->addChild($tagName, $valueExport);
    }
}