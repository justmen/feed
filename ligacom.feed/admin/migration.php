<?php


use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Ligacom\Feed;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin.php';

Loc::loadMessages(__FILE__);

if (!$USER->IsAdmin())
{
	$APPLICATION->AuthForm(Loc::getMessage('LIGACOM_FEED_ADMIN_MIGRATION_REQUIRE_MODULE'));
	return;
}
else if (!Main\Loader::includeModule('ligacom.feed'))
{
	\CAdminMessage::ShowMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => Loc::getMessage('LIGACOM_FEED_ADMIN_MIGRATION_REQUIRE_MODULE')
	]);

	return;
}

$APPLICATION->SetTitle(Feed\Config::getLang('ADMIN_MIGRATION_TITLE'));

$request = Main\Context::getCurrent()->getRequest();

if ($request->getPost('run') === 'Y' && check_bitrix_sessid())
{
	try
	{
		Feed\Migration\Controller::reset();

		CAdminMessage::ShowMessage(array(
			'TYPE' => 'OK',
			'MESSAGE' => Feed\Config::getLang('ADMIN_MIGRATION_READY')
		));
	}
	catch (Main\SystemException $exception)
	{
		CAdminMessage::ShowMessage(array(
			'TYPE' => 'ERROR',
			'MESSAGE' => $exception->getMessage()
		));
	}
}
else
{
	?>
	<form method="post">
		<?= bitrix_sessid_post(); ?>
		<button class="adm-btn" type="submit" name="run" value="Y"><?= Feed\Config::getLang('ADMIN_MIGRATION_RUN'); ?></button>
	</form>
	<?
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';