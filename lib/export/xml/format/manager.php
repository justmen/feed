<?php

namespace Ligacom\Feed\Export\Xml\Format;

use Bitrix\Main;
use Ligacom\Feed;

Main\Localization\Loc::loadMessages(__FILE__);

class Manager
{
	const EXPORT_SERVICE_GOOGLE_MERCHANT = 'Google.Merchant';

	protected static $customServiceList;

	public static function getServiceList()
	{
		$customServiceList = static::getCustomServiceList();

		return array_merge(
			[
				static::EXPORT_SERVICE_GOOGLE_MERCHANT
			],
			array_keys($customServiceList)
		);
	}

	public static function getServiceTitle($service)
	{
		$serviceLangKey = str_replace(['.', ' ', '-'], '_', $service);
		$serviceLangKey = strtoupper($serviceLangKey);

		return Feed\Config::getLang('EXPORT_XML_FORMAT_SERVICE_' . $serviceLangKey);
	}

	public static function getTypeTitle($type)
	{
		$typeLangKey = str_replace(['.', ' ', '-'], '_', $type);
		$typeLangKey = strtoupper($typeLangKey);

		return Feed\Config::getLang('EXPORT_XML_FORMAT_TYPE_' . $typeLangKey, null, $type);
	}

	public static function getTypeList($service)
	{
		$result = null;

		switch ($service)
		{
			case static::EXPORT_SERVICE_GOOGLE_MERCHANT:
				$result = [
					static::EXPORT_SERVICE_GOOGLE_MERCHANT
				];
			break;

			default:
				$customServiceList = static::getCustomServiceList();

				if (isset($customServiceList[$service]))
				{
					$result = array_keys($customServiceList[$service]);
				}
			break;
		}

		return $result;
	}

    /**
     * @param $service
     * @param $type
     * @return null
     * @throws Main\ObjectNotFoundException
     */
	public static function getEntity($service, $type)
	{
		$result = null;
		$customServiceList = static::getCustomServiceList();

		if (isset($customServiceList[$service][$type]))
		{
			$className = $customServiceList[$service][$type];
		}
		else
		{
			$className = __NAMESPACE__ . '\\' . str_replace('.', '', $service) . '\\' . str_replace(['.', '-'], '', $type);
		}

		if (class_exists($className))
		{
			$result = new $className;
		}
		else
		{
			throw new Main\ObjectNotFoundException('format ' . $type .' not found for service ' . $service);
		}

		return $result;
	}

	protected static function getCustomServiceList()
	{
		if (static::$customServiceList === null)
		{
			static::$customServiceList = static::loadCustomServiceList();
		}

		return static::$customServiceList;
	}

	protected static function loadCustomServiceList()
	{
		$result = [];

		$event = new Main\Event(Feed\Config::getModuleName(), 'onExportXmlFormatBuildList');
		$event->send();

		foreach ($event->getResults() as $eventResult)
		{
			$eventData = $eventResult->getParameters();

			if (isset($eventData['SERVICE']))
			{
				if (!isset($eventData['TYPE_LIST']))
				{
					throw new Main\ArgumentOutOfRangeException('TYPE_LIST must be defined for service ' . $eventData['SERVICE']);
				}
				else if (!is_array($eventData['TYPE_LIST']))
				{
					throw new Main\ArgumentOutOfRangeException('TYPE_LIST must be array for service ' . $eventData['SERVICE']);
				}
				else if (count($eventData['TYPE_LIST']) === 0)
				{
					throw new Main\ArgumentOutOfRangeException('TYPE_LIST must be not empty for service ' . $eventData['SERVICE']);
				}
				else
				{
					$formatReferenceClassName = 'Feed\Export\Xml\Format\Reference\Base';

					foreach ($eventData['TYPE_LIST'] as $formatName => $formatClassName)
					{
						if (!is_subclass_of($formatClassName, $formatReferenceClassName))
						{
							throw new Main\ArgumentOutOfRangeException($formatClassName . ' must inherit ' . $formatReferenceClassName . ' for service ' . $eventData['SERVICE']);
						}
					}
				}

				$result[$eventData['SERVICE']] = $eventData['TYPE_LIST'];
			}
		}

		return $result;
	}
}