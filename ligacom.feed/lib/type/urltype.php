<?php

namespace Ligacom\Feed\Type;

use Ligacom\Feed;

class UrlType extends AbstractType
{
	public function format($value, array $context = [], Feed\Export\Xml\Reference\Node $node = null, Feed\Result\XmlNode $nodeResult = null)
	{
		$result = $value;

		if (strpos($result, '://') === false) // is not absolute url
		{
			$result =
				$context['DOMAIN_URL']
				. (strpos($result, '/') !== 0 ? '/' : '')
				. $result;
		}

		$result = str_replace('&', '&amp;', $result); // escape xml entities

		return $result;
	}
}