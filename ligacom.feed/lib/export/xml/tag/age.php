<?php

namespace Ligacom\Feed\Export\Xml\Tag;

use Ligacom\Feed;

class Age extends Base
{
	/** @var string|null */
	protected $unitAttribute;

	public function getDefaultParameters()
	{
		return [
			'name' => 'age',
			'value_type' => Feed\Type\Manager::TYPE_AGE
		];
	}

	public function validate($value, array $context, $siblingsValues = null, Feed\Result\XmlNode $nodeResult = null, $settings = null)
	{
		$this->setValueUnit($siblingsValues);

		return parent::validate($value, $context, $siblingsValues, $nodeResult, $settings);
	}

	public function getValueUnit()
	{
		return $this->unitAttribute;
	}

	protected function setValueUnit($siblingsValues)
	{
		$tagValue = $this->getTagValues($siblingsValues, $this->id);

		if (isset($tagValue['ATTRIBUTES']['unit']))
		{
			$this->unitAttribute = trim($tagValue['ATTRIBUTES']['unit']);
		}
		else
		{
			$this->unitAttribute = null;
		}
	}
}