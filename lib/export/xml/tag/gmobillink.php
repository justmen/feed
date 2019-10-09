<?php

namespace Ligacom\Feed\Export\Xml\Tag;

use Ligacom\Feed;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

class GMobilLink extends GLink
{
	public function getDefaultParameters()
	{
		return [
			'id' => 'g:mobile_link',
			'name' => 'xmlns:g:mobile_link',
			'value_type' => Feed\Type\Manager::TYPE_URL
		];
	}

	public function getSourceRecommendation(array $context = [])
	{
		$result = [
			[
				'TYPE' => Feed\Export\Entity\Manager::TYPE_IBLOCK_ELEMENT_FIELD,
				'FIELD' => 'DETAIL_PAGE_URL',
			]
		];

		if (isset($context['OFFER_IBLOCK_ID']))
		{
			$result[] = [
				'TYPE' => Feed\Export\Entity\Manager::TYPE_IBLOCK_OFFER_FIELD,
				'FIELD' => 'DETAIL_PAGE_URL',
			];
		}

		return $result;
	}

	protected function formatValue($value, array $context = [], Feed\Result\XmlNode $nodeResult = null, $settings = null)
	{
		$hasValue = ($value !== null && $value !== '');

		if ($hasValue && $settings !== null)
		{
			$queryParams = [];
			$hasQueryParams = false;
			$utmFields = [
				'utm_source' => 'UTM_SOURCE',
				'utm_medium' => 'UTM_MEDIUM',
				'utm_campaign' => 'UTM_CAMPAIGN',
				'utm_content' => 'UTM_CONTENT',
				'utm_term' => 'UTM_TERM',
			];

			foreach ($utmFields as $utmRequest => $utmField)
			{
				if (isset($settings[$utmField]) && is_string($settings[$utmField]))
				{
					$utmValue = trim($settings[$utmField]);

					if ($utmValue !== '')
					{
						$hasQueryParams = true;
						$queryParams[$utmRequest] = $utmValue;
					}
				}
			}

			if ($hasQueryParams)
			{
				$value .= (strpos($value, '?') === false ? '?' : '&') . http_build_query($queryParams);
			}
		}

		return parent::formatValue($value, $context, $nodeResult, $settings);
	}

	public function getSettingsDescription()
	{
		$langKey = $this->getLangKey();

		$result = [
			'UTM_SOURCE' => [
				'TITLE' => Feed\Config::getLang($langKey . '_SETTINGS_UTM_SOURCE'),
				'TYPE' => 'param'
			],
			'UTM_MEDIUM' => [
				'TITLE' => Feed\Config::getLang($langKey . '_SETTINGS_UTM_MEDIUM'),
				'TYPE' => 'param'
			],
			'UTM_CAMPAIGN' => [
				'TITLE' => Feed\Config::getLang($langKey . '_SETTINGS_UTM_CAMPAIGN'),
				'TYPE' => 'param'
			],
			'UTM_CONTENT' => [
				'TITLE' => Feed\Config::getLang($langKey . '_SETTINGS_UTM_CONTENT'),
				'TYPE' => 'param'
			],
			'UTM_TERM' => [
				'TITLE' => Feed\Config::getLang($langKey . '_SETTINGS_UTM_TERM'),
				'TYPE' => 'param'
			]
		];

		return $result;
	}
}
