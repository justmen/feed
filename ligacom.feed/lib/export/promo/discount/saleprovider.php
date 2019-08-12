<?php

namespace Ligacom\Feed\Export\Promo\Discount;

use Bitrix\Main;
use Bitrix\Sale;
use Ligacom\Feed;

Main\Localization\Loc::loadMessages(__FILE__);

class SaleProvider extends AbstractProvider
{
    const SALE_ACTION_BASKET_DISCOUNT = 'ActSaleBsktGrp';
    const SALE_ACTION_GIFT = 'GiftCondGroup';

    const SALE_DISCOUNT_TYPE_DISCOUNT = 'Discount';
    const SALE_DISCOUNT_UNIT_PERCENT = 'Perc';
    const SALE_DISCOUNT_UNIT_SUM = 'CurEach';

    const SALE_CONDITION_BASKET_COUNT_GROUP = 'CondBsktCntGroup';
    const SALE_CONDITION_BASKET_ROW_GROUP = 'CondBsktRowGroup';
    const SALE_CONDITION_BASKET_QUANTITY = 'CondBsktFldQuantity';

    /**
     * �������������� �� ���������� � ������� ���������
     *
     * @return bool
     */
    public static function isEnvironmentSupport()
    {
        return Main\ModuleManager::isModuleInstalled('sale');
    }

    public static function getTitle()
    {
    	$isOnlySale = Feed\Utils::isOnlySaleDiscount();

        return Feed\Config::getLang('EXPORT_PROMO_PROVIDER_SALE_DISCOUNT_TITLE' . ($isOnlySale ? '_ALONE' : ''));
    }

    public static function getDescription()
    {
        return Feed\Config::getLang('EXPORT_PROMO_PROVIDER_SALE_DISCOUNT_DESCRIPTION');
    }

    /**
     * ������ ������ ��� ������ � �������� ���������
     *
     * @return array
     */
    public static function getExternalEnum()
    {
        $result = [];

        if (Main\Loader::includeModule('sale'))
        {
            $query = Sale\Internals\DiscountTable::getList([
                'select' => [ 'ID', 'NAME' ]
            ]);

            while ($discount = $query->fetch())
            {
                $result[] = [
                    'ID' => $discount['ID'],
                    'VALUE' => '[' . $discount['ID'] . '] ' . $discount['NAME']
                ];
            }
        }

        return $result;
    }

    /**
     * ���������� ��� ������
     *
     * @return string|null
     */
    public function detectPromoType()
    {
        $actionList = $this->getActionList();
        $firstAction = reset($actionList);
        $result = null;

        if ($firstAction !== false)
        {
            $result = $this->getActionType($firstAction);
        }

        return $result;
    }

    /**
     * ������ ��� promo
     *
     * @return array
     */
    public function getPromoFields()
    {
        $result = [
            'START_DATE' => $this->getField('ACTIVE_FROM'),
            'FINISH_DATE' => $this->getField('ACTIVE_TO'),
            'DISCOUNT_UNIT' => null,
            'DISCOUNT_CURRENCY' => null,
            'DISCOUNT_VALUE' => null,
            'PROMO_CODE' => null,
            'GIFT_REQUIRED_QUANTITY' => null,
            'GIFT_FREE_QUANTITY' => null,
        ];

        switch ($this->getPromoType())
        {
            case Feed\Export\Promo\Table::PROMO_TYPE_GIFT_WITH_PURCHASE:
                $conditionQuantity = $this->getRequiredQuantityFromConditions();

                $result['GIFT_REQUIRED_QUANTITY'] = ($conditionQuantity !== null ? $conditionQuantity : 1);
                $result['GIFT_FREE_QUANTITY'] = 1;
            break;

            case Feed\Export\Promo\Table::PROMO_TYPE_PROMO_CODE:
                $result['PROMO_CODE'] = $this->getPromoCode();

                $discountRule = $this->getPromoDiscountRule();

                if ($discountRule !== null)
                {
                    $result = array_merge($result, $discountRule);
                }
            break;

            default:
                $discountRule = $this->getPromoDiscountRule();

                if ($discountRule !== null)
                {
                    $result = array_merge($result, $discountRule);
                }
            break;
        }

        return $result;
    }

    /**
     * ������ �� �������
     *
     * @param $context
     *
     * @return array
     */
    public function getProductFilterList($context)
    {
        switch ($this->getPromoType())
        {
            case Feed\Export\Promo\Table::PROMO_TYPE_GIFT_WITH_PURCHASE:
                $result = $this->getProductFilterListFromConditions($context);
            break;

            default:
                $result = $this->getProductFilterListFromActions($context);
            break;
        }

        return $result;
    }

