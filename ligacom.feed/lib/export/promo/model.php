<?php

namespace Ligacom\Feed\Export\Promo;

use Ligacom\Feed;
use Bitrix\Main;

class Model extends Feed\Reference\Storage\Model
{
    /** @var Discount\AbstractProvider|null */
    protected $discount;
    /** @var array|null */
    protected $discountPromoFields;
    /** @var bool|null */
    protected $isDiscountValid;

    protected function __construct(array $fields = [])
    {
        parent::__construct($fields);

        $this->createDiscount();
    }

    protected function createDiscount()
    {
        $type = $this->getField('PROMO_TYPE');

        if (!Discount\Manager::isInternalType($type))
        {
            try
            {
                $externalId = $this->getField('EXTERNAL_ID');

                $this->discount = Discount\Manager::getProviderInstance($type, $externalId);
                $this->isDiscountValid = true;
            }
            catch (Main\SystemException $exception)
            {
                $this->isDiscountValid = false;
            }
        }
    }

    /**
	 * �������� ������ �������
	 *
	 * @return Table
	 */
	public static function getDataClass()
	{
		return Table::getClassName();
	}

	public function onBeforeRemove()
    {
        $this->handleChanges(false);
        $this->handleActiveDate(false);
    }

    public function onAfterSave()
    {
        $this->updateListener();
    }

	public function getTagDescriptionList()
    {
        return [
            [
                'TAG' => 'promo',
                'VALUE' => null,
                'ATTRIBUTES' => [
                    'id' => [ 'TYPE' => 'PROMO', 'FIELD' => 'ID' ],
                    'type' => [ 'TYPE' => 'PROMO', 'FIELD' => 'PROMO_TYPE' ],
                ]
            ],
            [
                'TAG' => 'start-date',
                'VALUE' => [ 'TYPE' => 'PROMO', 'FIELD' => 'START_DATE' ],
                'ATTRIBUTES' => []
            ],
            [
                'TAG' => 'end-date',
                'VALUE' => [ 'TYPE' => 'PROMO', 'FIELD' => 'FINISH_DATE' ],
                'ATTRIBUTES' => []
            ],
            [
                'TAG' => 'description',
                'VALUE' => [ 'TYPE' => 'PROMO', 'FIELD' => 'DESCRIPTION' ],
                'ATTRIBUTES' => []
            ],
            [
                'TAG' => 'url',
                'VALUE' => [ 'TYPE' => 'PROMO', 'FIELD' => 'URL' ],
                'ATTRIBUTES' => []
            ],
            [
                'TAG' => 'promo-code',
                'VALUE' => [ 'TYPE' => 'PROMO', 'FIELD' => 'PROMO_CODE' ],
                'ATTRIBUTES' => []
            ],
            [
                'TAG' => 'discount',
                'VALUE' => [ 'TYPE' => 'PROMO', 'FIELD' => 'DISCOUNT_VALUE' ],
                'ATTRIBUTES' => [
                    'unit' => [ 'TYPE' => 'PROMO', 'FIELD' => 'DISCOUNT_UNIT'  ],
                    'currency' => [ 'TYPE' => 'PROMO', 'FIELD' => 'DISCOUNT_CURRENCY'  ],
                ]
            ],
            [
                'TAG' => 'required-quantity',
                'VALUE' => [ 'TYPE' => 'PROMO', 'FIELD' => 'GIFT_REQUIRED_QUANTITY' ],
                'ATTRIBUTES' => []
            ],
            [
                'TAG' => 'free-quantity',
                'VALUE' => [ 'TYPE' => 'PROMO', 'FIELD' => 'GIFT_FREE_QUANTITY' ],
                'ATTRIBUTES' => []
            ],
            [
                'TAG' => 'product',
                'VALUE' => [ 'TYPE' => 'PRODUCT', 'FIELD' => 'CONTENTS' ],
                'ATTRIBUTES' => []
            ],
            [
                'TAG' => 'promo-gift',
                'VALUE' => [ 'TYPE' => 'GIFT', 'FIELD' => 'CONTENTS' ],
                'ATTRIBUTES' => []
            ],
        ];
    }

    public function isActive()
    {
        $result = true;

        if ((string)$this->getField('ACTIVE') !== Table::BOOLEAN_Y)
        {
            $result = false;
        }
        else if ($this->discount !== null)
        {
            $result = $this->discount->isActive();
        }

        return $result;
    }

    public function isActiveDate()
    {
        /** @var Main\Type\DateTime $startDate */
        /** @var Main\Type\DateTime $finishDate */
        $startDate = $this->getPromoField('START_DATE');
        $finishDate = $this->getPromoField('FINISH_DATE');
        $now = time();
        $result = true;

        if ($startDate && $startDate->getTimestamp() > $now)
        {
            $result = false;
        }
        else if ($finishDate && $finishDate->getTimestamp() <= $now)
        {
            $result = false;
        }

        return $result;
    }

