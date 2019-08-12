<?php

namespace Ligacom\Feed\Export\Xml\Attribute;

use Ligacom\Feed;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

class Base extends Feed\Export\Xml\Reference\Node
{
	public function getLangKey()
	{
		$nameLang = str_replace(['.', ' ', '-'], '_', $this->id);
		$nameLang = strtoupper($nameLang);

		return 'EXPORT_ATTRIBUTE_' . $nameLang;
	}

	/**
	 * ��������� �������� xml-��������
	 *
	 * @param                                    $value
	 * @param array                              $context
	 * @param \SimpleXMLElement                  $parent
	 * @param Feed\Result\XmlNode|null         $nodeResult
	 * @param array|null                         $settings
	 *
	 * @return null
	 */
	public function exportNode($value, array $context, \SimpleXMLElement $parent, Feed\Result\XmlNode $nodeResult = null, $settings = null)
	{
		$attributeName = $this->name;
		$attributeExport = $this->formatValue($value, $context, $nodeResult, $settings);

		@$parent->addAttribute($attributeName, $attributeExport); // sanitize encoding warning (no convert, performance issue)

		return null;
	}

	/**
	 * ������� �������� xml-��������
	 *
	 * @param \SimpleXMLElement      $parent
	 * @param \SimpleXMLElement|null $node
	 */
	public function detachNode(\SimpleXMLElement $parent, \SimpleXMLElement $node = null)
	{
		$attributeName = $this->name;
		$attributes = $parent->attributes();

		if (isset($attributes[$attributeName]))
		{
			unset($attributes[$attributeName]);
		}
	}
}
