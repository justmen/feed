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


	const ENTITY_TYPE_ROOT = 'root';
	const ENTITY_TYPE_OFFER = 'offer';
	const ENTITY_TYPE_CATEGORY = 'category';
	const ENTITY_TYPE_CURRENCY = 'currency';


	protected static $registeredAgentMethods = [];
	protected static $registeredChanges = [];

	/**
	 * @return String[]
	 */
	public static function getSteps()
	{
		return [
			static::STEP_ROOT,
			static::STEP_OFFER
		];
	}

	public static function getStepsWeight()
	{
		return [
			static::STEP_ROOT => 5,
			static::STEP_OFFER => 65
		];
	}

    /**
     * @param $stepName
     * @param Processor $processor
     * @return Steps\Offer|Steps\Root|null
     * @throws Main\SystemException
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