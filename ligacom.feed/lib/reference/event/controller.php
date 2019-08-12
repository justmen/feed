<?php

namespace Ligacom\Feed\Reference\Event;

use Bitrix\Main;
use Ligacom\Feed\Config;

class Controller
{
	protected static $isCompitibilityVersion = null;

	/**
	 * ��������� ����������
	 *
	 * @param $�lassName string
	 * @param $handlerParams array
	 *
	 * @throws Main\NotImplementedException
	 * @throws Main\SystemException
	 * */
	public static function register($className, $handlerParams)
	{
		$handlerDescription = static::getHandlerDescription($className, $handlerParams);

		static::saveHandler($handlerDescription);
	}

	/**
	 * ������� ����������
	 *
	 * @param $className
	 * @param $handlerParams
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 * @throws \Bitrix\Main\NotImplementedException
	 */
	public static function unregister($className, $handlerParams)
	{
		$handlerDescription = static::getHandlerDescription($className, $handlerParams);
		$registeredList = static::getRegisteredHandlers($className);
		$handlerKey = static::getHandlerKey($handlerDescription, true);

		if (isset($registeredList[$handlerKey]))
		{
			static::deleteHandler($registeredList[$handlerKey]);
		}
	}

	/**
	 * ��������� �������� ���������� ������������ �������
	 *
	 * @throws Main\NotImplementedException
	 * @throws Main\SystemException
	 * */
	public static function updateRegular()
	{
		$baseClassName = Regular::getClassName();

		$classList = static::getClassList($baseClassName);
		$handlerList = static::getClassHandlers($classList);
		$registeredList = static::getRegisteredHandlers($baseClassName);

		static::saveHandlers($handlerList);
		static::deleteHandlers($handlerList, $registeredList);
	}

	/**
	 * ������� ��� �������
	 *
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public static function deleteAll()
	{
		$namespace = Config::getNamespace();
		$registeredList = static::getRegisteredHandlers($namespace, true);

		static::deleteHandlers([], $registeredList);
	}

	/**
	 * ������� ������ ������� � ������� ������ ��� ������
	 *
	 * @param $classList array ������ �������
	 *
	 * @return array ������ ������������ ��� �����������
	 * @throws Main\NotImplementedException
	 * @throws Main\ArgumentException
	 */
	protected static function getClassHandlers($classList)
	{
		$result = array();

		/** @var Regular $className */
		foreach ($classList as $className)
		{
			$normalizedClassName = $className::getClassName();
			$handlers = $className::getHandlers();

			foreach ($handlers as $handler)
			{
				$handlerDescription = static::getHandlerDescription(
					$normalizedClassName,
					$handler
				);
				$handlerKey = static::getHandlerKey($handlerDescription, true);

				$result[$handlerKey] = $handlerDescription;
			}
		}

		return $result;
	}

	/**
	 * ���������� ������ ���� ���������� ���������� Event,
	 * ������������ ��� ����������� ���� ������������ � updateRegular().
	 *
	 * @param string �������� ������, ���������� �������� ���� �������
	 *
	 * @return array ������ ��� ������� ��� ������
	 * */
	protected static function getClassList($baseClassName)
	{
		$baseDir = Config::getModulePath();
		$baseNamespace = Config::getNamespace();
		$directory = new \RecursiveDirectoryIterator($baseDir);
		$iterator = new \RecursiveIteratorIterator($directory);
		$result = [];

		/** @var \DirectoryIterator $entry */
		foreach ($iterator as $entry)
		{
			if (
				$entry->isFile()
				&& $entry->getExtension() == 'php'
			)
			{
				$relativePath = str_replace($baseDir, '', $entry->getPath());
				$className = $baseNamespace . str_replace('/', '\\', $relativePath) . '\\' . $entry->getBasename('.php');
				$tableClassName = $className . 'Table';

				if (
					!empty($relativePath)
					&& !class_exists($tableClassName)
					&& class_exists($className)
					&& is_subclass_of($className, $baseClassName)
				)
				{
					$result[] = $className;
				}
			}
		}

		return $result;
	}