	/**
	 * ������ ���������� � ���������.
	 * ��� �������� null ����� ������������ ���������, ������� ������� � ������� ��������.
	 *
	 * @return int[]|null
	 */
	public function getGiftIblockList()
	{
		$result = null;

		if ($this->getPromoType() === Feed\Export\Promo\Table::PROMO_TYPE_GIFT_WITH_PURCHASE)
		{
			$iblockList = $this->getProductIblockListFromActions();
			$iblockList = $this->mergeIblockListOfferToCatalog($iblockList);

			if (!empty($iblockList))
			{
				$result = $iblockList;
			}
		}

		return $result;
	}

    /**
     * ������ �� ��������
     *
     * @param $context
     *
     * @return array
     */
    public function getGiftFilterList($context)
    {
        switch ($this->getPromoType())
        {
            case Feed\Export\Promo\Table::PROMO_TYPE_GIFT_WITH_PURCHASE:
                $result = $this->getProductFilterListFromActions($context);
            break;

            default:
                $result = []; // no gift
            break;
        }

        return $result;
    }

    /**
     * ��������� ������� ������ � ������
     *
     * @param $productId
     * @param $price
     * @param null $currency
     * @param null $filterData
     *
     * @return float
     */
    public function applyDiscountRules($productId, $price, $currency = null, $filterData = null)
    {
        $result = $price;

        if (isset($filterData['RULE']))
        {
            $result = $this->applySimpleDiscountRule($filterData['RULE'], $price, $currency);
        }

        return $result;
    }

    /**
     * ��������� ������� ������� ������ � ���� �� �����
     *
     * @param $rule
     * @param $price
     * @param $currency
     *
     * @return float
     */
    protected function applySimpleDiscountRule($rule, $price, $currency)
    {
        $result = $price;
        $currency = (string)$currency;
        $discountValue = $rule['DISCOUNT_VALUE'];
        $discountLimit = $rule['DISCOUNT_LIMIT'];
        $discountCurrency = (string)$rule['DISCOUNT_CURRENCY'];
        $isMatchCurrency = ($currency === '' || $discountCurrency === '' || $currency === $discountCurrency);
        $discountSum = 0;

        switch ($rule['DISCOUNT_UNIT'])
        {
            case Feed\Export\Promo\Table::DISCOUNT_UNIT_CURRENCY:
                if ($isMatchCurrency)
                {
                    $discountSum = $discountValue;
                }
                else if (Main\Loader::includeModule('currency'))
                {
                    $discountSum = \CCurrencyRates::ConvertCurrency($discountValue, $discountCurrency, $currency);
                }
            break;

            case Feed\Export\Promo\Table::DISCOUNT_UNIT_PERCENT:
                $discountSum = $result * ($discountValue / 100);
            break;
        }

        if ($discountLimit > 0 && !$isMatchCurrency && Main\Loader::includeModule('currency'))
        {
            $discountLimit = \CCurrencyRates::ConvertCurrency($discountLimit, $discountCurrency, $currency);
        }

        if ($discountLimit > 0 && $discountSum > $discountLimit)
        {
            $discountSum = $discountLimit;
        }

        if ($discountSum > 0)
        {
            $precision = $this->getApplyPrecision();
            $discountSum = roundEx($discountSum, $precision);

            $result -= $discountSum;
        }

        return $result;
    }

    /**
     * �������� ���������� ��� ���������� ������
     *
     * @return int
     */
    protected function getApplyPrecision()
    {
        return (int)Main\Config\Option::get('sale', 'value_precision', 2, '');
    }

    /**
     * ������� ������ (���������� ������������, ����������� �� ������������)
     *
     * @return array|null
     */
    protected function getPromoDiscountRule()
    {
        $actionList = $this->getActionList();
        $result = null;

        foreach ($actionList as $action)
        {
            $actionRule = $this->getActionDiscountRule($action);

            if (
                $actionRule !== null
                && (
                    $result === null
                    || ($result['DISCOUNT_UNIT'] === $actionRule['DISCOUNT_UNIT'] && $result['DISCOUNT_VALUE'] < $actionRule['DISCOUNT_VALUE'])
                )
            )
            {
                $result = $actionRule;
            }
        }

        return $result;
    }

	/**
	 * ������ ������������ ���������� � ��������� ������
	 *
	 * @return int[]
	 */
	protected function getProductIblockListFromActions()
	{
		$actionList = $this->getActionList();
		$result = [];

		foreach ($actionList as $action)
		{
			$actionIblockList = QueryBuilder::getActionIblockList($action);

			foreach ($actionIblockList as $actionIblockId)
			{
				if (!in_array($actionIblockId, $result, true))
				{
					$result[] = $actionIblockId;
				}
			}
		}

		return $result;
	}

