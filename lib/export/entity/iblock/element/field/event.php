<?php

namespace Ligacom\Feed\Export\Entity\Iblock\Element\Field;

use Bitrix\Main;
use Ligacom\Feed;

class Event extends Feed\Export\Entity\Reference\ElementEvent
{
	public function OnAfterIBlockElementAdd($iblockId, $offerIblockId, $fields)
	{
	    if ($fields['RESULT'])
        {
            $sourceType = $this->getType();
            $sourceParams = [
                'IBLOCK_ID' => $iblockId,
                'OFFER_IBLOCK_ID' => $offerIblockId
            ];

            if (
                !static::isElementChangeRegistered($fields['ID'], $sourceType, $sourceParams)
                && static::isTargetElement($iblockId, $offerIblockId, $fields['ID'], (int)$fields['IBLOCK_ID'])
            )
            {
                static::registerElementChange($fields['ID'], $sourceType, $sourceParams);
            }
		}
	}

	public function OnAfterIBlockElementUpdate($iblockId, $offerIblockId, $fields)
	{
	    if ($fields['RESULT'])
        {
            $sourceType = $this->getType();
            $sourceParams = [
                'IBLOCK_ID' => $iblockId,
                'OFFER_IBLOCK_ID' => $offerIblockId
            ];

            if (
                !static::isElementChangeRegistered($fields['ID'], $sourceType, $sourceParams)
                && static::isTargetElement($iblockId, $offerIblockId, $fields['ID'], (int)$fields['IBLOCK_ID'])
            )
            {
                static::registerElementChange($fields['ID'], $sourceType, $sourceParams);
            }
		}
	}

	public function OnAfterIBlockElementDelete($iblockId, $offerIblockId, $fields)
	{
        $sourceType = $this->getType();
        $sourceParams = [
            'IBLOCK_ID' => $iblockId,
            'OFFER_IBLOCK_ID' => $offerIblockId
        ];

		if (
			!static::isElementChangeRegistered($fields['ID'], $sourceType, $sourceParams)
			&& static::isTargetElement($iblockId, $offerIblockId, $fields['ID'], (int)$fields['IBLOCK_ID'])
		)
		{
			static::registerElementChange($fields['ID'], $sourceType, $sourceParams);
		}
	}

	protected function getEventsForIblock($iblockId, $offerIblockId = null)
	{
		return [
			[
				'module' => 'iblock',
				'event' => 'OnAfterIBlockElementAdd',
				'arguments' => [
					$iblockId,
					$offerIblockId
				]
			],
			[
				'module' => 'iblock',
				'event' => 'OnAfterIBlockElementUpdate',
				'arguments' => [
					$iblockId,
					$offerIblockId
				]
			],
			[
				'module' => 'iblock',
				'event' => 'OnAfterIBlockElementDelete',
				'arguments' => [
					$iblockId,
					$offerIblockId
				]
			]
		];
	}
}