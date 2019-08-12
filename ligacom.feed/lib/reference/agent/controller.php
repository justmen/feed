<?php

namespace Ligacom\Feed\Reference\Agent;

use Bitrix\Main;
use CAgent;
use Ligacom\Feed\Config;

class Controller
{
	/**
	 * ��������� �����
	 *
	 * @param $�lassName string
	 * @param $agentParams array|null
	 *
	 * @throws Main\NotImplementedException
	 * @throws Main\SystemException
	 * */
	public static function register($className, $agentParams)
	{
		$agentDescription = static::getAgentDescription($className, $agentParams);
		$registeredAgent = static::getRegisteredAgent($agentDescription);

		static::saveAgent($agentDescription, $registeredAgent);
	}

	/**
	 * ������� �����
	 *
	 * @param $className
	 * @param $agentParams
	 *
	 * @throws \Bitrix\Main\NotImplementedException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function unregister($className, $agentParams)
	{
		$agentDescription = static::getAgentDescription($className, $agentParams);
		$registeredAgent = static::getRegisteredAgent($agentDescription);

		if ($registeredAgent)
		{
			static::deleteAgent($registeredAgent);
		}
	}

	/**
	 * ��������� �������� ���������� �������
	 *
	 * @throws Main\NotImplementedException
	 * @throws Main\SystemException
	 * */
	public static function updateRegular()
	{
		$baseClassName = Regular::getClassName();

		$classList = static::getClassList($baseClassName);
		$agentList = static::getClassAgents($classList);
		$registeredList = static::getRegisteredAgents($baseClassName);

		static::saveAgents($agentList, $registeredList);
		static::deleteAgents($agentList, $registeredList);
	}

	/**
	 * ������� ��� ������
	 *
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function deleteAll()
	{
		$namespace = Config::getNamespace();
		$registeredList = static::getRegisteredAgents($namespace, true);

		static::deleteAgents([], $registeredList);
	}

	/**
	 * ������� ������ ������� � ������� ������ ��� ������
	 *
	 * @param $classList array ������ �������
	 *
	 * @return array ������ ������� ��� �����������
	 * @throws Main\NotImplementedException
	 */
	public static function getClassAgents($classList)
	{
		$agentList = array();

		/** @var Regular $className */
		foreach ($classList as $className)
		{
			$normalizedClassName = $className::getClassName();
			$agents = $className::getAgents();

			foreach ($agents as $agent)
			{
				$agentDescription = static::getAgentDescription(
					$normalizedClassName,
					$agent
				);
				$agentKey = strtolower($agentDescription['name']);

				$agentList[$agentKey] = $agentDescription;
			}
		}

		return $agentList;
	}

	/**
	 * ���������� �������� ������ ��� �����������, ��������� ������������� ������
	 *
	 * @param $className string
	 * @param $agentParams array|null ��������� ������
	 *
	 * @return array
	 * @throws Main\NotImplementedException
	 */
	public static function getAgentDescription($className, $agentParams)
	{
		$method = isset($agentParams['method']) ? $agentParams['method'] : 'run';

		if (!method_exists($className, $method))
		{
			throw new Main\NotImplementedException(
				'Method ' . $method
				. ' not defined in ' . $className
				. ' and cannot be registered as agent'
			);
		}

		$agentFnCall = static::getAgentCall(
			$className,
			$method,
			isset($agentParams['arguments']) ? $agentParams['arguments'] : null
		);

		return array(
			'name'      => $agentFnCall,
			'sort'      => isset($agentParams['sort']) ? (int)$agentParams['sort'] : 100,
			'interval'  => isset($agentParams['interval']) ? (int)$agentParams['interval'] : 86400,
			'next_exec' => isset($agentParams['next_exec']) ? $agentParams['next_exec'] : ''
		);
	}

	/**
	 * �������� ������ ����� ������������������ �������
	 *
	 * @param $baseClassName string �������� ������, ����������� �������� ���������� ��������
	 * @param $isBaseNamespace bool ������ �������� �� �������� �������
	 *
	 * @return array ������ ������������������ �������
	 * */
	public static function getRegisteredAgents($baseClassName, $isBaseNamespace = false)
	{
		$registeredList = array();
		$namespaceLower = strtolower(Config::getNamespace());
		$query = CAgent::GetList(
			array(),
			array(
				'NAME' => $namespaceLower . '%'
			)
		);

		while ($agentRow = $query->fetch())
		{
			$agentCallParts = explode('::', $agentRow['NAME']);
			$agentClassName = trim($agentCallParts[0]);

			if (
				$isBaseNamespace
				|| $agentClassName === ''
				|| !class_exists($agentClassName)
				|| is_subclass_of($agentClassName, $baseClassName)
			)
			{
				$agentKey = strtolower($agentRow['NAME']);
				$registeredList[$agentKey] = $agentRow;
			}
		}

		return $registeredList;
	}

