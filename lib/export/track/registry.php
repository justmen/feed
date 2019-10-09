<?php

namespace Ligacom\Feed\Export\Track;

use Ligacom\Feed;

class Registry
{
    /**
     * ��������� ������ ���������� ������ ��� �������� � ����������� �������
     *
     * @param $entityType
     * @param $entityId
     * @param $sourceList
     */
    public static function addEntitySources($entityType, $entityId, $sourceList)
    {
        $registeredList = static::getEntitySources($entityType, $entityId);
        $newList = static::diffSourceList($sourceList, $registeredList);
        $oldList = static::diffSourceList($registeredList, $sourceList);

        foreach ($newList as $newSource)
        {
            Table::add([
                'ENTITY_TYPE' => $entityType,
                'ENTITY_ID' => $entityId,
                'SOURCE_TYPE' => $newSource['SOURCE_TYPE'],
                'SOURCE_PARAMS' => $newSource['SOURCE_PARAMS']
            ]);
        }

        foreach ($oldList as $oldSource)
        {
            Table::delete($oldSource['ID']);
        }

        static::registerSources($newList);
        static::unregisterSources($oldList);
    }

    /**
     * ������� ������ ���������� �������� � ��������� ����������� �������
     *
     * @param $entityType
     * @param $entityId
     */
    public static function removeEntitySources($entityType, $entityId)
    {
        $registeredList = static::getEntitySources($entityType, $entityId);

        foreach ($registeredList as $oldSource)
        {
            Table::delete($oldSource['ID']);
        }

        static::unregisterSources($registeredList);
    }

    /**
     * ������ ������������������ ���������� ��� ��������
     *
     * @param $entityType
     * @param $entityId
     *
     * @return array
     */
    public static function getEntitySources($entityType, $entityId)
    {
        $result = [];

        $query = Table::getList([
            'filter' => [
                '=ENTITY_TYPE' => $entityType,
                '=ENTITY_ID' => $entityId
            ]
        ]);

        while ($sourceRow = $query->fetch())
        {
            $result[] = $sourceRow;
        }

        return $result;
    }

    /**
     * ������ ������������������
     *
     * @param $typeList
     *
     * @return array
     */
    public static function getTypeSources($typeList)
    {
        $result = [];

        if (!empty($typeList))
        {
            $query = Table::getList([
                'filter' => [
                    '=SOURCE_TYPE' => $typeList
                ]
            ]);

            while ($sourceRow = $query->fetch())
            {
                $result[] = $sourceRow;
            }
        }

        return $result;
    }

    /**
     * ������������ ����������� �������
     *
     * @param $newList
     */
    protected static function registerSources($newList)
    {
        foreach ($newList as $source)
        {
            $event = Feed\Export\Entity\Manager::getEvent($source['SOURCE_TYPE']);

            $event->handleChanges(true, $source['SOURCE_PARAMS']);
        }
    }

    /**
     * ��������� ����������� ������� ��� ��������� ����������
     *
     * @param $sourceList
     */
    protected static function unregisterSources($sourceList)
    {
        $typeList = static::extractTypes($sourceList);
        $registeredList = static::getTypeSources($typeList);
        $notExistList = static::diffSourceList($sourceList, $registeredList);

        foreach ($notExistList as $source)
        {
            $event = Feed\Export\Entity\Manager::getEvent($source['SOURCE_TYPE']);

            $event->handleChanges(false, $source['SOURCE_PARAMS']);
        }
    }

    /**
     * ������ ���������� �� ������� ������, ������� �� ���� ������� �� ������
     *
     * @param $firstSourceList
     * @param $secondSourceList
     *
     * @return array
     */
    protected static function diffSourceList($firstSourceList, $secondSourceList)
    {
        $result = [];

        foreach ($firstSourceList as $firstSource)
        {
            $isFound = false;

            foreach ($secondSourceList as $secondSource)
            {
                if (
                    $firstSource['SOURCE_TYPE'] === $secondSource['SOURCE_TYPE']
                    && $firstSource['SOURCE_PARAMS'] == $secondSource['SOURCE_PARAMS']
                )
                {
                    $isFound = true;
                    break;
                }
            }

            if (!$isFound)
            {
                $result[] = $firstSource;
            }
        }

        return $result;
    }

    /**
     * ������� ������������ ����
     *
     * @param $sourceList
     *
     * @return array
     */
    protected static function extractTypes($sourceList)
    {
        $result = [];

        foreach ($sourceList as $source)
        {
            if (!isset($result[$source['SOURCE_TYPE']]))
            {
                $result[$source['SOURCE_TYPE']] = true;
            }
        }

        return array_keys($result);
    }
}