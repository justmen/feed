<?php

namespace Ligacom\Feed\Export\Xml\Tag;

use \Ligacom\Feed\Export\Xml;
use Ligacom\Feed;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

class Base extends Xml\Reference\Node
{
	/** @var Xml\Attribute\Base[] */
	protected $attributes;
	/** @var Xml\Tag\Base[] */
	protected $children;
	/** @var bool */
	protected $hasEmptyValue;
	/** @var bool */
	protected $isMultiple;
	/** @var int|null */
	protected $maxCount;

	protected function refreshParameters()
	{
		parent::refreshParameters();

		$parameters = $this->parameters;

		$this->children = isset($parameters['children']) ? (array)$parameters['children'] : [];
		$this->attributes = isset($parameters['attributes']) ? (array)$parameters['attributes'] : [];
		$this->hasEmptyValue = !empty($this->children) || !empty($parameters['empty_value']);
		$this->isMultiple = !empty($parameters['multiple']);
		$this->maxCount = isset($parameters['max_count']) ? (int)$parameters['max_count'] : null;
	}

	public function isMultiple()
	{
		return $this->isMultiple;
	}

	public function isSelfClosed()
	{
		return $this->hasEmptyValue && empty($this->children);
	}

	public function getChild($tagName)
	{
		$result = null;

		foreach ($this->children as $child)
		{
			if ($child->getName() === $tagName)
			{
				$result = $child;
				break;
			}
		}

		return $result;
	}

	public function hasChild($tagName)
	{
		return ($this->getChild($tagName) !== null);
	}

	public function hasChildren()
	{
		return !empty($this->children);
	}

	/**
	 * @return Base[]
	 */
	public function getChildren()
	{
		return $this->children;
	}

	public function addChildren(array $tags)
	{
		foreach ($tags as $tag)
		{
			$this->addChild($tag);
		}
	}

	public function addChild(Base $tag, $position = null)
	{
		if ($position !== null)
		{
			array_splice($this->children, $position, 0, [ $tag ]);
		}
		else
		{
			$this->children[] = $tag;
		}

		$this->hasEmptyValue = true;
	}

	public function removeChild(Base $tag)
	{
		$tagIndex = array_search($tag, $this->children);

		if ($tagIndex !== false)
		{
			array_splice($this->children, $tagIndex, 1);
			$this->hasEmptyValue = !empty($this->children) || !empty($parameters['empty_value']);
		}
	}

	public function getLangKey()
	{
		$nameLang = str_replace(['.', ' ', '-'], '_', $this->id);
		$nameLang = strtoupper($nameLang);

		return 'EXPORT_TAG_' . $nameLang;
	}

	public function getAttribute($attributeName)
	{
		$result = null;

		foreach ($this->attributes as $attribute)
		{
			if ($attribute->getName() === $attributeName)
			{
				$result = $attribute;
				break;
			}
		}

		return $result;
	}

	public function hasAttribute($attributeName)
	{
		return ($this->getAttribute($attributeName) !== null);
	}

	public function hasAttributes()
	{
		return !empty($this->attributes);
	}

	/**
	 * @return Xml\Attribute\Base[]
	 */
	public function getAttributes()
	{
		return $this->attributes;
	}

	public function addAttribute(Xml\Attribute\Base $attribute, $position = null)
	{
		if ($position !== null)
		{
			array_splice($this->attributes, $position, 0, [ $attribute ]);
		}
		else
		{
			$this->attributes[] = $attribute;
		}
	}

    /**
     *
     * @return bool
     */
	public function hasEmptyValue()
	{
		return $this->hasEmptyValue;
	}

    /**
     * @return int|null
     */
	public function getMaxCount()
	{
		return $this->maxCount;
	}

    /**
     * @param $tagDescriptionList
     * @param array $context
     */
	public function extendTagDescriptionList(&$tagDescriptionList, array $context)
	{
		$tagId = $this->id;
		$isFound = false;

		// update exists

		foreach ($tagDescriptionList as &$tagDescription)
		{
			if ($tagDescription['TAG'] === $tagId)
			{
				$isFound = true;
				$tagDescription = $this->extendTagDescription($tagDescription, $context);
			}
		}
		unset($tagDescription);

		// if not found add self if not empty

		if (!$isFound)
		{
			$tagDescription = $this->extendTagDescription([], $context);

			if (!empty($tagDescription))
			{
				$tagDescription['TAG'] =  $tagId;
				$tagDescriptionList[] = $tagDescription;
			}
		}

		// extend children

		foreach ($this->getChildren() as $child)
		{
			$child->extendTagDescriptionList($tagDescriptionList, $context);
		}
	}

