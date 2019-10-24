<?php

namespace Ligacom\Feed\Export\Xml\Format\GoogleMerchant;

use Bitrix\Main;
use \Ligacom\Feed\Export\PromoGift;
use \Ligacom\Feed\Export\Xml;
use Ligacom\Feed\Export\Xml\Format\Reference\Feed;
use Ligacom\Feed\Type;
use \Ligacom\Feed\Export\Promo;
use \Ligacom\Feed\Export\PromoProduct;

class GoogleMerchant extends Xml\Format\Reference\Base
{
	public function getDocumentationLink()
	{
		return 'https://support.google.com/merchants/answer/7052112';
	}

	public function getType()
	{
		return 'google.merchant';
	}

	public function isSupportDeliveryOptions()
	{
		return false;
	}

	public function getHeader()
	{
		$encoding = Main\Application::isUtfMode() ? 'utf-8' : 'windows-1251';

		$result = '<?xml version="1.0" encoding="' . $encoding . '"?>';

		return $result;
	}

	public function getRoot()
	{
		$result = new Xml\Tag\Base([
			'name' => 'rss',
			'attributes' => [
                new Xml\Attribute\Base([ 'name' => 'version', 'value_type' => 'string', 'default_value' => '2.0']),
                new Xml\Attribute\Base([ 'name' => 'xmlns:xmlns:g', 'value_type' => 'string', 'default_value' => 'http://base.google.com/ns/1.0']),
			],
			'children' => [
				new Xml\Tag\Base([
					'name' => 'channel',
					'children' => [
						new Xml\Tag\ShopName(),
						new Xml\Tag\ShopTitle(),
						new Xml\Tag\ShopUrl()
					],
				]),
			],
		]);

		if ($this->isSupportDeliveryOptions())
		{
			$rootChidren = $result->getChildren();
			$shopTag = reset($rootChidren);

			$shopTag->addChild(new Xml\Tag\DeliveryOptions(), -3);
			$shopTag->addChild(new Xml\Tag\PickupOptions(), -3);
		}

		return $result;
	}

	public function getOfferParentName()
	{
		return 'channel';
	}

	/**
	 * @return Xml\Tag\Base
	 */
	public function getOffer()
	{
		return new Xml\Tag\Base([
			'name' => 'item',
			'required' => true,
			'visible' => true,
			'children' => array_merge(
				$this->getOfferDefaultChildren('prolog', [
					'picture' => [ 'required' => false ]
				]),
				[
					new Xml\Tag\gtitle(['required' => true]),
				],
				$this->getOfferDefaultChildren('epilog', [
					'barcode' => [ 'visible' => true ]
				])
			)
		]);
	}

