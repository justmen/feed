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

	$component = new CBitrixComponent();
	$component->arParams = [
		'MODEL_CLASS_NAME' => Feed\Export\Setup\Model::getClassName()
	];

	$provider = new Feed\Component\Setup\EditForm($component);

	$request = Main\Context::getCurrent()->getRequest();
	$baseName = $request->getPost('baseName') ?: 'IBLOCK_LINK';
	$iblockLinkList = (array)$request->getPost($baseName);

	if (!Main\Application::isUtfMode())
	{
		$iblockLinkList = Main\Text\Encoding::convertEncoding($iblockLinkList, 'UTF-8', LANG_CHARSET);
	}

	foreach ($iblockLinkList as &$iblockLink)
	{
		if (isset($iblockLink['ID']))
		{
			unset($iblockLink['ID']);
		}
	}
	unset($iblockLink);

	$setupFields = [
		'EXPORT_SERVICE' => $request->getPost('EXPORT_SERVICE'),
		'EXPORT_FORMAT' => $request->getPost('EXPORT_FORMAT'),
		'IBLOCK_LINK' => $iblockLinkList
	];

	$response = $provider->ajaxActionFilterCount($setupFields, $baseName);
}
catch (Main\SystemException $exception)
{
	$response = [
		'status' => 'error',
		'message' => $exception->getMessage()
	];
}

$APPLICATION->RestartBuffer();
echo Feed\Utils::jsonEncode($response, JSON_UNESCAPED_UNICODE);
die();
