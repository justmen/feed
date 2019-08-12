<?php

namespace Ligacom\Feed;

use Bitrix\Main;
use Ligacom\Feed;

Main\Localization\Loc::loadMessages(__FILE__);

class Environment
{
	const PHP_MIN_VERSION = '5.4.0';

	/**
	 * ��������� �������� ���������
	 *
	 * @return Result\Base
	 */
	public static function check()
	{
		$result = new Feed\Result\Base();

		static::checkPhpVersion($result);
		static::checkSiteEncoding($result);

		return $result;
	}

	/**
	 * ��������� ������ Php
	 *
	 * @param Result\Base $result
	 */
	protected static function checkPhpVersion(Feed\Result\Base $result)
	{
		if (CheckVersion(PHP_VERSION, static::PHP_MIN_VERSION) === false)
		{
			$errorMessage = Feed\Config::getLang('ENVIRONMENT_PHP_MIN_VERSION', [
				'#MIN#' => static::PHP_MIN_VERSION,
				'#CURRENT#' => PHP_VERSION
			]);

			$result->addError(new Feed\Error\Base($errorMessage, 'ENV_PHP_MIN_VERSION'));
		}
	}

	/**
	 * ��������� ������������ ��������� ����� � php-���������
	 *
	 * @param Result\Base $result
	 */
	protected static function checkSiteEncoding(Feed\Result\Base $result)
	{
		$isUtfMode = Main\Application::isUtfMode();
		$mbstringFuncOverload = (int)ini_get('mbstring.func_overload');
		$mbstringEncoding = static::unifyEncoding(ini_get('mbstring.internal_encoding'));
		$defaultEncoding = static::unifyEncoding(ini_get('default_charset'));
		$resultEncoding = $mbstringEncoding ?: $defaultEncoding;

		if ($isUtfMode)
		{
			if ($mbstringFuncOverload !== 2)
			{
				$errorMessage = Feed\Config::getLang('ENCODING_FUNC_OVERLOAD', [
					'#REQUIRED#' => 2,
					'#CURRENT#' => $mbstringFuncOverload
				]);

				$result->addError(new Feed\Error\Base($errorMessage, 'ENV_ENCODING_FUNC_OVERLOAD'));
			}

			if ($resultEncoding !== 'utf8')
			{
				$errorMessage = Feed\Config::getLang('ENCODING_NOT_VALID', [
					'#REQUIRED#' => 'utf-8',
					'#CURRENT#' => $resultEncoding
				]);

				$result->addError(new Feed\Error\Base($errorMessage, 'ENV_ENV_ENCODING_NOT_VALID'));
			}
		}
		else if ($mbstringFuncOverload === 2)
		{
			if ($resultEncoding === 'utf8')
			{
				$langEncoding = static::unifyEncoding(LANG_CHARSET);

				$errorMessage = Feed\Config::getLang('ENCODING_NOT_VALID', [
					'#REQUIRED#' => $langEncoding === 'cp1251' ? 'cp1251' : 'latin1',
					'#CURRENT#' => $resultEncoding
				]);

				$result->addError(new Feed\Error\Base($errorMessage, 'ENV_ENV_ENCODING_NOT_VALID'));
			}
		}
		else if ($mbstringFuncOverload !== 0)
		{
			$errorMessage = Feed\Config::getLang('ENCODING_FUNC_OVERLOAD', [
				'#REQUIRED#' => 0,
				'#CURRENT#' => $mbstringFuncOverload
			]);

			$result->addError(new Feed\Error\Base($errorMessage, 'ENV_ENCODING_FUNC_OVERLOAD'));
		}
	}

	protected static function unifyEncoding($encoding)
	{
		return str_replace(['-', 'windows'], ['', 'cp'], strtolower($encoding));
	}
}