    /**
     * @param $tagDescription
     * @param array $context
     * @return mixed
     */
	public function extendTagDescription($tagDescription, array $context)
	{
		$result = $tagDescription;

		if ($this->isDefined())
		{
			$result['VALUE'] = $this->getDefinedSource($context);
		}

		foreach ($this->getAttributes() as $attribute)
		{
			if ($attribute->isDefined())
			{
				$attributeId = $attribute->getId();

				if (!isset($result['ATTRIBUTES']))
				{
					$result['ATTRIBUTES'] = [];
				}

				$result['ATTRIBUTES'][$attributeId] = $attribute->getDefinedSource($context);
			}
		}

		return $result;
	}

	/**
	 *
	 * @return array|null
	 */
	public function getSettingsDescription()
	{
		return null;
	}

	public function exportDocument()
	{
		return new \SimpleXMLElement('<?xml version="1.0" encoding="' . LANG_CHARSET . '"?><root />', LIBXML_COMPACT);
	}

    /**
     * @param $tagValuesList - массив с массивами описаний тагов
     * @param array $context - настройки профиля выгрузки
     * @param \SimpleXMLElement|null $parent
     * @return Feed\Result\XmlNode
     * @throws Main\ObjectNotFoundException
     */
	public function exportTag(array $tagValuesList, array $context, \SimpleXMLElement $parent = null) :Feed\Result\XmlNode
	{
	    //если еще не создан рутовый таг, то создаем
		if ($parent === null) { $parent = $this->exportDocument(); }


		$tagValue = $this->getTagValues($tagValuesList, $this->id, false);

		return $this->exportTagValue($tagValue, $tagValuesList, $context, $parent);
	}

    /**
     * @param $tagValue
     * @param $tagValuesList
     * @param $context
     * @param \SimpleXMLElement $parent
     * @return Feed\Result\XmlNode
     * @throws Main\ObjectNotFoundException
     */
	protected function exportTagValue($tagValue, $tagValuesList, $context, \SimpleXMLElement $parent)
	{
		$result = new Feed\Result\XmlNode();
		$isValid = true;
		$value = null;
		$settings = isset($tagValue['SETTINGS']) ? $tagValue['SETTINGS'] : null;

		if (!$this->hasEmptyValue)
		{
			$result->setErrorTagName($this->id);
			$value = isset($tagValue['VALUE']) && $tagValue['VALUE'] !== '' ? $tagValue['VALUE'] : $this->getDefaultValue($context, $tagValuesList);

			$isValid = $this->validate($value, $context, $tagValuesList, $result, $settings);
		}

		if ($isValid)
		{
			$node = $this->exportNode($value, $context, $parent, $result, $settings);
			$attributes = isset($tagValue['ATTRIBUTES']) ? $tagValue['ATTRIBUTES'] : [];

			$hasAttributes = $this->exportTagAttributes($attributes, $context, $node, $result, $settings);
			$hasChildren = $this->exportTagChildren($tagValuesList, $context, $node, $result);

			if ($this->hasEmptyValue && !$hasChildren && !$hasAttributes)
			{
				if ($this->isRequired && !$result->hasErrors())
				{
					$result->setErrorTagName($this->id);
					$result->registerError(
						Feed\Config::getLang('XML_NODE_TAG_EMPTY'),
						Feed\Error\XmlNode::XML_NODE_TAG_EMPTY
					);
				}
				else
				{
					$result->invalidate();
				}
			}

			if ($result->isSuccess())
			{
				$result->setXmlElement($node);
			}
			else
			{
				$this->detachNode($parent, $node);
			}
		}

		return $result;
	}

    /**
     * @param $values
     * @param array $context
     * @param \SimpleXMLElement $parent
     * @param Feed\Result\XmlNode $tagResult
     * @param null $settings
     * @return bool
     * @throws Main\ObjectNotFoundException
     *
     */
	protected function exportTagAttributes($values, array $context, \SimpleXMLElement $parent, Feed\Result\XmlNode $tagResult, $settings = null)
	{
		$result = false;

		foreach ($this->getAttributes() as $attribute)
		{
			$id = $attribute->getId();
			$value = isset($values[$id]) && $values[$id] !== '' ? $values[$id] : $attribute->getDefaultValue($context, $values);
			$isRequired = $attribute->isRequired();

			$tagResult->setErrorStrict($isRequired);
			$tagResult->setErrorAttributeName($id);

			if ($attribute->validate($value, $context, $values, $tagResult, $settings))
			{
				$result = true;
				$attribute->exportNode($value, $context, $parent, $tagResult, $settings);
			}
		}

		$tagResult->setErrorStrict(true);
		$tagResult->setErrorAttributeName(null);

		return $result;
	}

