<?php

namespace Ligacom\Feed\Ui\UserField;

class SetupType extends ReferenceType
{
	function GetAdminListViewHTML($arUserField, $arHtmlControl)
	{
		$result = parent::GetAdminListViewHTML($arUserField, $arHtmlControl);
		$setupId = !empty($arHtmlControl['VALUE']) ? (int)$arHtmlControl['VALUE'] : 0;

		if ($setupId > 0)
		{
			$result = '<a href="/bitrix/admin/ligacom_feed_setup_edit.php?lang=' . LANGUAGE_ID . '&id=' . $setupId . '">' . $result .  '</a>';
		}

		return $result;
	}
}