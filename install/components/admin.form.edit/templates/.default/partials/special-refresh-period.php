<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

use Ligacom\Feed;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

/** @var $component Ligacom\Feed\Components\AdminFormEdit */
/** @var $specialFields array */

foreach ($specialFields as $specialFieldKey)
{
	$field = $component->getField($specialFieldKey);

	if ($field)
	{
		?>
		<tr>
			<td width="40%" align="right" valign="top">
				<?
				include __DIR__ . '/field-title.php';
				?>
			</td>
			<td width="60%">
				<?
				echo $component->getFieldHtml($field);

				if ($field['EDIT_IN_LIST'] === 'N')
				{
					?>
					<input type="hidden" name="<?= $field['FIELD_NAME'] ?>" value="" />
					<div class="b-admin-message-list">
						<?
						\CAdminMessage::ShowMessage([
							'TYPE' => 'ERROR',
							'MESSAGE' => Main\Localization\Loc::getMessage('LIGACOM_FEED_T_ADMIN_FORM_EDIT_SPECIAL_REFRESH_PERIOD_DISABLED_WARNING'),
							'DETAILS' => Main\Localization\Loc::getMessage('LIGACOM_FEED_T_ADMIN_FORM_EDIT_SPECIAL_REFRESH_PERIOD_DISABLED_WARNING_DETAILS'),
							'HTML' => true
						]);
						?>
					</div>
					<?
				}
				?>
			</td>
		</tr>
		<?
	}
}