	/**
	 * ���������� �������� ����������� ��� �����������, ��������� ������������� ������
	 *
	 * @param $className Base
	 * @param $handlerParams array|null ��������� �����������
	 *
	 * @return array
	 * @throws Main\NotImplementedException
	 * @throws Main\ArgumentException
	 */
	protected static function getHandlerDescription($className, $handlerParams)
	{
		if (empty($handlerParams['module']) || empty($handlerParams['event']))
		{
			throw new Main\ArgumentException(
				'Require module and event param in ' . $className
			);
		}

		$method = isset($handlerParams['method']) ? $handlerParams['method'] : $handlerParams['event'];

		if (!method_exists($className, $method))
		{
			throw new Main\NotImplementedException(
				'Method ' . $method
				. ' not defined in ' . $className
				. ' and cannot be registered as event handler'
			);
		}

		if (static::isCompatibilityMode($className)) // is compatibility mode
		{
			$newClassName = static::getCompatibilityClassName();
			$newMethod = static::getCompatibilityMethod();
			$newArguments = (
				isset($handlerParams['arguments']) && is_array($handlerParams['arguments'])
					? $handlerParams['arguments']
					: []
			);

			array_unshift($newArguments, $method);
			array_unshift($newArguments, $className);

			$className = $newClassName;
			$method = $newMethod;
			$handlerParams['arguments'] = $newArguments;
		}

		return array(
			'module'    => $handlerParams['module'],
			'event'     => $handlerParams['event'],
			'class'     => $className::getClassName(),
			'method'    => $method,
			'sort'      => isset($handlerParams['sort']) ? (int)$handlerParams['sort'] : 100,
			'arguments' => isset($handlerParams['arguments']) ? $handlerParams['arguments'] : ''
		);
	}

	/**
	 * �������� ������ ����� ������������������ ������������
	 *
	 * @param $baseClassName string �������� ������, ����������� �������� ���������� ��������
	 * @param $isBaseNamespace bool ������ �������� �� �������� �������
	 *
	 * @return array ������ ������������������ ������������
	 * @throws Main\Db\SqlQueryException
	 * */
	protected static function getRegisteredHandlers($baseClassName, $isBaseNamespace = false)
	{
		$registeredList = array();
		$compitilibityClassName = static::getCompatibilityClassName();
		$compitilibityMethod = static::getCompatibilityMethod();
		$namespaceLower = str_replace('\\', '\\\\', strtolower(Config::getNamespace()));
		$connection = Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();
		$query = $connection->query(
			'SELECT * FROM b_module_to_module WHERE TO_CLASS like "' . $sqlHelper->forSql($namespaceLower) . '%"'
		);

		while ($handlerRow = $query->fetch())
		{
			$handlerClassName = $handlerRow['TO_CLASS'];

			if (
				$handlerClassName === $compitilibityClassName
				&& $handlerRow['TO_METHOD'] === $compitilibityMethod
				&& $handlerRow['TO_METHOD_ARG'] !== ''
			)
			{
				$handlerArguments = unserialize($handlerRow['TO_METHOD_ARG']);

				if ($handlerArguments !== false && isset($handlerArguments[0]))
				{
					$handlerClassName = $handlerArguments[0];
				}
			}

			if (
				$isBaseNamespace
				|| $handlerClassName === $baseClassName
				|| !class_exists($handlerClassName)
				|| is_subclass_of($handlerClassName, $baseClassName)
			)
			{
				$handlerKey = static::getHandlerKey($handlerRow, false);
				$registeredList[$handlerKey] = $handlerRow;
			}
		}

		return $registeredList;
	}

	/**
	 * �������� ������������������ ����������
	 *
	 * @param $handlerDescription array
	 *
	 * @return array|null ���������� ����������
	 * @throws Main\Db\SqlQueryException
	 * */
	public static function getRegisteredHandler($handlerDescription)
	{
		$connection = Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();
		$query = $connection->query(
			'SELECT * FROM b_module_to_module'
			. ' WHERE '
				. ' FROM_MODULE_ID = "' . $sqlHelper->forSql($handlerDescription['module']) . '"'
				. ' MESSAGE_ID = "' . $sqlHelper->forSql($handlerDescription['event']) . '"'
				. ' TO_CLASS = "' . $sqlHelper->forSql($handlerDescription['class']) . '"'
				. ' TO_METHOD = "' . $sqlHelper->forSql($handlerDescription['method']) . '"'
		);

		return $query->fetch() ?: null;
	}

