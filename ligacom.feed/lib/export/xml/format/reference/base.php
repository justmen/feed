<?php

namespace Ligacom\Feed\Export\Xml\Format\Reference;

abstract class Base
{
	/**
	 * @return string|null
	 */
	abstract public function getDocumentationLink();

	/**
	 * @return bool
	 */
	abstract public function isSupportDeliveryOptions();

	/**
	 * @return string
	 */
	abstract public function getHeader();

	/**
	 * @return Feed\Export\Xml\Tag\Base
	 * */
	abstract public function getRoot();


	/**
	 * @return string
	 */
	abstract public function getCurrencyParentName();

	/**
	 * @return Feed\Export\Xml\Tag\Base
	 * */
	abstract public function getCurrency();

	/**
	 * @return string
	 */
	abstract public function getOfferParentName();

	/**
	 * @return Feed\Export\Xml\Tag\Base
	 * */
    abstract public function getOffer();

	/**
	 * @return string
	 */
	abstract public function getType();
}
