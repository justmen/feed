<?php

namespace Ligacom\Feed\Export\Run\Storage;

use Bitrix\Main;
use Ligacom\Feed;

Main\Localization\Loc::loadMessages(__FILE__);

class PromoTable extends Feed\Reference\Storage\Table
{
    public static function getTableName()
    {
        return 'ligacom_feed_export_run_promo';
    }

    public static function createIndexes(Main\DB\Connection $connection)
    {
        $tableName = static::getTableName();

        $connection->createIndex($tableName, 'IX_' . $tableName . '_1', [ 'STATUS', 'HASH' ]);
        $connection->createIndex($tableName, 'IX_' . $tableName . '_2', [ 'TIMESTAMP_X' ]);
    }

    public static function getMap()
    {
        return [
            new Main\Entity\IntegerField('SETUP_ID', [
                'required' => true,
                'primary' => true
            ]),
            new Main\Entity\IntegerField('ELEMENT_ID', [
                'required' => true,
                'primary' => true
            ]),
            new Main\Entity\StringField('HASH', [
                'size' => 33, // md5
                'validation' => [__CLASS__, 'validateHash'],
            ]),
            new Main\Entity\StringField('STATUS', [
                'size' => 1,
                'validation' => [__CLASS__, 'validateStatus'],
            ]),
            new Main\Entity\DatetimeField('TIMESTAMP_X', [
                'required' => true
            ]),

            new Main\Entity\ReferenceField('SETUP', Feed\Export\Setup\Table::getClassName(), [
                '=this.SETUP_ID' => 'ref.ID'
            ]),

            new Main\Entity\ReferenceField('PROMO', Feed\Export\Promo\Table::getClassName(), [
                '=this.ELEMENT_ID' => 'ref.ID'
            ]),

            new Main\Entity\ReferenceField('LOG', Feed\Logger\Table::getClassName(), [
                '=ref.PROMO_ID' => 'this.ID'
            ]),

        ];
    }

    public static function validateHash()
    {
        return [
            new Main\Entity\Validator\Length(null, 33)
        ];
    }

    public static function validateStatus()
    {
        return [
            new Main\Entity\Validator\Length(null, 1)
        ];
    }

    public static function getMapDescription()
    {
        global $USER_FIELD_MANAGER;

        $result = parent::getMapDescription();

        // status

        if (isset($result['STATUS']))
        {
            $result['STATUS']['USER_TYPE'] = $USER_FIELD_MANAGER->GetUserType('enumeration');
            $result['STATUS']['USER_TYPE']['CLASS_NAME'] = 'Ligacom\Feed\Ui\UserField\LogType';
            $result['STATUS']['VALUES'] = [];
            $statusList = [
                Feed\Export\Run\Steps\Base::STORAGE_STATUS_FAIL => Feed\Psr\Log\LogLevel::CRITICAL,
                Feed\Export\Run\Steps\Base::STORAGE_STATUS_SUCCESS => null,
                Feed\Export\Run\Steps\Base::STORAGE_STATUS_INVALID => Feed\Psr\Log\LogLevel::WARNING,
                Feed\Export\Run\Steps\Base::STORAGE_STATUS_DUPLICATE => Feed\Psr\Log\LogLevel::WARNING,
                Feed\Export\Run\Steps\Base::STORAGE_STATUS_DELETE => Feed\Psr\Log\LogLevel::WARNING,
            ];

            foreach ($statusList as $status => $logLevel)
            {
                $result['STATUS']['VALUES'][] = [
                    'ID' => $status,
                    'VALUE' => Feed\Export\Run\Steps\Base::getStorageStatusTitle($status),
                    'LOG_LEVEL' => $logLevel
                ];
            }
        }

        // element id

		if (isset($result['ELEMENT_ID']) && isset($result['PROMO']))
		{
			$result['ELEMENT_ID']['USER_TYPE'] = $result['PROMO']['USER_TYPE'];
			$result['ELEMENT_ID']['SETTINGS'] = $result['PROMO']['SETTINGS'];
		}

        // log

        if (isset($result['LOG']))
        {
            $result['LOG']['USER_TYPE']['CLASS_NAME'] = 'Ligacom\Feed\Ui\UserField\LogRowType';
        }

        return $result;
    }

    protected static function saveApplyReference($primary, $fields)
    {
        // nothing (no support multiple primary)
    }

    public static function deleteReference($primary)
    {
        // nothing (controlled inside processor)
    }

    public static function getReference($primary = null)
    {
        $isMultipleLink = false;
        $linkFilter = null;
        $link = [];

        if (is_array($primary) && !isset($primary['SETUP_ID'])) // make filter
        {
            $linkFilter = [];

            foreach ($primary as $primaryItem)
            {
                if (isset($primaryItem['SETUP_ID']) && isset($primaryItem['ELEMENT_ID']))
                {
                    $isMultipleLink = true;

                    $linkFilter[] = [
                        'ENTITY_PARENT' => $primaryItem['SETUP_ID'],
                        'PROMO_ID' => $primaryItem['ELEMENT_ID']
                    ];
                }
            }

            if (count($linkFilter) > 1)
            {
                $linkFilter['LOGIC'] = 'OR';
            }
        }

        if ($isMultipleLink)
        {
            $link[] = $linkFilter;
        }
        else
        {
            $link['ENTITY_PARENT'] = isset($primary['SETUP_ID']) ? $primary['SETUP_ID'] : null;
            $link['PROMO_ID'] = isset($primary['ELEMENT_ID']) ? $primary['ELEMENT_ID'] : null;
        }

        return [
            'LOG' => [
                'TABLE' => Feed\Logger\Table::getClassName(),
                'LINK_FIELD' => [ 'ENTITY_PARENT', 'PROMO_ID' ],
                'LINK' => $link
            ]
        ];
    }
}