    /**
     * @param $tagValuesList
     * @param $context
     * @param \SimpleXMLElement $parent
     * @param Feed\Result\XmlNode $tagResult
     * @return bool
     * @throws Main\ObjectNotFoundException
     */
	protected function exportTagChildren($tagValuesList, $context, \SimpleXMLElement $parent, Feed\Result\XmlNode $tagResult) :bool
	{
		$result = false;

		foreach ($this->getChildren() as $child)
		{
			$childId = $child->getId();
			$childResultList = [];
			$isError = $child->isRequired(); // error for parent if children required

			if ($child->isMultiple())
			{
				$maxCount = $child->getMaxCount();
				$childCount = 0;
				$tagValues = $child->getTagValues($tagValuesList, $childId, true);

				if (empty($tagValues)) { $tagValues[] = []; } // try export defaults

				foreach ($tagValues as $tagValue)
				{
					$childResult = $child->exportTagValue($tagValue, $tagValuesList, $context, $parent);
					$childResultList[] = $childResult;

					if ($childResult->isSuccess())
					{
						$result = true;
						$isError = false;

						++$childCount;

						if ($maxCount !== null && $childCount >= $maxCount)
						{
							break;
						}
					}
				}
			}
			else
			{
				$tagValue = $child->getTagValues($tagValuesList, $childId, false);

				$childResult = $child->exportTagValue($tagValue, $tagValuesList, $context, $parent);
				$childResultList[] = $childResult;

				if ($childResult->isSuccess())
				{
					$result = true;
					$isError = false;
				}
			}

			$this->copyResultList($childResultList, $tagResult, $isError);
		}

		return $result;
	}

    /**
     * @param $value
     * @param array $context
     * @param \SimpleXMLElement $parent
     * @param Feed\Result\XmlNode|null $nodeResult
     * @param null $settings
     * @return \SimpleXMLElement|null
     */
	public function exportNode($value, array $context, \SimpleXMLElement $parent, Feed\Result\XmlNode $nodeResult = null, $settings = null)
	{
		$tagName = $this->name;

		if ($this->hasEmptyValue)
		{

                $result = $parent->addChild($tagName);


		}
		else
		{
			$valueExport = $this->formatValue($value, $context, $nodeResult, $settings);

			$result = $parent->addChild($tagName, $valueExport);
		}

        return $result;
	}

    /**
     * @param \SimpleXMLElement $parent
     * @param \SimpleXMLElement|null $node
     */
	public function detachNode(\SimpleXMLElement $parent, \SimpleXMLElement $node = null)
	{
		if ($node !== null) { unset($node[0]); }
	}

    /**
     * @param array $fromList
     * @param Feed\Result\XmlNode $to
     * @param $isError
     */
	protected function copyResultList(array $fromList, Feed\Result\XmlNode $to, $isError)
	{
		$foundErrorMessages = [];
		$foundWarningMessages = [];

		foreach ($fromList as $from)
		{
			// copy errors

			foreach ($from->getErrors() as $error)
			{
				$errorUniqueKey = $error->getUniqueKey();

				if ($isError || $error->isCritical())
				{
					if (!isset($foundErrorMessages[$errorUniqueKey]))
					{
						$foundErrorMessages[$errorUniqueKey] = true;

						$to->addError($error);
					}
				}
				else
				{
					if (!isset($foundWarningMessages[$errorUniqueKey]))
					{
						$foundWarningMessages[$errorUniqueKey] = true;

						$to->addWarning($error);
					}
				}
			}

			// copy warnings

			foreach ($from->getWarnings() as $warning)
			{
				$warningUniqueKey = $warning->getUniqueKey();

				if (!isset($foundWarningMessages[$warningUniqueKey]))
				{
					$foundWarningMessages[$warningUniqueKey] = true;

					$to->addWarning($warning);
				}
			}

			// copy replaces

			if (!$isError && $from->isSuccess())
			{
				foreach ($from->getReplaces() as $index => $replace)
				{
					$to->addReplace($replace, $index);
				}

				if ($from->hasPlain())
				{
					$to->registerPlain();
				}
			}
		}

		if ($isError && empty($foundErrorMessages))
		{
			$to->invalidate();
		}
	}

    /***
     * возвращает значения тега, если оно есть
     * @param $tagValuesList
     * @param $tagId
     * @param bool $isMultiple
     * @return array|mixed|null
     */
	protected function getTagValues($tagValuesList, $tagId, $isMultiple = false)
	{
		$result = null;

		if (isset($tagValuesList[$tagId]))
		{
			$tagValues = $tagValuesList[$tagId];
			$isSingleValue = array_key_exists('VALUE', $tagValues);

			if ($isMultiple)
			{
				$result = $isSingleValue ? [ $tagValues ] : $tagValues;
			}
			else
			{
				$result = $isSingleValue ? $tagValues : reset($tagValues);
			}
		}
		else if ($isMultiple)
		{
			$result = [];
		}

		return $result;
	}

	public function IsCompositeTag() :bool {

    }
}
