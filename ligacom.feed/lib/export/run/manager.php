<?php

namespace Ligacom\Feed\Export\Run;

use Bitrix\Main;
use Ligacom\Feed;

Main\Localization\Loc::loadMessages(__FILE__);

class Manager
{
	const STEP_ROOT = 'root';
	const STEP_OFFER = 'offer';
	const STEP_CURRENCY = 'currency';
	const STEP_CATEGORY = 'category';
	const STEP_PROMO_PRODUCT = 'promo_product';
	const STEP_PROMO_GIFT = 'promo_gift';
	const STEP_PROMO = 'promo';
	const STEP_GIFT = 'gift';

	const ENTITY_TYPE_ROOT = 'root';
	const ENTITY_TYPE_OFFER = 'offer';
	const ENTITY_TYPE_CATEGORY = 'category';
	const ENTITY_TYPE_CURRENCY = 'currency';
	const ENTITY_TYPE_PROMO = 'promo';
	const ENTITY_TYPE_GIFT = 'gift';

	protected static $registeredAgentMethods = [];
	protected static $registeredChanges = [];

	/**
	 * @return String[]
	 */
	public static function getSteps()
	{
		return [
			static::STEP_ROOT,
			static::STEP_OFFER,
			static::STEP_CATEGORY,
			static::STEP_CURRENCY,
			static::STEP_PROMO_PRODUCT,
			static::STEP_PROMO_GIFT,
            static::STEP_GIFT,
			static::STEP_PROMO,
		];
	}

	public static function getStepsWeight()
	{
		return [
			static::STEP_ROOT => 5,
			static::STEP_OFFER => 65,
			static::STEP_CATEGORY => 5,
			static::STEP_CURRENCY => 5,
            static::STEP_PROMO_PRODUCT => 5,
            static::STEP_PROMO_GIFT => 5,
            static::STEP_GIFT => 5,
            static::STEP_PROMO => 5,
		];
	}

	/**
	 * @param $stepName
	 * @param Processor $processor
	 *
	 * @return Steps\Base
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getStepProvider($stepName, Processor $processor)
	{
		$result = null;

		switch ($stepName)
		{
			case static::STEP_ROOT:
				$result = new Steps\Root($processor);
			break;

			case static::STEP_OFFER:
				$result = new Steps\Offer($processor);
			break;

			case static::STEP_CATEGORY:
				$result = new Steps\Category($processor);
			break;

			case static::STEP_CURRENCY:
				$result = new Steps\Currencies($processor);
			break;

			case static::STEP_PROMO_PRODUCT:
				$result = new Steps\PromoProduct($processor);
			break;

			case static::STEP_PROMO_GIFT:
				$result = new Steps\PromoGift($processor);
			break;

			case static::STEP_PROMO:
				$result = new Steps\Promo($processor);
			break;

			case static::STEP_GIFT:
				$result = new Steps\Gift($processor);
			break;

			default:
				throw new Main\SystemException('not found export run step');
			break;
		}

		return $result;
	}

	public static function getStepTitle($stepName)
	{
		return Feed\Config::getLang('EXPORT_RUN_STEP_' . strtoupper($stepName));
	}

	public static function isChangeRegistered($setupId, $entityType, $entityId)
	{
		$changeKey = $setupId . ':' . $entityType . ':' . $entityId;

		return isset(static::$registeredChanges[$changeKey]);
	}

	public static function registerChange($setupId, $entityType, $entityId)
	{
		$changeKey = $setupId . ':' . $entityType . ':' . $entityId;

		if (!isset(static::$registeredChanges[$changeKey]))
		{
			static::$registeredChanges[$changeKey] = true;

			$queryExists = Storage\ChangesTable::getList([
				'filter' => [
					'=SETUP_ID' => $setupId,
					'=ENTITY_TYPE' => $entityType,
					'=ENTITY_ID' => $entityId
				]
			]);

			if (!$queryExists->fetch())
			{
				Storage\ChangesTable::add([
					'SETUP_ID' => $setupId,
					'ENTITY_TYPE' => $entityType,
					'ENTITY_ID' => $entityId,
					'TIMESTAMP_X' => new Main\Type\DateTime()
				]);
			}

			static::registerAgent('change');
		}
	}

	public static function releaseChanges($setupId, Main\Type\DateTime $dateTime)
	{
		Storage\ChangesTable::deleteBatch([
			'filter' => [
				'=SETUP_ID' => $setupId,
				'<=TIMESTAMP_X' => $dateTime
			]
		]);
	}

	protected static function registerAgent($method)
	{
		if (!isset(static::$registeredAgentMethods[$method]))
		{
			static::$registeredAgentMethods[$method] = true;

			Agent::register([
				'method' => $method,
				'sort' => 200 // more priority
			]);
		}
	}
}