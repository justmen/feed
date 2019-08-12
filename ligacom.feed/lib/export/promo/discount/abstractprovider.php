<?php

namespace Ligacom\Feed\Export\Promo\Discount;

use Ligacom\Feed;

abstract class AbstractProvider
{
    /** @var int */
    protected $id;
    /** @var array */
    protected $fields;
    /** @var string|null */
    protected $promoType;

    public static function getClassName()
    {
        return '\\' . get_called_class();
    }

    public static function isEnvironmentSupport()
    {
        return true;
    }

    public static function getTitle()
    {
        return '';
    }

    public static function getDescription()
    {
        return '';
    }

    public static function getExternalEnum()
    {
        return null;
    }

    public function __construct($id)
    {
        $this->id = (int)$id;
    }

    public function isActive()
    {
        return ($this->getField('ACTIVE') === 'Y');
    }

    public function getPromoType()
    {
        if ($this->promoType === null)
        {
            $this->promoType = $this->detectPromoType();
        }

        return $this->promoType;
    }

    abstract protected function detectPromoType();

    abstract public function getPromoFields();

    abstract public function getProductFilterList($context);

	/**
	 * ������ ���������� � ���������.
	 * ��� �������� null ����� ������������ ���������, ������� ������� � ������� ��������.
	 *
	 * @return int[]|null
	 */
	public function getGiftIblockList()
	{
		return null;
	}

    abstract public function getGiftFilterList($context);

    abstract public function applyDiscountRules($productId, $price, $currency = null, $filterData = null);

    /**
     * ������ ���������� ��� ������������
     *
     * @return array
     */
    public function getTrackSourceList()
    {
        return [];
    }

    /**
     * ��������� ������, ������� �� ������ � ��������
     *
     * @param $context
     *
     * @return bool
     */
    public function isExportExternalGift($context)
    {
        $option = Feed\Config::getOption('export_promo_discount_external_gift');

        return ($option !== 'N');
    }

    /**
     * @param $key
     *
     * @return mixed
     */
    public function getField($key)
    {
        $fields = $this->getFields();

        return (isset($fields[$key]) ? $fields[$key] : null);
    }

    /**
     * @return array
     */
    public function getFields()
    {
        if ($this->fields === null)
        {
            $this->fields = $this->loadFields();
        }

        return $this->fields;
    }

    abstract protected function loadFields();
}