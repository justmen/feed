<?php

namespace Ligacom\Feed\Ui\UserField\ServiceCategory;

use Ligacom\Feed;

class Provider extends Feed\Ui\UserField\Autocomplete\Provider
{
	public static function searchByName($searchQuery)
	{
		$sectionList = Feed\Service\Data\Category::getList();
		$searchQuery = ToLower($searchQuery);
		$currentTree = [];
		$currentTreeDepth = 0;
		$result = [];

		foreach ($sectionList as $sectionKey => $section)
		{
			if ($section['depth'] < $currentTreeDepth)
			{
				array_splice($currentTree, $section['depth']);
			}

			$currentTree[$section['depth']] = $sectionKey;
			$currentTreeDepth = $section['depth'];

			$sectionName = Feed\Service\Data\Category::getTitle($section['id']);
			$sectionNameLower = ToLower($sectionName);

			if (strpos($sectionNameLower, $searchQuery) !== false)
			{
				$sectionFullName = '';

				foreach ($currentTree as $treeSectionKey)
				{
					$treeSection = $sectionList[$treeSectionKey];

					$sectionFullName .= ($sectionFullName === '' ? '' : ' / ') . Feed\Service\Data\Category::getTitle($treeSection['id']);
				}

				$result[] = [
					'ID' => $section['id'],
					'NAME' => $sectionFullName,
				];
			}
		}

		return $result;
	}

	public static function getList()
	{
		$sectionList = Feed\Service\Data\Category::getList();
		$result = [];

		foreach ($sectionList as $section)
		{
			$result[] = [
				'ID' => $section['id'],
				'NAME' => Feed\Service\Data\Category::getTitle($section['id']),
				'DEPTH_LEVEL' => $section['depth']
			];
		}

		return $result;
	}

	public static function getPropertyValue($property, $value)
	{
		$result = null;
		$valueInteger = (int)$value;

		if ($valueInteger > 0)
		{
			$sectionList = Feed\Service\Data\Category::getList();
			$currentTree = [];
			$currentTreeDepth = 0;

			foreach ($sectionList as $sectionKey => $section)
			{
				if ($section['depth'] < $currentTreeDepth)
				{
					array_splice($currentTree, $section['depth']);
				}

				$currentTree[$section['depth']] = $sectionKey;
				$currentTreeDepth = $section['depth'];

				if ($section['id'] === $valueInteger)
				{
					$sectionFullName = '';

					foreach ($currentTree as $treeSectionKey)
					{
						$treeSection = $sectionList[$treeSectionKey];

						$sectionFullName .= ($sectionFullName === '' ? '' : ' / ') . Feed\Service\Data\Category::getTitle($treeSection['id']);
					}

					$result = [
						'ID' => $section['id'],
						'NAME' => $sectionFullName
					];

					break;
				}
			}
		}

		return $result;
	}
}