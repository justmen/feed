<?php

namespace Ligacom\Feed\Export\Promo\Discount;

use Ligacom\Feed;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

class Manager
{
    /** @var string[] */
    protected static $providerList;

    /**
     * ������ ���� �����
     *
     * @return string[]
     *
     * @throws Main\ArgumentOutOfRangeException
     */
    public static function getTypeList()
    {
        return array_merge(
            static::getInternalTypeList(),
            static::getProviderTypeList()
        );
    }

    /**
     * �������� �� ��������� ��� ����������
     *
     * @param $type
     *
     * @return bool
     */
    public static function isInternalType($type)
    {
        $list = static::getInternalTypeList();

        return in_array($type, $list);
    }

    /**
     * ������ ���������� �����
     *
     * @return string[]
     */
    public static function getInternalTypeList()
    {
        return [
            Feed\Export\Promo\Table::PROMO_TYPE_PROMO_CODE,
            Feed\Export\Promo\Table::PROMO_TYPE_FLASH_DISCOUNT,
			Feed\Export\Promo\Table::PROMO_TYPE_GIFT_WITH_PURCHASE,
            Feed\Export\Promo\Table::PROMO_TYPE_GIFT_N_PLUS_M,
        ];
    }

    /**
     * �������� ��� ����������� ���� �����
     *
     * @param $type
     *
     * @return string
     */
    public static function getInternalTypeTitle($type)
    {
        $typeKey = str_replace(['-', ' '], '_', strtoupper($type));

        return Feed\Config::getLang('EXPORT_PROMO_PROVIDER_INTERNAL_TYPE_' . $typeKey);
    }

    /**
     * C����� ����� �����������
     *
     * @return string[]
     *
     * @throws Main\ArgumentOutOfRangeException
     */
    public static function getProviderTypeList()
    {
        return array_keys(static::getProviderList());
    }

    /**
     * ������ �����������
     *
     * @return array $type => $className
     *
     * @throws Main\ArgumentOutOfRangeException
     */
    public static function getProviderList()
    {
        if (static::$providerList === null)
        {
            static::$providerList = array_merge(
                static::loadModuleProviderList(),
                static::loadUserProviderList()
            );
        }

        return static::$providerList;
    }

    /**
     * �������� ��� ���� ���������� (�� �����������)
     *
     * @param $type
     * @param $className
     *
     * @return string
     *
     * @throws Main\SystemException
     */
    public static function getProviderTypeTitle($type, $className = null)
    {
        if ($className === null)
        {
            $className = static::getProviderTypeClassName($type);
        }

        return $className::getTitle();
    }

    /**
     * ������ ��� ���� ����������
     *
     * @param $type
     * @param $externalId
     *
     * @return AbstractProvider
     *
     * @throws Main\SystemException
     */
    public static function getProviderInstance($type, $externalId)
    {
        $className = static::getProviderTypeClassName($type);

        return new $className($externalId);
    }

    /**
     * �������� ������ ��� ���� ����������
     *
     * @param $type
     *
     * @return AbstractProvider
     *
     * @throws Main\SystemException
     */
    public static function getProviderTypeClassName($type)
    {
        $list = static::getProviderList();
        $result = null;

        if (isset($list[$type]))
        {
            $result = $list[$type];
        }
        else
        {
            throw new Main\SystemException(
                Feed\Config::getLang('EXPORT_PROMO_PROVIDER_CLASS_NAME_NOT_FOUND', [
                    '#TYPE#' => $type
                ])
            );
        }

        return $result;
    }

    /**
     * �������� �������� ��� ���� ���� enum
     *
     * @return array
     */
    public static function getTypeEnum()
    {
        $result = [];
        $externalGroup = Feed\Config::getLang('EXPORT_PROMO_PROVIDER_EXTERNAL_GROUP');
        $internalGroup = Feed\Config::getLang('EXPORT_PROMO_PROVIDER_INTERNAL_GROUP');

	    foreach (static::getProviderList() as $type => $className)
	    {
		    $result[] = [
			    'ID' => $type,
			    'VALUE' => static::getProviderTypeTitle($type, $className),
				'GROUP' => $externalGroup
		    ];
	    }

        foreach (static::getInternalTypeList() as $internalType)
        {
            $result[] = [
                'ID' => $internalType,
                'VALUE' => static::getInternalTypeTitle($internalType),
				'GROUP' => $internalGroup
            ];
        }

        return $result;
    }

    /**
     * ��������� ����������, ���������� � ������
     *
     * @return array
     */
    protected static function loadModuleProviderList()
    {
        $result = [];
        $typeList = [
            'sale_discount' => SaleProvider::getClassName(),
            'catalog_discount' => CatalogProvider::getClassName(),
        ];

        foreach ($typeList as $type => $className)
        {
            if ($className::isEnvironmentSupport())
            {
                $result[$type] = $className;
            }
        }

        return $result;
    }

    /**
     * ��������� ����������, ������������ �������������
     *
     * @return array
     *
     * @throws Main\ArgumentOutOfRangeException
     */
    protected static function loadUserProviderList()
    {
        $result = [];

        $event = new Main\Event(Feed\Config::getModuleName(), 'onExportPromoProviderBuildList');
        $event->send();

        foreach ($event->getResults() as $eventResult)
        {
            $eventData = $eventResult->getParameters();

            if (isset($eventData['TYPE']))
            {
                if (
                    !isset($eventData['CLASS_NAME']) // is required
                    || !is_subclass_of($eventData['CLASS_NAME'], 'Feed\Export\Promo\Discount\AbstractProvider') // must be child of reference
                )
                {
                    throw new Main\ArgumentOutOfRangeException('invalid provider class');
                }

                $result[$eventData['TYPE']] = $eventData['CLASS_NAME'];
            }
        }

        return $result;
    }
}