	/**
	 * �������� ������������������ ����� ��� ������ ������
	 *
	 * @param $agentDescription array
	 *
	 * @return array|null ������������������ �����
	 * */
	public static function getRegisteredAgent($agentDescription)
	{
		$query = CAgent::GetList(
			array(),
			array(
				'NAME' => $agentDescription['name']
			)
		);

		return $query->fetch() ?: null;
	}

	/**
	 * ���������� ������ ��� ������ ������ callAgent ������ ����� eval
	 *
	 * @param $className string
	 * @param $method string
	 * @param $arguments array|null
	 *
	 * @return string ����� ������
	 * */
	public static function getAgentCall($className, $method, $arguments = null)
	{
		return static::getFunctionCall(
			$className,
			'callAgent',
			isset($arguments)
				? array( $method, $arguments )
				: array( $method )
		);
	}

	/**
	 * ���������� ������ ��� ������ ����� ����� ����� eval
	 *
	 * @param $className string
	 * @param $method string
	 * @param $arguments array|null
	 *
	 * @return string ����� ������
	 * */
	public static function getFunctionCall($className, $method, $arguments = null)
	{
		$argumentsString = '';

		if (is_array($arguments))
		{
			$isFirstArgument = true;

			foreach ($arguments as $argument)
			{
				if (!$isFirstArgument)
				{
					$argumentsString .= ', ';
				}

				$argumentsString .= var_export($argument, true);

				$isFirstArgument = false;
			}
		}

		return $className . '::' . $method . '(' . $argumentsString . ');';
	}

	/**
	 * ���������� ������ ���� ���������� ���������� Agent
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
	 * ������������ ��� ������ � ���� ������
	 *
	 * @params $agentList array ������ ������� ��� �����������
	 * @params $registeredList array ������ ����� ������������������ �������
	 *
	 * @throws Main\SystemException
	 * */
	protected static function saveAgents($agentList, $registeredList)
	{
		foreach ($agentList as $agentKey => $agent)
		{
			static::saveAgent(
				$agent,
				isset($registeredList[$agentKey]) ? $registeredList[$agentKey] : null
			);
		}
	}

	/**
	 * ������������ ����� � ���� ������
	 *
	 * @params $agent array ����� ��� �����������
	 * @params $registeredAgent array ����� ������������������ �����
	 *
	 * @throws Main\SystemException
	 * */
	protected static function saveAgent($agent, $registeredAgent)
	{
		global $APPLICATION;

		$agentData = array(
			'NAME'           => $agent['name'],
			'MODULE_ID'      => Config::getModuleName(),
			'SORT'           => $agent['sort'],
			'ACTIVE'         => 'Y',
			'AGENT_INTERVAL' => $agent['interval'],
			'IS_PERIOD'      => 'N',
			'USER_ID'        => 0
		);

		if (!empty($agent['next_exec']))
		{
			$agentData['NEXT_EXEC'] = $agent['next_exec'];
		}

		if (!isset($registeredAgent)) // ��������� �����, ���� �����������
		{
			$saveResult = CAgent::Add($agentData);
		}
		else
		{
			$saveResult = CAgent::Update($registeredAgent['ID'], $agentData);
		}

		if (!$saveResult)
		{
			$exception = $APPLICATION->GetException();

			throw new Main\SystemException(
				'agent '
				. $agent['name']
				. ' register error'
				. ($exception ? ': ' . $exception->GetString() : '')
			);
		}
	}

	/**
	 * ������� �������������� ������ �� ���� ������
	 *
	 * @params $agentList array ������ ������� ��� �����������
	 * @params $registeredList array ������ ����� ������������������ �������
	 *
	 * @throws Main\SystemException
	 * */
	protected static function deleteAgents($agentList, $registeredList)
	{
		foreach ($registeredList as $agentKey => $agentRow)
		{
			if (!isset($agentList[$agentKey]))
			{
				static::deleteAgent($agentRow);
			}
		}
	}

	/**
	 * ������� ����� �� ���� ������
	 *
	 * @params $registeredRow array ����� ������������������ �����
	 *
	 * @throws Main\SystemException
	 * */
	protected static function deleteAgent($registeredRow)
	{
		$deleteResult = CAgent::Delete($registeredRow['ID']);

		if (!$deleteResult)
		{
			throw new Main\SystemException('agent ' . $registeredRow['NAME'] . ' not deleted');
		}
	}
}

