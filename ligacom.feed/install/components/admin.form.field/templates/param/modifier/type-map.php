<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

use Ligacom\Feed;

$arResult['TYPE_MAP'] = [];
$arResult['TYPE_MAP_JS'] = [];
$arResult['NODE_AVAILABLE_SOURCES'] = [];

$isExpertMode = Feed\Config::isExpertMode();
$variableSourceTypes = null;
$templateSourceTypes = null;
$templateAvailableTags = [
	'name' => true,
	'model' => true
];

/** @var Feed\Export\Xml\Tag\Base $tag */
foreach ($arResult['TAGS'] as $tag)
{
	$tagId = $tag->getId();
	$nodeList = [ $tag ];

	if ($tag->hasAttributes())
	{
		array_splice($nodeList, 1, 0, $tag->getAttributes());
	}

	foreach ($nodeList as $node)
	{
		$nodeFullType = ($node === $tag ? $tagId : $tagId . '.' . $node->getId());
		$nodeSources = [];
		$valueType = $node->getValueType();
		$typeMap = null;
		$isSupportTemplate = false;

		if (!isset($arResult['TYPE_MAP'][$valueType]))
		{
			$typeList = Feed\Export\Entity\Data::getDataTypes($valueType);
			$typeMap = array_flip($typeList);

			$arResult['TYPE_MAP_JS'][$valueType] = $typeList;
			$arResult['TYPE_MAP'][$valueType] = $typeMap;
		}
		else
		{
			$typeMap = $arResult['TYPE_MAP'][$valueType];
		}

		if (isset($typeMap[Feed\Export\Entity\Data::TYPE_STRING]))
		{
			$isSupportTemplate = ($isExpertMode || isset($templateAvailableTags[$nodeFullType]));

			if ($variableSourceTypes === null)
			{
				$variableSourceTypes = [];

				foreach ($arResult['SOURCE_TYPE_ENUM'] as $sourceEnum)
				{
					if ($sourceEnum['VARIABLE'])
					{
						$variableSourceTypes[$sourceEnum['ID']] = true;
					}
				}
			}

			$nodeSources = $variableSourceTypes;

			if ($isSupportTemplate)
			{
				if ($templateSourceTypes === null)
				{
					$templateSourceTypes = [];

					foreach ($arResult['SOURCE_TYPE_ENUM'] as $sourceEnum)
					{
						if ($sourceEnum['TEMPLATE'])
						{
							$templateSourceTypes[$sourceEnum['ID']] = true;
						}
					}
				}

				$nodeSources = array_merge($nodeSources, $templateSourceTypes);
			}
		}

		foreach ($arResult['SOURCE_FIELD_ENUM'] as $fieldEnum)
		{
			if (
				!isset($nodeSources[$fieldEnum['SOURCE']])
				&& isset($typeMap[$fieldEnum['TYPE']])
			)
			{
				$nodeSources[$fieldEnum['SOURCE']] = true;
			}
		}

		if (empty($nodeSources))
		{
			$nodeSources[Feed\Export\Entity\Manager::TYPE_TEXT] = true;
		}

		$arResult['NODE_AVAILABLE_SOURCES'][$nodeFullType] = $nodeSources;
	}
}