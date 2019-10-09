<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

use Ligacom\Feed;
use Bitrix\Main;

/** @var $component Ligacom\Feed\Components\AdminFormEdit */
/** @var $specialFields array */

Main\Localization\Loc::loadMessages(__FILE__);

foreach ($specialFields as $specialFieldKey)
{
	$field = $component->getField($specialFieldKey);

	if ($field)
	{
		$fieldTitle = $component->getFieldTitle($field);
		$fieldValue = $component->getFieldValue($field);
		$shopParams = [
			'TITLE',
			'COMPANY'
		];

		foreach ($shopParams as $paramName)
		{
			$paramTitle = Main\Localization\Loc::getMessage('LIGACOM_FEED_T_ADMIN_FORM_EDIT_SPECIAL_SHOP_DATA_' . $paramName);
			$paramHelp = (string)Main\Localization\Loc::getMessage('LIGACOM_FEED_T_ADMIN_FORM_EDIT_SPECIAL_SHOP_DATA_' . $paramName . '_HELP');
			$paramValue = isset($fieldValue[$paramName]) ? $fieldValue[$paramName] : null;
			$paramAttributes = '';

			if ($paramName === 'NAME')
			{
				$paramAttributes .= ' maxlength="20"';
			}

			?>
			<tr>
				<td width="40%" align="right" valign="top">
					<?
					if (strlen($paramHelp) > 0)
					{
						?><span class="b-icon icon--question indent--right b-tag-tooltip--holder">
							<span class="b-tag-tooltip--content"><?= $paramHelp; ?></span>
						</span><?
					}

					echo $paramTitle;
					?>
				</td>
				<td width="60%">
					<input type="text" name="<?= $field['FIELD_NAME'] . '[' . $paramName . ']'; ?>" value="<?= htmlspecialcharsbx($paramValue) ?>" <?= $paramAttributes; ?> />
				</td>
			</tr>
			<?
		}
	}
}
