<?php

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Ligacom\Feed;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin.php';

Loc::loadMessages(__FILE__);

if (!$USER->IsAdmin())
{
	$APPLICATION->AuthForm(Loc::getMessage('LIGACOM_FEED_ADMIN_PROMO_LIST_ACCESS_DENIED'));
	return;
}
else if (!Main\Loader::includeModule('ligacom.feed'))
{
	\CAdminMessage::ShowMessage([
        'TYPE' => 'ERROR',
        'MESSAGE' => Loc::getMessage('LIGACOM_FEED_ADMIN_PROMO_LIST_REQUIRE_MODULE')
    ]);
}
else
{
	Feed\Metrika::load();

	$APPLICATION->IncludeComponent(
		'ligacom.feed:admin.grid.list',
		'',
		array(
			'GRID_ID' => 'LIGACOM_FEED_ADMIN_PROMO_LIST',
			'PROVIDER_TYPE' => 'Promo',
			'MODEL_CLASS_NAME' => Feed\Export\Promo\Model::getClassName(),
            'EDIT_URL' => '/bitrix/admin/ligacom_feed_promo_edit.php?lang=' . LANGUAGE_ID . '&id=#ID#',
            'ADD_URL' => '/bitrix/admin/ligacom_feed_promo_edit.php?lang=' . LANGUAGE_ID,
            'EXPORT_URL' => '/bitrix/admin/ligacom_feed_promo_run.php?lang=' . LANGUAGE_ID,
            'TITLE' => Loc::getMessage('LIGACOM_FEED_ADMIN_PROMO_LIST_PAGE_TITLE'),
            'NAV_TITLE' => Loc::getMessage('LIGACOM_FEED_ADMIN_PROMO_LIST_NAV_TITLE'),
			'LIST_FIELDS' => array(
				'ID',
				'NAME',
				'ACTIVE',
				'SETUP',
				'PROMO_TYPE',
				'URL',
				'START_DATE',
				'FINISH_DATE',
                'EXTERNAL_ID'
			),
			'DEFAULT_LIST_FIELDS' => [
				'ID',
				'NAME',
				'ACTIVE',
				'SETUP',
				'PROMO_TYPE'
			],
			'CONTEXT_MENU' => [
				[
					'TEXT' => Loc::getMessage('LIGACOM_FEED_ADMIN_PROMO_LIST_BUTTON_ADD'),
					'LINK' => 'ligacom_feed_promo_edit.php?lang=' . LANG,
					'ICON' => 'btn_new'
				]
			],
			'ROW_ACTIONS' => array(
                'RUN' => array(
                    'URL' => '/bitrix/admin/ligacom_feed_promo_run.php?lang=' . LANGUAGE_ID . '&id=#ID#',
                    'ICON' => 'unpack',
                    'TEXT' => Loc::getMessage('LIGACOM_FEED_ADMIN_PROMO_LIST_ROW_ACTION_RUN')
                ),
                'EXPORT_RESULT' => array(
                    'URL' => '/bitrix/admin/ligacom_feed_promo_result.php?lang=' . LANGUAGE_ID . '&find_element_id=#ID#&set_filter=Y',
                    'TEXT' => Loc::getMessage('LIGACOM_FEED_ADMIN_PROMO_LIST_ROW_ACTION_EXPORT_RESULT')
                ),
				'EDIT' => array(
					'URL' => '/bitrix/admin/ligacom_feed_promo_edit.php?lang=' . LANGUAGE_ID . '&id=#ID#',
					'ICON' => 'edit',
					'TEXT' => Loc::getMessage('LIGACOM_FEED_ADMIN_PROMO_LIST_ROW_ACTION_EDIT'),
					'DEFAULT' => true
				),
                'ACTIVATE' => array(
                    'ACTION' => 'activate',
                    'TEXT' => Loc::getMessage('LIGACOM_FEED_ADMIN_PROMO_LIST_ROW_ACTION_ACTIVATE')
                ),
                'DEACTIVATE' => array(
                    'ACTION' => 'deactivate',
                    'TEXT' => Loc::getMessage('LIGACOM_FEED_ADMIN_PROMO_LIST_ROW_ACTION_DEACTIVATE'),
                    'CONFIRM' => 'Y',
                    'CONFIRM_MESSAGE' => Loc::getMessage('LIGACOM_FEED_ADMIN_PROMO_LIST_ROW_ACTION_DEACTIVATE_CONFIRM')
                ),
                'COPY' => array(
                    'URL' => '/bitrix/admin/ligacom_feed_promo_edit.php?lang=' . LANGUAGE_ID . '&id=#ID#&copy=Y',
                    'ICON' => 'copy',
                    'TEXT' => Loc::getMessage('LIGACOM_FEED_ADMIN_PROMO_LIST_ROW_ACTION_COPY')
                ),
				'DELETE' => array(
					'ICON' => 'delete',
					'TEXT' => Loc::getMessage('LIGACOM_FEED_ADMIN_PROMO_LIST_ROW_ACTION_DELETE'),
					'CONFIRM' => 'Y',
					'CONFIRM_MESSAGE' => Loc::getMessage('LIGACOM_FEED_ADMIN_PROMO_LIST_ROW_ACTION_DELETE_CONFIRM')
				)
			),
			'GROUP_ACTIONS' => [
			    'activate' => Loc::getMessage('LIGACOM_FEED_ADMIN_PROMO_LIST_ROW_ACTION_ACTIVATE'),
			    'deactivate' => Loc::getMessage('LIGACOM_FEED_ADMIN_PROMO_LIST_ROW_ACTION_DEACTIVATE'),
				'delete' => Loc::getMessage('LIGACOM_FEED_ADMIN_PROMO_LIST_ROW_ACTION_DELETE')
			]
		)
	);
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';