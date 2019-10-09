<?
/** @global CMain$APPLICATION */
use Bitrix\Main\Localization\Loc;

if ($USER->IsAdmin())
{
	Loc::loadMessages(__FILE__);

	return array(
		"parent_menu" => "global_menu_services",
		"section" => "ligacom_feed",
		"sort" => 1000,
		"text" => Loc::getMessage("LIGACOM_FEED_MENU_CONTROL"),
		"title" => Loc::getMessage("LIGACOM_FEED_MENU_TITLE"),
		"icon" => "sale_menu_icon_marketplace",
		"items_id" => "menu_yamarket",
		"items" => array(
			array(
				"text" => Loc::getMessage("LIGACOM_FEED_MENU_SETTINGS"),
				"title" => Loc::getMessage("LIGACOM_FEED_MENU_SETTINGS"),
				"url" => "ligacom_feed_setup_list.php?lang=".LANGUAGE_ID,
				"more_url" => array(
					"ligacom_feed_setup_list.php",
					"ligacom_feed_setup_edit.php",
					"ligacom_feed_setup_run.php",
					"ligacom_feed_migration.php"
				)
			),
			array(
				"text" => Loc::getMessage("LIGACOM_FEED_MENU_LOG"),
				"title" => Loc::getMessage("LIGACOM_FEED_MENU_LOG"),
				"url" => "ligacom_feed_log.php?lang=".LANGUAGE_ID,
				"more_url" => array()
			),

		)
	);
}
else
{
	return false;
}