	/**
	 * �������� �������� �������� ����������� �� �������
	 *
	 * @param $iblockList int[]
	 *
	 * @return int[]
	 */
	protected function mergeIblockListOfferToCatalog($iblockList)
	{
		$result = [];

		foreach ($iblockList as $iblockId)
		{
			$catalogIblockId = Feed\Export\Entity\Iblock\Provider::getCatalogIblockId($iblockId);

			if ($catalogIblockId === null)
			{
				$catalogIblockId = $iblockId;
			}

			if (!in_array($catalogIblockId, $result, true))
			{
				$result[] = $catalogIblockId;
			}
		}

		return $result;
	}

    /**
     * ������ ��������� ��������� �� ������ ������� ������
     *
     * @param $context
     *
     * @return array
     */
    protected function getProductFilterListFromConditions($context)
    {
        $conditionList = $this->getField('CONDITIONS_LIST');
        $filterList = QueryBuilder::convertActionToFilter($conditionList, $context);
        $result = [];

        if (!empty($filterList))
        {
            foreach ($filterList as $filter)
            {
                $result[] = [
                    'FILTER' => $filter,
                    'DATA' => null
                ];
            }
        }

        return $result;
    }

    /**
     * ������ ��������� ��������� �� ������ ����������� �������� ������
     *
     * @param $context array
     *
     * @return array
     */
    protected function getProductFilterListFromActions($context)
    {
        $actionList = $this->getActionList();
        $result = [];

        foreach ($actionList as $action)
        {
            $actionFilterList = QueryBuilder::convertActionToFilter($action, $context);

            if (!empty($actionFilterList))
            {
                $discountRule = $this->getActionDiscountRule($action);

                foreach ($actionFilterList as $filter)
                {
                    $result[] = [
                        'FILTER' => $filter,
                        'DATA' => [
                            'RULE' => $discountRule
                        ]
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * ���������� ��������� ���������� ��� ��������� �������
     *
     * @return int|null
     */
    protected function getRequiredQuantityFromConditions()
    {
        $result = null;
        $conditionList = $this->getField('CONDITIONS_LIST');
        $quantityConditionList = QueryBuilder::searchConditionList($conditionList, [
            static::SALE_CONDITION_BASKET_COUNT_GROUP,
            static::SALE_CONDITION_BASKET_ROW_GROUP,
            static::SALE_CONDITION_BASKET_QUANTITY
        ]);
        $supportCompareMap = [
            '>'  => true,
            '>=' => true,
            '='  => true
        ];

        foreach ($quantityConditionList as $quantityCondition)
        {
            $conditionValue = null;
            $conditionCompare = QueryBuilder::getConditionCompare($quantityCondition);

            if (isset($quantityCondition['DATA']['Value']))
            {
                $conditionValue = (float)$quantityCondition['DATA']['Value'];
            }
            else if (isset($quantityCondition['DATA']['value']))
            {
                $conditionValue = (float)$quantityCondition['DATA']['value'];
            }

            if (
                $conditionValue > 0 && ($result === null || $conditionValue < $result)
                && isset($supportCompareMap[$conditionCompare['QUERY']])
            )
            {
                $result = $conditionValue;
            }
        }

        return $result;
    }

    /**
     * �������� ������ (���������� ������ ���������� ��������)
     *
     * @return array
     */
    protected function getActionList()
    {
        $actionList = $this->getField('ACTIONS_LIST');
        $firstActionType = null;
        $result = [];

        if (!empty($actionList['CHILDREN']) && is_array($actionList['CHILDREN']))
        {
            foreach ($actionList['CHILDREN'] as $action)
            {
                $actionType = $this->getActionType($action);

                if ($actionType !== null && ($firstActionType === null || $actionType === $firstActionType))
                {
                    $isValidAction = true;

                    if ($actionType !== Feed\Export\Promo\Table::PROMO_TYPE_GIFT_WITH_PURCHASE)
                    {
                        $rule = $this->parseActionDiscountRule($action);
                        $isValidAction = $this->isValidDiscountRule($rule);
                    }

                    if ($isValidAction)
                    {
                        $firstActionType = $actionType;
                        $result[] = $action;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * ��������� ������ ������
     *
     * @return array
     */
    protected function loadFields()
    {
        $result = [];

        if (Main\Loader::includeModule('sale'))
        {
            $query = Sale\Internals\DiscountTable::getList([
                'filter' => [
                    '=ID' => $this->id
                ]
            ]);

            if ($discount = $query->fetch())
            {
                $result = $discount;
            }
        }

        return $result;
    }

    /**
     * ����� ������
     *
     * @return string|null
     */
    protected function getPromoCode()
    {
        $result = null;

        if ($this->getField('USE_COUPONS') === 'Y' && Main\Loader::includeModule('sale'))
        {
            $now = new Main\Type\DateTime();

            $queryCoupon = Sale\Internals\DiscountCouponTable::getList([
                'filter' => [
                    '=DISCOUNT_ID' => $this->id,
                    '=USER_ID' => 0,
                    '=TYPE' => Sale\Internals\DiscountCouponTable::TYPE_MULTI_ORDER,
                    '=ACTIVE' => 'Y',
                    [
                        'LOGIC' => 'OR',
                        'ACTIVE_FROM' => false,
                        '<=ACTIVE_FROM' => $now,
                    ],
                    [
                        'LOGIC' => 'OR',
                        'ACTIVE_TO' => false,
                        '>=ACTIVE_TO' => $now,
                    ]
                ],
                'select' => [
                    'MAX_USE',
                    'USE_COUNT',
                    'COUPON'
                ],
                'limit' => 100
            ]);


            while ($coupon = $queryCoupon->fetch())
            {
                $maxUse = (int)$coupon['MAX_USE'];
                $couponValue = (string)$coupon['COUPON'];

                if ($couponValue !== '' && ($maxUse <= 0 || (int)$coupon['USE_COUNT'] < $maxUse))
                {
                    $result = $couponValue;
                }
            }
        }

        return $result;
    }

    /**
     * ��� ��������
     *
     * @param $action
     *
     * @return string|null
     */
    protected function getActionType($action)
    {
        $result = null;

        if (isset($action['CLASS_ID']))
        {
            switch ($action['CLASS_ID'])
            {
                case static::SALE_ACTION_BASKET_DISCOUNT:
                    $result = (
                        $this->getField('USE_COUPONS') === 'Y'
                            ? Feed\Export\Promo\Table::PROMO_TYPE_PROMO_CODE
                            : Feed\Export\Promo\Table::PROMO_TYPE_FLASH_DISCOUNT
                    );
                break;

                case static::SALE_ACTION_GIFT:
                    $result = Feed\Export\Promo\Table::PROMO_TYPE_GIFT_WITH_PURCHASE; // no support for n plus m
                break;
            }
        }

        return $result;
    }

    /**
     * ������� ������ ��� ��������
     *
     * @param $action
     *
     * @return array|null
     */
    protected function getActionDiscountRule($action)
    {
        $result = null;

        if (isset($action['CLASS_ID']))
        {
            switch ($action['CLASS_ID'])
            {
                case static::SALE_ACTION_BASKET_DISCOUNT:
                    $rule = $this->parseActionDiscountRule($action);

                    if ($this->isValidDiscountRule($rule))
                    {
                        $result = $rule;
                    }
                break;
            }
        }

        return $result;
    }

    /**
     * �������������� �� ������� �������� ��� ��������
     *
     * @param $rule
     *
     * @return bool
     */
    protected function isValidDiscountRule($rule)
    {
        return ($rule['DISCOUNT_TYPE'] === static::SALE_DISCOUNT_TYPE_DISCOUNT && $rule['DISCOUNT_VALUE'] > 0);
    }

    /**
     * ������� ������
     *
     * @param $action
     *
     * @return array
     */
    protected function parseActionDiscountRule($action)
    {
        return [
           'DISCOUNT_TYPE' => isset($action['DATA']['Type']) ? $action['DATA']['Type'] : static::SALE_DISCOUNT_TYPE_DISCOUNT,
           'DISCOUNT_LIMIT' => (float)(isset($action['DATA']['Max']) ? $action['DATA']['Max'] : 0),
           'DISCOUNT_VALUE' => (float)(isset($action['DATA']['Value']) ? $action['DATA']['Value'] : 0),
           'DISCOUNT_UNIT' =>
               isset($action['DATA']['Unit']) && $action['DATA']['Unit'] === static::SALE_DISCOUNT_UNIT_PERCENT
                   ? Feed\Export\Promo\Table::DISCOUNT_UNIT_PERCENT
                   : Feed\Export\Promo\Table::DISCOUNT_UNIT_CURRENCY,
           'DISCOUNT_CURRENCY' => Main\Config\Option::get('sale', 'default_currency', 'RUB', '')
        ];
    }
}