    /**
     * ��������� ���� ��������� ���������� ����� �������
     *
     * @param $now int|null
     *
     * @return Main\Type\Date|null
     */
    public function getNextActiveDate($now = null)
    {
        $result = null;
        $resultTimestamp = null;
        $dateList = [
            $this->getPromoField('START_DATE'),
            $this->getPromoField('FINISH_DATE')
        ];

        foreach ($dateList as $date)
        {
            if (!empty($date) && $date instanceof Main\Type\Date)
            {
                $dateTimestamp = $date->getTimestamp();

                if ($now === null) { $now = time(); }

                if ($now < $dateTimestamp && ($resultTimestamp === null || $dateTimestamp < $resultTimestamp))
                {
                    $resultTimestamp = $dateTimestamp;
                    $result = $date;
                }
            }
        }

        return $result;
    }

    public function isExportForAll()
    {
        return ((string)$this->getField('SETUP_EXPORT_ALL') === Table::BOOLEAN_Y);
    }

    public function getPromoType()
    {
        if ($this->discount !== null)
        {
            $result = $this->discount->getPromoType();
        }
        else
        {
            $result = $this->getField('PROMO_TYPE');
        }

        return $result;
    }

    public function getPromoField($key)
    {
        $result = null;

        if ($this->discount !== null)
        {
            $promoFields = $this->getPromoFields();

            if (isset($promoFields[$key]))
            {
                $result = $promoFields[$key];
            }
        }
        else
        {
            $result = $this->getField($key);
        }

        return $result;
    }

    public function getPromoFields()
    {
        if ($this->discount === null)
        {
            $result = $this->getFields();
        }
        else if ($this->discountPromoFields !== null)
        {
            $result = $this->discountPromoFields;
        }
        else
        {
            $result = $this->discount->getPromoFields() + $this->getFields();
            $result['PROMO_TYPE'] = $this->getPromoType();

            $this->discountPromoFields = $result;
        }

        return $result;
    }

    public function hasProductDiscountPrice()
    {
        return $this->getPromoType() === Table::PROMO_TYPE_FLASH_DISCOUNT;
    }

    public function applyDiscountRules($productId, $priceValue, $currency = null, $filterData = null)
    {
        if ($this->discount !== null)
        {
            $result = $this->discount->applyDiscountRules($productId, $priceValue, $currency, $filterData);
        }
        else
        {
            $result = $priceValue;
            $currency = (string)$currency;
            $discountValue = (float)$this->getField('DISCOUNT_VALUE');
            $discountUnit = $this->getField('DISCOUNT_UNIT');

            switch ($discountUnit)
            {
                case Table::DISCOUNT_UNIT_CURRENCY:
                    $discountCurrency = (string)$this->getField('DISCOUNT_CURRENCY');

                    if ($currency === '' || $discountCurrency === '' || $currency === $discountCurrency)
                    {
                        $result -= $discountValue;
                    }
                    else if (Main\Loader::includeModule('currency'))
                    {
                        $result -= \CCurrencyRates::ConvertCurrency($discountValue, $discountCurrency, $currency);
                    }
                break;

                case Table::DISCOUNT_UNIT_PERCENT:
                    $result *= (1 - $discountValue / 100);
                break;
            }
        }

        return $result;
    }

    public function updateListener()
    {
        $this->handleChanges();
        $this->handleActiveDate();
    }

    public function isListenChanges()
    {
        return ($this->isActive() && $this->isActiveDate() && $this->hasAutoUpdateSetup());
    }

    public function handleChanges($direction = null)
    {
        $entityType = Feed\Export\Track\Table::ENTITY_TYPE_PROMO;
        $entityId = $this->getId();

        if ($direction === null) { $direction = $this->isListenChanges(); }

        if ($direction)
        {
            $trackSourceList = $this->getTrackSourceList();

            Feed\Export\Track\Registry::addEntitySources($entityType, $entityId, $trackSourceList);
        }
        else
        {
            Feed\Export\Track\Registry::removeEntitySources($entityType, $entityId);
        }
    }

    public function isListenActiveDate()
    {
        return ($this->isActive() && $this->getNextActiveDate() !== null && $this->hasAutoUpdateSetup());
    }

    public function handleActiveDate($direction = null)
    {
        $nextDate = $this->getNextActiveDate();
        $agentParams = [
            'method' => 'entityChange',
            'arguments' => [
                Feed\Export\Track\Table::ENTITY_TYPE_PROMO,
                $this->getId()
            ]
        ];

        if ($direction === null) { $direction = $this->isListenActiveDate(); }

        if ($direction && $nextDate)
        {
            $agentParams['next_exec'] = $nextDate->toString();

            Feed\Export\Track\Agent::register($agentParams);
        }
        else
        {
            Feed\Export\Track\Agent::unregister($agentParams);
        }
    }

    public function getTrackSourceList()
    {
        $result = [];

        /** @var Feed\Export\PromoProduct\Model $promoProduct */
        foreach ($this->getProductCollection() as $promoProduct)
        {
            $result = array_merge(
                $result,
                $promoProduct->getTrackSourceList()
            );
        }

        if ($this->isSupportGift())
        {
            /** @var Feed\Export\PromoGift\Model $promoGift */
            foreach ($this->getGiftCollection() as $promoGift)
            {
                $result = array_merge(
                    $result,
                    $promoGift->getTrackSourceList()
                );
            }
        }

        if ($this->discount !== null)
        {
            $result = array_merge($result, $this->discount->getTrackSourceList());
        }

        return $result;
    }

