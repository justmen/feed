<?php

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Ligacom\Feed;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

$isPopup = (isset($_REQUEST['popup']) && $_REQUEST['popup'] === 'Y');

if ($isPopup)
{
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_popup_admin.php");
}
else
{
	require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';
}

Loc::loadMessages(__FILE__);

if (!$USER->IsAdmin())
{
	$APPLICATION->AuthForm(Loc::getMessage('LIGACOM_FEED_ADMIN_LOG_REQUIRE_MODULE'));
	return;
}
else if (!Main\Loader::includeModule('ligacom.feed'))
{
	\CAdminMessage::ShowMessage([
        'TYPE' => 'ERROR',
        'MESSAGE' => Loc::getMessage('LIGACOM_FEED_ADMIN_LOG_REQUIRE_MODULE')
    ]);
}
else
{

	$APPLICATION->IncludeComponent('ligacom.feed:admin.grid.list', '', array(
		'GRID_ID' => 'LIGACOM_FEED_ADMIN_LOG',
		'PROVIDER_TYPE' => 'Data',
		'DATA_CLASS_NAME' => Ligacom\Feed\Logger\Table::getClassName(),
        'TITLE' => Loc::getMessage('LIGACOM_FEED_ADMIN_LOG_PAGE_TITLE'),
		'LIST_FIELDS' => array(
			'TIMESTAMP_X',
			'LEVEL',
			'MESSAGE',
			'ENTITY_TYPE',
			'SETUP',
			'OFFER_ID',
			'GIFT_ID',
			'PROMO_ID',
		),
		'CONTEXT_MENU_EXCEL' => 'Y',
		'ROW_ACTIONS' => array(
			'DELETE' => array(
				'ICON' => 'delete',
				'TEXT' => Loc::getMessage('LIGACOM_FEED_ADMIN_LOG_ROW_ACTION_DELETE'),
				'CONFIRM' => 'Y',
				'CONFIRM_MESSAGE' => Loc::getMessage('LIGACOM_FEED_ADMIN_LOG_ROW_ACTION_DELETE_CONFIRM')
			)
		),
		'GROUP_ACTIONS' => array(
			'delete' => Loc::getMessage('LIGACOM_FEED_ADMIN_LOG_ROW_ACTION_DELETE')
		),
		'ALLOW_BATCH' => 'Y'
	));
}

if ($isPopup)
{
	require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_popup_admin.php';
}
else
{
	require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
}