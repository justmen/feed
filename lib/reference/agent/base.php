<?php

namespace Ligacom\Feed\Reference\Agent;

use Bitrix\Main;

abstract class Base
{
	public static function getClassName()
	{
		return '\\' . get_called_class();
	}

	/**
	 * ��������� �����
	 *
	 * @param $agentParams array|null ��������� ������, �����:
	 *               method => string # �������� ������ (�������������)
	 *               arguments => array # ��������� ������ ������ (�������������)
	 *               interval => integer, # �������� �������, � �������� (�������������)
	 *               sort => integer, # ����������, ��-��������� � 100 (�������������)
	 *               next_exec => string, # ���� � ������� Y-m-d H:i:s (�������������)
	 *
	 * @throws Main\NotImplementedException
	 * @throws Main\SystemException
	 * */
	public static function register($agentParams = null)
	{
		$className = static::getClassName();

		$agentParams = !isset($agentParams) ? static::getDefaultParams() : array_merge(
			static::getDefaultParams(),
			$agentParams
		);

		Controller::register($className, $agentParams);
	}

	/**
	 * ������� �����
	 *
	 * @param array|null $agentParams
	 *
	 * @throws \Bitrix\Main\NotImplementedException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function unregister($agentParams = null)
	{
		$className = static::getClassName();

		$agentParams = !isset($agentParams) ? static::getDefaultParams() : array_merge(
			static::getDefaultParams(),
			$agentParams
		);

		Controller::unregister($className, $agentParams);
	}

	/**
	 * ������� ��� ������ ������, ��������� ���������� ������ ��� �������� false
	 *
	 * @param $method string
	 * @param $arguments array|null
	 *
	 * @return string|null
	 * */
	public static function callAgent($method, $arguments = null)
	{
		$className = static::getClassName();
		$callResult = null;
		$result = '';

		if (is_array($arguments))
		{
			$callResult = call_user_func_array(array($className, $method), $arguments);
		}
		else
		{
			$callResult = call_user_func(array($className, $method));
		}

		if ($callResult !== false)
		{
			if (is_array($callResult))
			{
				$arguments = $callResult;
			}

			$result = Controller::getAgentCall($className, $method, $arguments);
		}

		return $result;
	}

	/**
	 * @return array �������� ������ ��� ���������� �� ��������� (����� run), �����:
	 *               method => string # �������� ������ (�������������)
	 *               arguments => array # ��������� ������ ������ (�������������)
	 *               interval => integer, # �������� �������, � �������� (�������������)
	 *               sort => integer, # ����������, ��-��������� � 100 (�������������)
	 *               next_exec => string, # ���� � ������� Y-m-d H:i:s (�������������)
	 * */

	public static function getDefaultParams()
	{
		return array();
	}

	/**
	 *  ����� ������ �� ���������, ��������� ��� �������� false
	 *
	 * @return mixed|false
	 * */

	public static function run()
	{
		return false;
	}
}
