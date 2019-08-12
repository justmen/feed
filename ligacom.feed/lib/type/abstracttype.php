<?php

namespace Ligacom\Feed\Type;

use Ligacom\Feed;

abstract class AbstractType
{
	/**
	 * @param                                               $value
	 * @param array                                         $context
	 * @param Feed\Export\Xml\Reference\Node|null $node
	 * @param Feed\Result\XmlNode|null            $nodeResult
	 *
	 * @return bool
	 */
	public function validate($value, array $context = [], Feed\Export\Xml\Reference\Node $node = null, Feed\Result\XmlNode $nodeResult = null)
	{
		return true; // nothing by default
	}

	/**
	 * @param $value
	 * @param $context array
	 * @param $node Feed\Export\Xml\Reference\Node|null
	 * @param $nodeResult Feed\Result\XmlNode|null
	 *
	 * @return string
	 */
	abstract public function format($value, array $context = [], Feed\Export\Xml\Reference\Node $node = null, Feed\Result\XmlNode $nodeResult = null);
}