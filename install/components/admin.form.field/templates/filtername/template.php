<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

use Bitrix\Main\Localization\Loc;
use Ligacom\Feed;

$lang = [
 	'TOGGLE' => Loc::getMessage('LIGACOM_FEED_T_ADMIN_FORM_FIELD_FILTER_NAME_TOGGLE')
];
$langStatic = [
	'PLACEHOLDER' => Loc::getMessage('LIGACOM_FEED_T_ADMIN_FORM_FIELD_FILTER_NAME_PLACEHOLDER')
];

$this->addExternalCss('/bitrix/css/ligacom.feed/ui/input/hiddeninput.css');
$this->addExternalJs('/bitrix/js/ligacom.feed/ui/input/hiddeninput.js');

?>
<span class="b-hidden-input b-heading level--4 js-plugin" data-plugin="Ui.Input.HiddenInput" data-lang-prefix="LIGACOM_FEED_FILTER_NAME_">
	<span class="b-hidden-input__control">
		<input class="adm-input js-hidden-input__input <?= $arParams['CHILD_CLASS_NAME']; ?>" placeholder="<?= $langStatic['PLACEHOLDER']; ?>" data-name="NAME" <?
			if (!$arParams['PLACEHOLDER'])
			{
				echo ' name="' . $arParams['INPUT_NAME'] . '"';
				echo ' value="' . $arParams['VALUE'] . '"';
			}
		?> />
	</span>
	<label class="b-hidden-input__toggle b-link action--heading target--inside js-hidden-input__label" tabindex="0">
		<?= $arParams['HAS_VALUE'] ? $arParams['VALUE'] : $lang['TOGGLE']; ?>
	</label>
</span>
<script>
	(function() {
		var utils = BX.namespace('LigacomFeed.Utils');

		utils.registerLang(
			<?= Ligacom\Feed\Utils::jsonEncode($lang, JSON_UNESCAPED_UNICODE); ?>,
			'LIGACOM_FEED_FILTER_NAME_'
		);
	})();
</script>