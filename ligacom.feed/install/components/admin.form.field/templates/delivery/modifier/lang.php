<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

use Bitrix\Main\Localization\Loc;

$this->IncludeLangFile('template.php');

$arResult['LANG'] = [
	'ORDER_BEFORE_LANG' => Loc::getMessage('LIGACOM_FEED_T_ADMIN_FIELD_DELIVERY_ORDER_BEFORE_LANG'),
	'HOUR_1' => Loc::getMessage('LIGACOM_FEED_T_ADMIN_FIELD_DELIVERY_HOUR_1'),
	'HOUR_2' => Loc::getMessage('LIGACOM_FEED_T_ADMIN_FIELD_DELIVERY_HOUR_2'),
	'HOUR_5' => Loc::getMessage('LIGACOM_FEED_T_ADMIN_FIELD_DELIVERY_HOUR_5'),
	'PERIOD_LANG' => Loc::getMessage('LIGACOM_FEED_T_ADMIN_FIELD_DELIVERY_PERIOD_LANG'),
	'PRICE_LANG' => Loc::getMessage('LIGACOM_FEED_T_ADMIN_FIELD_DELIVERY_PRICE_LANG'),
	'DAY_1' => Loc::getMessage('LIGACOM_FEED_T_ADMIN_FIELD_DELIVERY_DAY_1'),
	'DAY_2' => Loc::getMessage('LIGACOM_FEED_T_ADMIN_FIELD_DELIVERY_DAY_2'),
	'DAY_5' => Loc::getMessage('LIGACOM_FEED_T_ADMIN_FIELD_DELIVERY_DAY_5'),
	'PRICE_CURRENCY_1' => Loc::getMessage('LIGACOM_FEED_T_ADMIN_FIELD_DELIVERY_PRICE_CURRENCY_1'),
	'PRICE_CURRENCY_2' => Loc::getMessage('LIGACOM_FEED_T_ADMIN_FIELD_DELIVERY_PRICE_CURRENCY_2'),
	'PRICE_CURRENCY_5' => Loc::getMessage('LIGACOM_FEED_T_ADMIN_FIELD_DELIVERY_PRICE_CURRENCY_5'),
	'MODAL_TITLE' => Loc::getMessage('LIGACOM_FEED_T_ADMIN_FIELD_DELIVERY_MODAL_TITLE'),
	'ADD_LIMIT_REACHED' => Loc::getMessage('LIGACOM_FEED_T_ADMIN_FIELD_DELIVERY_ADD_LIMIT_REACHED'),
	'DELIVERY_TYPE_DELIVERY' => Loc::getMessage('LIGACOM_FEED_T_ADMIN_FIELD_DELIVERY_DELIVERY_TYPE_DELIVERY'),
	'DELIVERY_TYPE_PICKUP' => Loc::getMessage('LIGACOM_FEED_T_ADMIN_FIELD_DELIVERY_DELIVERY_TYPE_PICKUP'),
	'NAME' => Loc::getMessage('LIGACOM_FEED_T_ADMIN_FIELD_DELIVERY_NAME'),
];