	public function getContext($isOnlySelf = false)
	{
		$result = [
			'PROMO_ID' => $this->getId(),
            'HAS_DISCOUNT_PRICE' => $this->hasProductDiscountPrice()
		];

		if (!$isOnlySelf)
		{
			$result = $this->mergeParentContext($result);
		}

		return $result;
	}

	public function hasAutoUpdateSetup()
    {
        $result = false;

        /** @var Feed\Export\Setup\Model $setup */
        foreach ($this->getSetupCollection() as $setup)
        {
            if ($setup->isAutoUpdate() && $setup->isFileReady())
            {
                $result = true;
                break;
            }
        }

        return $result;
    }

	protected function mergeParentContext($selfContext)
	{
		/** @var Feed\Export\Setup\Model $parent */
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

    /**
     * @return Feed\Export\PromoProduct\Collection
     */
    public function getProductCollection()
    {
        return $this->getChildCollection('PROMO_PRODUCT');
    }

	/**
	 * @return bool
	 */
	public function isSupportGift()
    {
        return $this->getPromoType() === Table::PROMO_TYPE_GIFT_WITH_PURCHASE;
    }

    /**
     * @return Feed\Export\PromoGift\Collection
     */
    public function getGiftCollection()
    {
        return $this->getChildCollection('PROMO_GIFT');
    }

	/**
	 * @return int|null
	 */
	public function getGiftLimit()
    {
        return 12;
    }

    /**
     * @return Feed\Export\Setup\Collection
     */
    public function getSetupCollection()
    {
        return $this->getChildCollection('SETUP');
    }

    protected function getChildCollectionQueryParameters($fieldKey)
    {
        $result = [];

        switch ($fieldKey)
        {
            case 'SETUP':
                if (!$this->isExportForAll())
                {
                    $result['filter'] = [
                        '=PROMO_LINK.PROMO_ID' => $this->getId()
                    ];
                }
            break;

            default:
                $result = parent::getChildCollectionQueryParameters($fieldKey);
            break;
        }

        return $result;
    }

    protected function loadChildCollection($fieldKey)
    {
        $result = null;

        if ($this->discount !== null)
        {
            switch ($fieldKey)
            {
                case 'PROMO_GIFT':
                	$iblockList = ($this->discount !== null ? $this->discount->getGiftIblockList() : null);

                	$result = $this->buildPromoProductCollection($fieldKey, $iblockList);
				break;

                case 'PROMO_PRODUCT':
                    $result = $this->buildPromoProductCollection($fieldKey);
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
     * @param $iblockList int[]|null
     *
     * @return Feed\Reference\Storage\Collection
     */
    protected function buildPromoProductCollection($fieldKey, $iblockList = null)
    {
		/** @var Feed\Reference\Storage\Collection $result */
        /** @var Feed\Export\PromoProduct\Model $promoProduct */

        $collectionClassName = $this->getChildCollectionReference($fieldKey);
        $modelClassName = $collectionClassName::getItemReference();

        $result = new $collectionClassName();
        $result->setParent($this);

        if ($iblockList === null) { $iblockList = $this->getSetupIblockList(); }

        foreach ($iblockList as $iblockId)
        {
            $promoProduct = $modelClassName::initialize([
                'PROMO_ID' => $this->getId(),
                'IBLOCK_ID' => $iblockId
            ]);

            $promoProduct->setCollection($result);
            $promoProduct->setDiscount($this->discount);

            $result->addItem($promoProduct);
        }

        return $result;
    }

	/**
	 * ������ ����������, ������������ � ������� ��������
	 *
	 * @return int[]
	 */
	protected function getSetupIblockList()
	{
		/** @var Feed\Export\IblockLink\Model $iblockLink */
		$parent = $this->getParent();
		$iblockList = [];

		if ($parent !== null && ($parent instanceof Feed\Export\Setup\Model))
		{
			foreach ($parent->getIblockLinkCollection() as $iblockLink)
			{
				$iblockList[$iblockLink->getIblockId()] = true;
			}
		}
		else
		{
			foreach ($this->getSetupCollection() as $setup)
			{
				foreach ($setup->getIblockLinkCollection() as $iblockLink)
				{
					$iblockList[$iblockLink->getIblockId()] = true;
				}
			}
		}

		return array_keys($iblockList);
	}

    protected function getChildCollectionReference($fieldKey)
    {
        $result = null;

        switch ($fieldKey)
        {
            case 'PROMO_PRODUCT':
                $result = Feed\Export\PromoProduct\Collection::getClassName();
            break;

            case 'PROMO_GIFT':
                $result = Feed\Export\PromoGift\Collection::getClassName();
            break;

            case 'SETUP':
                $result = Feed\Export\Setup\Collection::getClassName();
            break;
        }

        return $result;
    }
}