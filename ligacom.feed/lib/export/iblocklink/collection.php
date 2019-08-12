<?php

namespace Ligacom\Feed\Export\IblockLink;

use Ligacom\Feed;

class Collection extends Feed\Reference\Storage\Collection
{
	public static function getItemReference()
	{
		return Model::getClassName();
	}

	public function getByIblockId($iblockId)
    {
        $iblockId = (int)$iblockId;
        $result = null;

        if ($iblockId > 0)
        {
            /** @var Model $item */
            foreach ($this->collection as $item)
            {
                if ($item->getIblockId() === $iblockId)
                {
                    $result = $item;
                    break;
                }
            }
        }

        return $result;
    }

	public function getByOfferIblockId($iblockId)
    {
        $iblockId = (int)$iblockId;
        $result = null;

        if ($iblockId > 0)
        {
            /** @var Model $item */
            foreach ($this->collection as $item)
            {
                if ($item->getOfferIblockId() === $iblockId)
                {
                    $result = $item;
                    break;
                }
            }
        }

        return $result;
    }
}