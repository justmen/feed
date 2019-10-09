<?php

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Ligacom\Feed;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin.php';

Loc::loadMessages(__FILE__);

if (!$USER->IsAdmin())
{
	$APPLICATION->AuthForm(Loc::getMessage('LIGACOM_FEED_ADMIN_SETUP_LIST_ACCESS_DENIED'));
	return;
}
else if (!Main\Loader::includeModule('ligacom.feed'))
{
	\CAdminMessage::ShowMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => Loc::getMessage('LIGACOM_FEED_ADMIN_SETUP_LIST_REQUIRE_MODULE')
	]);
}
else
{

	$APPLICATION->IncludeComponent(
		'ligacom.feed:admin.grid.list',
		'',
		array(
			'GRID_ID' => 'LIGACOM_FEED_ADMIN_SETUP_LIST',
			'PROVIDER_TYPE' => 'Setup',
			'MODEL_CLASS_NAME' => Feed\Export\Setup\Model::getClassName(),
			'EDIT_URL' => '/bitrix/admin/ligacom_feed_setup_edit.php?lang=' . LANGUAGE_ID . '&id=#ID#',
			'ADD_URL' => '/bitrix/admin/ligacom_feed_setup_edit.php?lang=' . LANGUAGE_ID,
			'TITLE' => Loc::getMessage('LIGACOM_FEED_ADMIN_SETUP_LIST_PAGE_TITLE'),
			'NAV_TITLE' => Loc::getMessage('LIGACOM_FEED_ADMIN_SETUP_LIST_NAV_TITLE'),
			'LIST_FIELDS' => array(
				'ID',
				'NAME',
				'EXPORT_SERVICE',
				'EXPORT_FORMAT',
				'DOMAIN',
				'HTTPS',
				'IBLOCK',
				'FILE_NAME',
				'ENABLE_AUTO_DISCOUNTS',
				'AUTOUPDATE',
				'REFRESH_PERIOD'
			),
			'DEFAULT_LIST_FIELDS' => [
				'ID',
				'NAME',
				'EXPORT_SERVICE',
				'EXPORT_FORMAT',
				'DOMAIN',
				'HTTPS',
				'IBLOCK',
				'FILE_NAME',
			],
			'CONTEXT_MENU' => [
				[
					'TEXT' => Loc::getMessage('LIGACOM_FEED_ADMIN_SETUP_LIST_BUTTON_ADD'),
					'LINK' => 'ligacom_feed_setup_edit.php?lang=' . LANG,
					'ICON' => 'btn_new'
				]
			],
			'ROW_ACTIONS' => array(
				'RUN' => array(
					'URL' => '/bitrix/admin/ligacom_feed_setup_run.php?lang=' . LANGUAGE_ID . '&id=#ID#',
					'ICON' => 'unpack',
					'TEXT' => Loc::getMessage('LIGACOM_FEED_ADMIN_SETUP_LIST_ROW_ACTION_RUN')
				),
				'EDIT' => array(
					'URL' => '/bitrix/admin/ligacom_feed_setup_edit.php?lang=' . LANGUAGE_ID . '&id=#ID#',
					'ICON' => 'edit',
					'TEXT' => Loc::getMessage('LIGACOM_FEED_ADMIN_SETUP_LIST_ROW_ACTION_EDIT'),
					'DEFAULT' => true
				),
				'COPY' => array(
					'URL' => '/bitrix/admin/ligacom_feed_setup_edit.php?lang=' . LANGUAGE_ID . '&id=#ID#&copy=Y',
					'ICON' => 'copy',
					'TEXT' => Loc::getMessage('LIGACOM_FEED_ADMIN_SETUP_LIST_ROW_ACTION_COPY')
				),
				'DELETE' => array(
					'ICON' => 'delete',
					'TEXT' => Loc::getMessage('LIGACOM_FEED_ADMIN_SETUP_LIST_ROW_ACTION_DELETE'),
					'CONFIRM' => 'Y',
					'CONFIRM_MESSAGE' => Loc::getMessage('LIGACOM_FEED_ADMIN_SETUP_LIST_ROW_ACTION_DELETE_CONFIRM')
				)
			),
			'GROUP_ACTIONS' => [
				'delete' => Loc::getMessage('LIGACOM_FEED_ADMIN_SETUP_LIST_ROW_ACTION_DELETE')
			]
		)
	);
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';