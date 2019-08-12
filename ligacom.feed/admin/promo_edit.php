<?php

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Ligacom\Feed;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin.php';

Loc::loadMessages(__FILE__);

if (!$USER->IsAdmin())
{
	$APPLICATION->AuthForm(Loc::getMessage('LIGACOM_FEED_PROMO_EDIT_ACCESS_DENIED'));
	return;
}
else if (!Main\Loader::includeModule('ligacom.feed'))
{
	\CAdminMessage::ShowMessage([
        'TYPE' => 'ERROR',
        'MESSAGE' => Loc::getMessage('LIGACOM_FEED_PROMO_EDIT_REQUIRE_MODULE')
    ]);
}
else
{
	Feed\Metrika::load();

	$id = null;
	$hasId = false;
	$contextMenu = [
        [
            'ICON' => 'btn_list',
            'LINK' => '/bitrix/admin/ligacom_feed_promo_list.php?lang=' . LANGUAGE_ID,
            'TEXT' => Feed\Config::getLang('PROMO_EDIT_CONTEXT_MENU_LIST')
        ]
    ];

	if (!empty($_GET['id']))
    {
        $id = (int)$_GET['id'];
        $hasId = true;

        $contextMenu[] = [
            'LINK' => '/bitrix/admin/ligacom_feed_promo_result.php?lang=' . LANGUAGE_ID . '&find_element_id=' . $id . '&set_filter=Y',
            'TEXT' => Feed\Config::getLang('PROMO_EDIT_CONTEXT_MENU_EXPORT_RESULT')
        ];

        $contextMenu[] = [
            'LINK' => '/bitrix/admin/ligacom_feed_log.php?lang=' . LANGUAGE_ID . '&find_promo_id=' . $id . '&set_filter=Y',
            'TEXT' => Feed\Config::getLang('PROMO_EDIT_CONTEXT_MENU_LOG')
        ];
    }

	$APPLICATION->IncludeComponent('ligacom.feed:admin.form.edit', '', [
		'TITLE' => Feed\Config::getLang('PROMO_EDIT_TITLE_EDIT'),
		'TITLE_ADD' => Feed\Config::getLang('PROMO_EDIT_TITLE_ADD'),
		'BTN_SAVE' => Feed\Config::getLang('PROMO_EDIT_BTN_SAVE'),
		'FORM_ID'   => 'LIGACOM_FEED_ADMIN_PROMO_EDIT',
		'FORM_BEHAVIOR' => 'steps',
		'PRIMARY' => $id,
		'COPY' => isset($_GET['copy']) ? $_GET['copy'] === 'Y' : false,
		'LIST_URL' => '/bitrix/admin/ligacom_feed_promo_list.php?lang=' . LANGUAGE_ID,
        'SAVE_URL' => '/bitrix/admin/ligacom_feed_promo_run.php?lang=' . LANGUAGE_ID . '&id=#ID#',
		'PROVIDER_TYPE' => 'Promo',
		'MODEL_CLASS_NAME' => Feed\Export\Promo\Model::getClassName(),
		'CONTEXT_MENU' => $contextMenu,
		'TABS' => [
			[
				'name' => Feed\Config::getLang('PROMO_EDIT_TAB_COMMON'),
				'fields' => [
					'ACTIVE',
					'NAME',
					'PROMO_TYPE',
					'PROMO_GIFT_IBLOCK_ID',
					'URL',
					'SETUP_EXPORT_ALL',
					'SETUP',
					'DESCRIPTION'
				],
				'data' => [
					'NOTE' => Feed\Config::getLang('PROMO_EDIT_DOCUMENTATION_TITLE'),
					'NOTE_DESCRIPTION' => Feed\Config::getLang('PROMO_EDIT_DOCUMENTATION_DESCRIPTION'),
				]
			],
			[
				'name' => Feed\Config::getLang('PROMO_EDIT_TAB_RULE'),
                'layout' => 'promo-discount',
				'fields' => [
					'EXTERNAL_ID',
					'START_DATE',
					'FINISH_DATE',
					'DISCOUNT_UNIT',
					'DISCOUNT_CURRENCY',
					'DISCOUNT_VALUE',
					'PROMO_CODE',
					'GIFT_REQUIRED_QUANTITY',
					'GIFT_FREE_QUANTITY',
					'PROMO_PRODUCT',
					'PROMO_GIFT'
				]
			],
		]
	]);
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
