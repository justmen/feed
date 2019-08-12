<?php

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Ligacom\Feed;

define('BX_SESSION_ID_CHANGE', false);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

Loc::loadMessages(__FILE__);

$response = null;

try
{
	if (!$USER->IsAdmin())
	{
		throw new Main\SystemException(Loc::getMessage('LIGACOM_FEED_ACCESS_DENIED'));
	}
	else if (!Main\Loader::includeModule('ligacom.feed'))
	{
		throw new Main\SystemException(Loc::getMessage('LIGACOM_FEED_MODULE_NOT_INSTALLED'));
	}

	session_write_close(); // release sessions

	$request = Main\Context::getCurrent()->getRequest();
	$iblockId = (int)$request->getPost('IBLOCK_ID');
	$sourcePath = $request->getPost('SOURCE_FIELD');
	$sourceParts = explode('.', $sourcePath);
	$query = trim($request->getPost('QUERY'));

	if (!Main\Application::isUtfMode())
	{
		$query = Main\Text\Encoding::convertEncoding($query, 'UTF-8', LANG_CHARSET);
	}

	if ($iblockId > 0 && strlen($query) > 1 && count($sourceParts) === 2)
	{
		$sourceTypeName = $sourceParts[0];
		$sourceFieldName = $sourceParts[1];
		$context = Feed\Export\Entity\Iblock\Provider::getContext($iblockId);
		$source = Feed\Export\Entity\Manager::getSource($sourceTypeName);
		$sourceField = $source->getField($sourceFieldName, $context);

		if ($sourceField === null)
		{
			throw new Main\ObjectNotFoundException(Loc::getMessage('LIGACOM_FEED_FILTER_AUTOCOMPLETE_FIELD_NOT_FOUND'));
		}
		else if (empty($sourceField['AUTOCOMPLETE']))
		{
			throw new Main\SystemException(Loc::getMessage('LIGACOM_FEED_FILTER_AUTOCOMPLETE_FIELD_NOT_SUPPORT_AUTOCOMPLETE'));
		}
		else
		{
			$suggestList = $source->getFieldAutocomplete($sourceField, $query, $context);

			$response = [
				'status' => 'ok',
				'suggest' => (array)$suggestList
			];
		}
	}
}
catch (Main\SystemException $exception)
{
	$response = [
		'status' => 'error',
		'message' => $exception->getMessage()
	];
}

echo Feed\Utils::jsonEncode($response, JSON_UNESCAPED_UNICODE);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin_after.php';