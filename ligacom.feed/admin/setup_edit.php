<?php

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Ligacom\Feed;

define('BX_SESSION_ID_CHANGE', false);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin.php';

Loc::loadMessages(__FILE__);

if (!$USER->IsAdmin())
{
	$APPLICATION->AuthForm(Loc::getMessage('LIGACOM_FEED_SETUP_EDIT_ACCESS_DENIED'));
	return;
}
else if (!Main\Loader::includeModule('ligacom.feed'))
{
	\CAdminMessage::ShowMessage([
        'TYPE' => 'ERROR',
        'MESSAGE' => Loc::getMessage('LIGACOM_FEED_SETUP_EDIT_REQUIRE_MODULE')
    ]);
}
else
{
	Ligacom\Feed\Metrika::load();

	$APPLICATION->IncludeComponent('ligacom.feed:admin.form.edit', '', [
		'TITLE' => Feed\Config::getLang('SETUP_EDIT_TITLE_EDIT'),
		'TITLE_ADD' => Feed\Config::getLang('SETUP_EDIT_TITLE_ADD'),
		'BTN_SAVE' => Feed\Config::getLang('SETUP_EDIT_BTN_SAVE'),
		'FORM_ID'   => 'LIGACOM_FEED_ADMIN_SETUP_EDIT',
		'FORM_BEHAVIOR' => 'steps',
		'PRIMARY'   => !empty($_GET['id']) ? $_GET['id'] : null,
		'COPY' => isset($_GET['copy']) ? $_GET['copy'] === 'Y' : false,
		'LIST_URL'  => '/bitrix/admin/ligacom_feed_setup_list.php?lang=' . LANGUAGE_ID,
		'SAVE_URL'  => '/bitrix/admin/ligacom_feed_setup_run.php?lang=' . LANGUAGE_ID . '&id=#ID#',
		'PROVIDER_TYPE' => 'Setup',
		'MODEL_CLASS_NAME' => Feed\Export\Setup\Model::getClassName(),
		'CONTEXT_MENU' => [
			[
                'ICON' => 'btn_list',
	            'LINK' => '/bitrix/admin/ligacom_feed_setup_list.php',
	            'TEXT' => Feed\Config::getLang('SETUP_EDIT_CONTEXT_MENU_LIST')
            ]
        ],
		'TABS' => [
			[
				'name' => Feed\Config::getLang('SETUP_EDIT_TAB_COMMON'),
				'data' => [
					'METRIKA_GOAL' => 'select_infoblocks'
				],
				'fields' => [
					'NAME',
					'DOMAIN',
					'HTTPS',
					'FILE_NAME',
					'EXPORT_SERVICE',
					'EXPORT_FORMAT',
					'SHOP_DATA',
					'IBLOCK',
					'ENABLE_AUTO_DISCOUNTS',
					'AUTOUPDATE',
					'REFRESH_PERIOD',
				]
			],
			[
				'name' => Feed\Config::getLang('SETUP_EDIT_TAB_PARAM'),
				'layout' => 'setup-param',
				'data' => [
					'METRIKA_GOAL' => 'infoblock_matching'
				],
				'fields' => [
					'IBLOCK_LINK.PARAM'
				]
			],
			[
				'name' => Feed\Config::getLang('SETUP_EDIT_TAB_FILTER'),
				'layout' => 'setup-filter',
				'final' => true,
				'data' => [
					'METRIKA_GOAL' => 'delivery_options'
				],
				'fields' => [
					'DELIVERY',
					'SALES_NOTES',
					'IBLOCK_LINK.DELIVERY',
					'IBLOCK_LINK.SALES_NOTES',
					'IBLOCK_LINK.FILTER',
					'IBLOCK_LINK.EXPORT_ALL',
				]
			]
		]
	]);
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
