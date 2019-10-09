<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

use Bitrix\Main\Localization\Loc;

/** @var $itemInputName string */
/** @var $itemValue array */
/** @var $isItemPlaceholder boolean */
/** @var $lang array */
/** @var $langStatic array */

if ($arParams['ALLOW_NAME'])
{
	$APPLICATION->IncludeComponent('ligacom.feed:admin.form.field', 'filtername', [
		'INPUT_NAME' => $itemInputName . '[NAME]',
		'VALUE' => $itemValue['NAME'],
		'MULTIPLE' => 'N',
		'PLACEHOLDER' => $isItemPlaceholder ? 'Y' : 'N',
		'CHILD' => 'Y',
		'CHILD_CLASS_NAME' => 'js-filter-item__input'
	]);
}
?>
<input class="js-filter-item__input" type="hidden" data-name="ID" <?

	if (!$isItemPlaceholder)
	{
		echo ' name="' . $itemInputName . '[ID]"';
		echo ' value="' . $itemValue['ID'] . '"';
	}

?> />
<input class="js-filter-item__input js-filter-item__sort" type="hidden" data-name="SORT" <?

	if (!$isItemPlaceholder)
	{
		echo ' name="' . $itemInputName . '[SORT]"';
		echo ' value="' . $itemValue['SORT'] . '"';
	}

?> />
<div class="b-form-panel">
	<div class="b-form-panel__section fill--secondary">
		<?
		$APPLICATION->IncludeComponent('ligacom.feed:admin.form.field', 'condition', [
			'INPUT_NAME' => $itemInputName . '[FILTER_CONDITION]',
			'VALUE' => $itemValue['FILTER_CONDITION'],
			'MULTIPLE' => 'Y',
			'PLACEHOLDER' => $isItemPlaceholder ? 'Y' : 'N',
			'CHILD' => 'Y',
			'CHILD_CLASS_NAME'  => 'js-filter-item__child',
			'FILTER_BASE_NAME' => $arParams['FILTER_BASE_NAME'],
			'SOURCE_ENUM' => $arResult['SOURCE_ENUM'],
			'FIELD_ENUM' => $arResult['FIELD_ENUM'],
			'COMPARE_ENUM' => $arResult['COMPARE_ENUM'],
			'VALUE_ENUM' => $arResult['VALUE_ENUM'],
			'CONTEXT' => $arParams['CONTEXT']
		]);
		?>
	</div>
	<?
	if ($arParams['ALLOW_DELIVERY_OPTIONS'])
	{
		?>
		<div class="b-form-panel__section fill--primary">
			<div class="b-form-pill-group js-delivery-summary-group <? $APPLICATION->ShowProperty($itemInputName . '[DELIVERY]_IS_FILL_CLASS_NAME');?>">
				<?
				if ($arParams['ALLOW_DELIVERY_OPTIONS'])
				{
					$APPLICATION->IncludeComponent('ligacom.feed:admin.form.field', 'delivery', [
						'INPUT_NAME' => $itemInputName . '[DELIVERY]',
						'VALUE' => $itemValue['DELIVERY'],
						'MULTIPLE' => 'Y',
						'PLACEHOLDER' => $isItemPlaceholder ? 'Y' : 'N',
						'CHILD' => 'Y',
						'CHILD_CLASS_NAME'  => 'js-filter-item__child',
						'GROUP_OUTSIDE' => 'Y',
						'EDIT_BUTTON_TITLE' => $langStatic['DELIVERY_EDIT_BUTTON'],
					]);
				}
				?>
			</div>
		</div>
		<?
	}

	if ($arParams['MULTIPLE'])
	{
		?>
		<button class="b-close js-filter-collection__item-delete" type="button"></button>
		<?
	}
	?>
</div>
