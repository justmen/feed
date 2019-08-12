<?php

namespace Ligacom\Feed\Export\Xml\Tag;

use Ligacom\Feed;

class SalesNotes extends Base
{
	public function getDefaultParameters()
	{
		return [
			'name' => 'sales_notes',
			'max_length' => 50
		];
	}

	public function getDefaultValue(array $context = [], $siblingsValues = null)
	{
		return (isset($context['SALES_NOTES']) ? $context['SALES_NOTES'] : null);
	}

	public function exportNode($value, array $context, \SimpleXMLElement $parent, Feed\Result\XmlNode $nodeResult = null, $settings = null)
	{
		if (isset($context['SALES_NOTES']))
		{
			$value = $context['SALES_NOTES'];
		}

		return parent::exportNode($value, $context, $parent, $nodeResult);
	}
}