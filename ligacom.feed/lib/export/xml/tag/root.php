<?php

namespace Ligacom\Feed\Export\Xml\Tag;

use Ligacom\Feed;

class Root extends Base
{
	public function validate($value, array $context, $siblingsValues = null, Feed\Result\XmlNode $nodeResult = null, $settings = null)
	{
		return true;
	}

	public function exportNode($value, array $context, \SimpleXMLElement $parent, Feed\Result\XmlNode $nodeResult = null, $settings = null)
	{
		if ($nodeResult !== null)
		{
			$valueExport = $nodeResult->addReplace('');
		}
		else
		{
			$valueExport = ' ';
		}

		return $parent->addChild($this->name, $valueExport);
	}
}