	protected function getOfferDefaultChildren($place, $overrides = null, $sort = null, $excludeList = null)
	{
		$result = [];

		switch ($place)
		{
			case 'prolog':
				$result = [
                    new Xml\Tag\GId(['visible' => true]),
                    new Xml\Tag\GitemGroupId(['visible' => true]),
                    new Xml\Tag\GProductType(),
                    new Xml\Tag\GAvailability(['visible' => true, 'default_value' => 'in stock']),
                    new Xml\Tag\GBrand(['required'=> true]),
                    new Xml\Tag\Base([
                        'name' => 'currency',
                        'empty_value' => true,
                        'attributes' => [
                            new Xml\Attribute\Base(['name' => 'id', 'value_type' => 'currency', 'required' => true]),
                            new Xml\Attribute\Base(['name' => 'rate', 'required' => true]),
                        ],
                    ]),
					new Xml\Tag\GPrice(
					    [
					        'required' => true,
                            'double_field' => [
                                new Xml\Tag\Base(['name'=> 'currencyTest', 'visible'=> true])
                            ],
                        ]
                    ),
					new Xml\Tag\GImageLink(['multiple' => true, 'visible' => true]),

				];

				if ($this->isSupportDeliveryOptions())
				{
					array_splice($result, -2, 0, [
						new Xml\Tag\DeliveryOptions(),
						new Xml\Tag\PickupOptions(),
					]);
				}
			break;

			case 'epilog':
				$result = [

                    new Xml\Tag\GCondition(),
                    new Xml\Tag\GGtin(),
                    new Xml\Tag\GMpn(),
                    new Xml\Tag\CurrencyId([ 'required' => true]),
                    new Xml\Tag\GIdentifierExists(),
                    new Xml\Tag\GMobilLink(),
                    new Xml\Tag\GGoogleProductCategory(),
                    new Xml\Tag\GAdditionalImageLink(['multiple'=>true]),//todo реализовать мультитег из одного источника, например из свойства файл
                    new Xml\Tag\GAvailabilityDate(),
                    new Xml\Tag\GExpirationDate(),
                    new Xml\Tag\GSalePrice(),
                    new Xml\Tag\GIsBundle(),
                    new Xml\Tag\GAgeGroup(),
                    new Xml\Tag\GColor(),
                    new Xml\Tag\GGender(),
                    new Xml\Tag\GMaterial(),
                    new Xml\Tag\GPattern(),
                    new Xml\Tag\GSize(),
                    new Xml\Tag\GSizeType(),
                    new Xml\Tag\GSizeSystem(),
                    new Xml\Tag\GCustomLabel0(),
                    new Xml\Tag\GCustomLabel1(),
                    new Xml\Tag\GCustomLabel2(),
                    new Xml\Tag\GCustomLabel3(),
                    new Xml\Tag\GCustomLabel4(),
					new Xml\Tag\Base(
					    [
					        'id' => 'g:excluded_​destination',
					        'name' => 'xmlns:g:excluded_​destination',
                            'value_type' => 'string']
                    ),
					new Xml\Tag\Base(
					    [
					        'id' => 'g:included_​destination',
					        'name' => 'xmlns:g:included_​destination',
                            'value_type' => 'string'
                        ]
                    ),
                    new Xml\Tag\Base(
                        [
                            'id' => 'g:shipping',
                            'name' => 'xmlns:g:shipping',
                            'multiple'=>true,
                            'attributes' => [
                                new Xml\Attribute\Base(['name' => 'name', 'required' => true, 'visible' => true]),
                                new Xml\Attribute\Base(['name' => 'unit']),
                            ],
                            'children'=>[
                                new Xml\Tag\ShopUrl(),
                                new Xml\Tag\Base(
                                    [
                                        'id'=>'g:country',
                                        'name'=>'xmlns:g:country',
                                        'value_type' => 'string',
                                        'visible'=>true]
                                ),
                                new Xml\Tag\Base(
                                    [
                                        'id'=>'g:service',
                                        'name'=>'xmlns:g:service',
                                        'value_type' => 'string',
                                        'visible'=>true
                                    ]
                                ),
                                new Xml\Tag\Base(
                                    [
                                        'id'=>'g:price',
                                        'name'=>'xmlns:g:price',
                                        'value_type' => 'string',
                                        'visible'=>true]
                                ),
                            ]
                        ]
                    ),//todo решить проблему с выводом в списке
					new Xml\Tag\Base(
					    [
					        'id' => 'g:adult',
					        'name' => 'xmlns:g:adult',
                            'value_type' => 'boolean'
                        ]
                    ),
				];
			break;
		}

		$this->overrideTags($result, $overrides);
		$this->excludeTags($result, $excludeList);
		$this->sortTags($result, $sort);

		return $result;
	}

	protected function overrideTags($tags, $overrides)
	{
		if ($overrides !== null)
		{
			/** @var Feed\Export\Xml\Tag\Base $tag */
			foreach ($tags as $tag)
			{
				$tagName = $tag->getName();

				if (isset($overrides[$tagName]))
				{
					$tag->extendParameters($overrides[$tagName]);
				}
			}
		}
	}

	protected function sortTags(&$tags, $sort)
	{
		if ($sort !== null)
		{
			$fullSort = [];
			$nextSortIndex = 10;

			foreach ($tags as $tag)
			{
				$tagId = $tag->getId();
				$fullSort[$tagId] = isset($sort[$tagId]) ? $sort[$tagId] : $nextSortIndex;

				$nextSortIndex += 10;
			}

			uasort($tags, function($tagA, $tagB) use ($fullSort) {
				$tagAId = $tagA->getId();
				$tagBId = $tagB->getId();
				$tagASort = $fullSort[$tagAId];
				$tagBSort = $fullSort[$tagBId];

				if ($tagASort === $tagBSort) { return 0; }

				return ($tagASort < $tagBSort ? -1 : 1);
			});
		}
	}

	protected function excludeTags(&$tags, $excludeList)
	{
		if ($excludeList !== null)
		{
			foreach ($tags as $tagIndex => $tag)
			{
				$tagName = $tag->getName();

				if (isset($excludeList[$tagName]))
				{
					unset($tags[$tagIndex]);
				}
			}
		}
	}


    /**
     * @return Feed\Export\Xml\Tag\Base
     * */
    public function getCurrency()
    {
        // TODO: Implement getCurrency() method.
    }

}
