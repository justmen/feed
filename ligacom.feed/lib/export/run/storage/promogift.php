<?php

namespace Ligacom\Feed\Export\Run\Storage;

use Bitrix\Main;
use Ligacom\Feed;

class PromoGiftTable extends Feed\Reference\Storage\Table
{
    public static function getTableName()
    {
        return 'ligacom_feed_export_run_promo_gift';
    }

    public static function createIndexes(Main\DB\Connection $connection)
    {
        $tableName = static::getTableName();

        $connection->createIndex($tableName, 'IX_' . $tableName . '_1', [ 'STATUS' ]);
        $connection->createIndex($tableName, 'IX_' . $tableName . '_2', [ 'TIMESTAMP_X' ]);
        $connection->createIndex($tableName, 'IX_' . $tableName . '_3', [ 'ELEMENT_TYPE' ]);
    }

    public static function getMap()
    {
        return [
            new Main\Entity\IntegerField('SETUP_ID', [
                'required' => true,
                'primary' => true
            ]),
            new Main\Entity\IntegerField('PROMO_ID', [
                'required' => true,
                'primary' => true
            ]),
            new Main\Entity\IntegerField('ELEMENT_ID', [
                'required' => true,
                'primary' => true
            ]),
            new Main\Entity\EnumField('ELEMENT_TYPE', [
                'required' => true,
                'default_value' => Feed\Export\PromoGift\Table::PROMO_GIFT_TYPE_OFFER,
                'values' => [
                    Feed\Export\PromoGift\Table::PROMO_GIFT_TYPE_OFFER,
                    Feed\Export\PromoGift\Table::PROMO_GIFT_TYPE_GIFT
                ]
            ]),
            new Main\Entity\StringField('HASH', [
                'size' => 33, // md5
                'validation' => [__CLASS__, 'validateHash'],
            ]),
            new Main\Entity\StringField('STATUS', [
                'size' => 1,
                'validation' => [__CLASS__, 'validateStatus'],
            ]),
            new Main\Entity\TextField('CONTENTS'),
            new Main\Entity\DatetimeField('TIMESTAMP_X', [
                'required' => true
            ])
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
}