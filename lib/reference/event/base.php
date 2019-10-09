<?php

namespace Ligacom\Feed\Reference\Event;

use Bitrix\Main;

abstract class Base
{
	public static function getClassName()
	{
		return '\\' . get_called_class();
	}

	/**
	 * ��������� �������
	 *
	 * @param $handlerParams array|null ��������� �����������, �����:
	 *               module => string # �������� ������
	 *               event => string, # �������� �������
	 *               method => string, # �������� ������ (�������������)
	 *               sort => integer, # ���������� (�������������)
	 *               arguments => array # ��������� (�������������)
	 *
	 * @throws Main\NotImplementedException
	 * @throws Main\SystemException
	 * */
	public static function register($handlerParams = null)
	{
		$className = static::getClassName();

		$handlerParams = !isset($handlerParams) ? static::getDefaultParams() : array_merge(
			static::getDefaultParams(),
			$handlerParams
		);

		Controller::register($className, $handlerParams);
	}

	/**
	 * ������� �������
	 *
	 * @param null $handlerParams
	 */
	public static function unregister($handlerParams = null)
	{
		$className = static::getClassName();

		$handlerParams = !isset($handlerParams) ? static::getDefaultParams() : array_merge(
			static::getDefaultParams(),
			$handlerParams
		);

		Controller::unregister($className, $handlerParams);
	}

	/**
	 * @return array �������� ����������� ��� ���������� �� ���������, �����:
	 *               module => string # �������� ������
	 *               event => string, # �������� �������
	 *               method => string, # �������� ������ (�������������)
	 *               sort => integer, # ���������� (�������������)
	 *               arguments => array # ��������� (�������������)
	 * */

	public static function getDefaultParams()
	{
		return array();
	}
}