	/**
	 * ���� ������� ��� ����������� �������
	 *
	 * @param $handlerData array
	 * @param $byDescription boolean ������������ ���� �� �������� ��� ������������������� �����������
	 *
	 * @return string
	 */
	protected static function getHandlerKey($handlerData, $byDescription = false)
	{
		$signKeys = array(
			'module'    => 'FROM_MODULE_ID',
			'event'     => 'MESSAGE_ID',
			'class'     => 'TO_CLASS',
			'method'    => 'TO_METHOD',
			'arguments' => 'TO_METHOD_ARG'
		);
		$values = array();

		foreach ($signKeys as $descriptionKey => $rowKey)
		{
			$key = $byDescription ? $descriptionKey : $rowKey;
			$values[] = is_array($handlerData[$key]) && !empty($handlerData[$key]) ? serialize($handlerData[$key]) : $handlerData[$key];
		}

		return strtolower(implode('|', $values));
	}

	/**
	 * ������������ ��� ����������� � ���� ������
	 *
	 * @param $handlerList array ������ ������������ ��� �����������
	 *
	 * @throws Main\SystemException
	 * */
	protected static function saveHandlers($handlerList)
	{
		foreach ($handlerList as $handlerKey => $handlerDescription)
		{
			static::saveHandler($handlerDescription);
		}
	}

	/**
	 * ������������ ���������� � ���� ������
	 *
	 * @param $handlerDescription array ����������
	 * */
	protected static function saveHandler($handlerDescription)
	{
		$eventManager = Main\EventManager::getInstance();

		$eventManager->registerEventHandler(
			$handlerDescription['module'],
			$handlerDescription['event'],
			Config::getModuleName(),
			$handlerDescription['class'],
			$handlerDescription['method'],
			$handlerDescription['sort'],
			'',
			$handlerDescription['arguments']
		);
	}

	/**
	 * ������� �������������� ����������� �� ���� ������
	 *
	 * @param $handlerList array ������ ������������ ��� �����������
	 * @param $registeredList array ������ ����� ������������������ ������������
	 * */
	protected static function deleteHandlers($handlerList, $registeredList)
	{
		foreach ($registeredList as $handlerKey => $handlerRow)
		{
			if (!isset($handlerList[$handlerKey]))
			{
				static::deleteHandler($handlerRow);
			}
		}
	}

	/**
	 * ������� ���������� �� ���� ������
	 *
	 * @param $handlerRow array ����� ������������������ ����������
	 * */
	protected static function deleteHandler($handlerRow)
	{
		$eventManager = Main\EventManager::getInstance();

		$handlerArgs = $handlerRow['TO_METHOD_ARG'];

		if (is_string($handlerArgs))
		{
			$handlerArgsUnserialize = unserialize($handlerArgs);

			if (is_array($handlerArgsUnserialize) && !empty($handlerArgsUnserialize))
			{
				$handlerArgs = $handlerArgsUnserialize;
			}
		}

		$eventManager->unregisterEventHandler(
			$handlerRow['FROM_MODULE_ID'],
			$handlerRow['MESSAGE_ID'],
			Config::getModuleName(),
			$handlerRow['TO_CLASS'],
			$handlerRow['TO_METHOD'],
			'',
			$handlerArgs
		);
	}

	protected static function isCompatibilityMode($className)
	{
		$charLimit = 50;

		return (static::isCompatibilityVersion() && strlen($className) > $charLimit);
	}

	protected static function isCompatibilityVersion()
	{
		if (static::$isCompitibilityVersion === null)
		{
			static::$isCompitibilityVersion = ((float)Main\ModuleManager::getVersion('main') < 16);
		}

		return static::$isCompitibilityVersion;
	}

	protected static function getCompatibilityClassName()
	{
		return Config::getNamespace() . '\\EventManager';
	}

	protected static function getCompatibilityMethod()
	{
		return 'callMethod';
	}
}
