<?php

namespace Ligacom\Feed\Export\PromoProduct;

use Ligacom\Feed;

class Model extends Feed\Reference\Storage\Model
{
    /** @var array */
	protected $iblockContext;
	/** @var Feed\Export\Promo\Discount\AbstractProvider|null*/
	protected $discount;

	/**
	 * �������� ������ �������
	 *
	 * @return Table
	 */
	public static function getDataClass()
	{
		return Table::getClassName();
	}

	public function getTagDescriptionList()
	{
		$result = [
			[
				'TAG' => 'product',
				'VALUE' => null,
				'ATTRIBUTES' => [
					'offer-id' => [
						'TYPE' => Feed\Export\Entity\Manager::TYPE_IBLOCK_OFFER_FIELD,
						'FIELD' => 'ID'
					]
				],
				'SETTINGS' => null
			]
		];

		return $result;
	}

	public function getIblockId()
    {
        return (int)$this->getField('IBLOCK_ID');
    }

    public function setDiscount(Feed\Export\Promo\Discount\AbstractProvider $discount = null)
    {
        $this->discount = $discount;
    }

    public function getDiscount()
    {
        return $this->discount;
    }

    public function getUsedSources()
    {
        $result = $this->getSourceSelect();

        foreach ($this->getFilterCollection() as $filterModel)
        {
            $filterUserSources = $filterModel->getUsedSources();

            foreach ($filterUserSources as $sourceType)
            {
                if (!isset($result[$sourceType]))
                {
                    $result[$sourceType] = true;
                }
            }
        }

        return array_keys($result);
    }

    public function getSourceSelect()
    {
        return []; // nothing, all data from iblockLink
    }

    public function getTrackSourceList()
    {
        $sourceList = $this->getUsedSources();
        $context = $this->getContext();
        $result = [];

        foreach ($sourceList as $sourceType)
        {
            $eventHandler = Feed\Export\Entity\Manager::getEvent($sourceType);

            $result[] = [
                'SOURCE_TYPE' => $sourceType,
                'SOURCE_PARAMS' => $eventHandler->getSourceParams($context)
            ];
        }

        return $result;
    }

	public function getContext($isOnlySelf = false)
	{
		$result = $this->getIblockContext();
		$result['HAS_SETUP_IBLOCK'] = false;

		if ($this->getIblockLink())
        {
            $result['HAS_SETUP_IBLOCK'] = true;
        }

		if (!$isOnlySelf)
		{
			$result = $this->mergeParentContext($result);
		}

		return $result;
	}

	protected function mergeParentContext($selfContext)
	{
		/** @var Feed\Export\Promo\Model $parent */
		$collection = $this->getCollection();
		$parent = $collection ? $collection->getParent() : null;
		$parentContext = $parent ? $parent->getContext() : null;
		$result = $selfContext;

		if ($parentContext !== null)
		{
			$result += $parentContext;
		}

		return $result;
	}

	protected function getIblockContext()
	{
		if ($this->iblockContext === null)
		{
			$iblockId = $this->getIblockId();

			$this->iblockContext = Feed\Export\Entity\Iblock\Provider::getContext($iblockId);
		}

		return $this->iblockContext;
	}

    /**
     * @return Feed\Export\Filter\Collection
     */
    public function getFilterCollection()
    {
        return $this->getChildCollection('FILTER');
    }

    protected function loadChildCollection($fieldKey)
    {
        $result = null;

        if ($this->discount !== null)
        {
            switch ($fieldKey)
            {
                case 'FILTER':
                    $result = $this->buildFilterCollection($fieldKey);
                break;
            }
        }

        if ($result === null)
        {
            $result = parent::loadChildCollection($fieldKey);
        }

        return $result;
    }

    /**
     * ������� ��������� �� ���������� ������� �������� (���������� ��� ������ �������)
     *
     * @param $fieldKey
     *
     * @return Feed\Reference\Storage\Collection
     */
    protected function buildFilterCollection($fieldKey)
    {
        /** @var Feed\Reference\Storage\Collection $result */
        /** @var Feed\Export\Filter\Model $filterModel */
        $collectionClassName = $this->getChildCollectionReference($fieldKey);
        $modelClassName = $collectionClassName::getItemReference();

        $result = new $collectionClassName();
        $result->setParent($this);

        if ($this->discount !== null)
        {
            $context = $this->getDiscountFilterContext();
            $filterList = $this->getDiscountProductFilterList($context);

            foreach ($filterList as $filter)
            {
                $filterModel = $modelClassName::initialize([
                    'ENTITY_TYPE' => Feed\Export\Filter\Table::ENTITY_TYPE_PROMO_PRODUCT,
                    'ENTITY_ID' => $this->getId()
                ]);

                $filterModel->setCollection($result);
                $filterModel->setPlainFilter((array)$filter['FILTER']);

                if (isset($filter['DATA']))
                {
                    $filterModel->setPlainData($filter['DATA']);
                }

                $result->addItem($filterModel);
            }
        }

        return $result;
    }

    protected function getDiscountFilterContext()
    {
        $result = $this->getIblockContext();
        $result['TAGS'] = [];

        $iblockLink = $this->getIblockLink();

        if ($iblockLink !== null)
        {
            $tags = [
                'oldprice' => [ 'oldprice', 'price' ]
            ];

            foreach ($tags as $tagName => $targetTagList)
            {
                $tagValue = null;

                foreach ($targetTagList as $targetTagName)
                {
                    $targetTagDescription = $iblockLink->getTagDescription($targetTagName);

                    if (isset($targetTagDescription['VALUE']['TYPE'], $targetTagDescription['VALUE']['FIELD']))
                    {
                        $tagValue = $targetTagDescription['VALUE'];
                        break;
                    }
                }

                if ($tagValue !== null)
                {
                    $result['TAGS'][$tagName] = $tagValue;
                }
            }
        }

        return $result;
    }

    /**
     * ��������� ��������� ��� ������� �������� (�������� ������ � ������ ��������)
     *
     * @return Feed\Export\IblockLink\Model|null
     */
    protected function getIblockLink()
    {
        /** @var Feed\Export\Promo\Model $promo */
        /** @var Feed\Export\Setup\Model $setup */
        $promo = $this->getParent();
        $setup = $promo ? $promo->getParent() : null;
        $result = null;

        if ($setup)
        {
            $iblockId = $this->getIblockId();
            $iblockLinkCollection = $setup->getIblockLinkCollection();

            $result = $iblockLinkCollection->getByIblockId($iblockId);
        }

        return $result;
    }

    protected function getDiscountProductFilterList($context)
    {
        $result = [];

        if ($this->discount !== null)
        {
            $result = $this->discount->getProductFilterList($context);
        }

        return $result;
    }

    protected function getChildCollectionReference($fieldKey)
    {
        $result = null;

        switch ($fieldKey)
        {
            case 'FILTER':
                $result = Feed\Export\Filter\Collection::getClassName();
            break;
        }

        return $result;
    }
}