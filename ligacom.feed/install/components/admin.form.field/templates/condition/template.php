<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

use Bitrix\Main\Localization\Loc;
use Ligacom\Feed;

// assets

CJSCore::Init([ 'popup' ]);

$this->addExternalCss('/bitrix/js/ligacom.feed/lib/select2/select2.css');
$this->addExternalCss('/bitrix/js/ligacom.feed/lib/select2/select2.theme.css');
$this->addExternalJs('/bitrix/js/ligacom.feed/lib/select2/select2.js');
$this->addExternalJs('/bitrix/js/ligacom.feed/lib/editdialog.js');
$this->addExternalJs('/bitrix/js/ligacom.feed/ui/input/taginput.js');
$this->addExternalJs('/bitrix/js/ligacom.feed/field/condition/summary.js');
$this->addExternalJs('/bitrix/js/ligacom.feed/field/condition/collection.js');
$this->addExternalJs('/bitrix/js/ligacom.feed/field/condition/item.js');

$lang = [
	'MODAL_TITLE' => Loc::getMessage('LIGACOM_FEED_T_ADMIN_FIELD_CONDITION_MODAL_TITLE'),
	'PLACEHOLDER' => Loc::getMessage('LIGACOM_FEED_T_ADMIN_FIELD_CONDITION_SUMMARY_PLACEHOLDER'),
	'JUNCTION' => Loc::getMessage('LIGACOM_FEED_T_ADMIN_FIELD_CONDITION_SUMMARY_JUNCTION'),
	'COUNT' => Loc::getMessage('LIGACOM_FEED_T_ADMIN_FIELD_CONDITION_COUNT'),
	'COUNT_PROGRESS' => Loc::getMessage('LIGACOM_FEED_T_ADMIN_FIELD_CONDITION_COUNT_PROGRESS'),
	'COUNT_FAIL' => Loc::getMessage('LIGACOM_FEED_T_ADMIN_FIELD_CONDITION_COUNT_FAIL'),
	'COUNT_EMPTY' => Loc::getMessage('LIGACOM_FEED_T_ADMIN_FIELD_CONDITION_COUNT_EMPTY'),
	'PRODUCT_1' => Loc::getMessage('LIGACOM_FEED_T_ADMIN_FIELD_CONDITION_PRODUCT_1'),
	'PRODUCT_2' => Loc::getMessage('LIGACOM_FEED_T_ADMIN_FIELD_CONDITION_PRODUCT_2'),
	'PRODUCT_5' => Loc::getMessage('LIGACOM_FEED_T_ADMIN_FIELD_CONDITION_PRODUCT_5'),
];
$langStatic = [
	'EDIT_BUTTON' => Loc::getMessage('LIGACOM_FEED_T_ADMIN_FIELD_CONDITION_SUMMARY_EDIT'),
	'FIELD_FIELD' => Loc::getMessage('LIGACOM_FEED_T_ADMIN_FIELD_CONDITION_FIELD_FIELD'),
	'PLACEHOLDER_FIELD' => Loc::getMessage('LIGACOM_FEED_T_ADMIN_FIELD_CONDITION_PLACEHOLDER_FIELD'),
	'FIELD_COMPARE' => Loc::getMessage('LIGACOM_FEED_T_ADMIN_FIELD_CONDITION_FIELD_COMPARE'),
	'PLACEHOLDER_COMPARE' => Loc::getMessage('LIGACOM_FEED_T_ADMIN_FIELD_CONDITION_PLACEHOLDER_COMPARE'),
	'FIELD_VALUE' => Loc::getMessage('LIGACOM_FEED_T_ADMIN_FIELD_CONDITION_FIELD_VALUE'),
	'PLACEHOLDER_VALUE' => Loc::getMessage('LIGACOM_FEED_T_ADMIN_FIELD_CONDITION_PLACEHOLDER_VALUE'),
	'NO_VALUE' => Loc::getMessage('LIGACOM_FEED_T_ADMIN_FIELD_CONDITION_NO_VALUE'),
	'LOAD_PROGRESS' => Loc::getMessage('LIGACOM_FEED_T_ADMIN_FIELD_CONDITION_LOAD_PROGRESS'),
	'LOAD_ERROR' => Loc::getMessage('LIGACOM_FEED_T_ADMIN_FIELD_CONDITION_LOAD_ERROR'),
	'SEARCHING' => Loc::getMessage('LIGACOM_FEED_T_ADMIN_FIELD_CONDITION_SEARCHING'),
	'VALUE_TOO_LONG' => Loc::getMessage('LIGACOM_FEED_T_ADMIN_FIELD_CONDITION_VALUE_TOO_LONG'),
	'VALUE_TOO_SHORT' => Loc::getMessage('LIGACOM_FEED_T_ADMIN_FIELD_CONDITION_VALUE_TOO_SHORT'),
	'VALUE_MAX_SELECT' => Loc::getMessage('LIGACOM_FEED_T_ADMIN_FIELD_CONDITION_VALUE_MAX_SELECT'),
	'ADD_BUTTON' => Loc::getMessage('LIGACOM_FEED_T_ADMIN_FIELD_CONDITION_ADD_BUTTON'),
];
$langChosen = [
	'CHOSEN_PLACEHOLDER' => $langStatic['PLACEHOLDER_VALUE'],
	'CHOSEN_NO_RESULTS' => $langStatic['NO_VALUE'],
	'CHOSEN_LOAD_PROGRESS' => $langStatic['LOAD_PROGRESS'],
	'CHOSEN_LOAD_ERROR' => $langStatic['LOAD_ERROR'],
	'CHOSEN_SEARCHING' => $langStatic['SEARCHING'],
	'CHOSEN_TOO_LONG' => $langStatic['VALUE_TOO_LONG'],
	'CHOSEN_TOO_SHORT' => $langStatic['VALUE_TOO_SHORT'],
	'CHOSEN_MAX_SELECT' => $langStatic['VALUE_MAX_SELECT'],
];

// output

?>
<div class="<?= $arParams['CHILD'] ? $arParams['CHILD_CLASS_NAME'] : 'js-plugin'; ?>" data-plugin="Field.Condition.Summary" <?= $arParams['CHILD'] ? '' : 'data-base-name="' . $arParams['INPUT_NAME'] .'"';  ?> data-name="FILTER_CONDITION">
	<?
	include __DIR__ . '/partials/summary.php';
	?>
	<div class="is--hidden js-condition-summary__edit-modal">
		<?
		include __DIR__ . '/partials/table.php';
		?>
	</div>
</div>
<?
if ($APPLICATION->GetPageProperty('LIGACOM_FEED_FORM_FIELD_CONDITION_LANG') !== 'Y')
{
	$APPLICATION->SetPageProperty('LIGACOM_FEED_FORM_FIELD_CONDITION_LANG', 'Y');

	?>
	<script>
		(function() {
			var utils = BX.namespace('LigacomFeed.Utils');

			utils.registerLang(<?= Ligacom\Feed\Utils::jsonEncode($lang, JSON_UNESCAPED_UNICODE); ?>, 'LIGACOM_FEED_FIELD_CONDITION_'); // field lang
			utils.registerLang(<?= Ligacom\Feed\Utils::jsonEncode($langChosen, JSON_UNESCAPED_UNICODE); ?>); // chosen lang
		})();
	</script>
	<?
}