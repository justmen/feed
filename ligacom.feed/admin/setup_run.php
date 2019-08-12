<?php

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Ligacom\Feed;

define('BX_SESSION_ID_CHANGE', false);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

Loc::loadMessages(__FILE__);

if (!$USER->IsAdmin())
{
	$APPLICATION->AuthForm(Loc::getMessage('LIGACOM_FEED_ACCESS_DENIED'));

	return;
}
else if (!Main\Loader::includeModule('ligacom.feed'))
{
	\CAdminMessage::ShowMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => Loc::getMessage('LIGACOM_FEED_MODULE_NOT_INSTALLED')
	]);

	return;
}

$request = Main\Context::getCurrent()->getRequest();
$actionMessage = '';

// action process

$requestAction = $request->get('action');

if ($requestAction)
{
	$response = [
		'status' => 'error',
		'message' => null
	];

	try
	{
		if (!check_bitrix_sessid())
		{
			throw new Main\SystemException(Feed\Config::getLang('ADMIN_SETUP_RUN_ACTION_SESSION_EXPIRED'));
		}

		session_write_close(); // release session

		/** @var Feed\Export\Setup\Model $setup */
		$setupId = (int)$request->getPost('SETUP_ID');
		$setup = Feed\Export\Setup\Model::loadById($setupId);
		$initTimestamp = $request->getPost('INIT_TIME');
		$initTime = (
			$initTimestamp !== null
				? Main\Type\DateTime::createFromTimestamp($initTimestamp)
				: new Main\Type\DateTime()
		);
		$timeLimit = (int)$request->getPost('TIME_LIMIT') ?: 30;
		$timeSleep = (int)$request->getPost('TIME_SLEEP') ?: 3;

		$processor = new Feed\Export\Run\Processor($setup, [
			'step' => $request->getPost('STEP'),
			'stepOffset' => $request->getPost('STEP_OFFSET'),
			'progressCount' => true,
			'timeLimit' => $timeLimit,
			'initTime' => $initTime,
			'usePublic' => false
		]);

		switch ($requestAction)
		{
			case 'run':

				Feed\Export\Run\Admin::progress($setupId);

				if ($request->getPost('STEP') === null) // is first request
				{
					if ($setup->hasFullRefresh())
					{
						$setup->handleRefresh(false);
					}

					Feed\Export\Run\Manager::releaseChanges($setupId, $initTime);

					Feed\Export\Run\Admin::setTimeLimit($timeLimit);
					Feed\Export\Run\Admin::setTimeSleep($timeSleep);
				}

				$processResult = $processor->run();

				if ($processResult->isFinished())
				{
					Feed\Export\Run\Admin::release($setupId);

					if ($setup->hasFullRefresh())
					{
						$setup->handleRefresh(true);
					}

					if ($setup->isAutoUpdate())
					{
						$setup->handleChanges(true);
					}

					$adminMessage = new CAdminMessage(array(
						'MESSAGE' => Feed\Config::getLang('ADMIN_SETUP_RUN_ACTION_RUN_SUCCESS_TITLE'),
						'DETAILS' => Feed\Config::getLang('ADMIN_SETUP_RUN_ACTION_RUN_SUCCESS_DETAILS', [
							'#URL#' => $setup->getFileRelativePath()
						]),
						'TYPE' => 'OK',
						'HTML' => true
					));

					$response['status'] = 'ok';
					$response['message'] = $adminMessage->Show();

					$response['message'] .= '<div class="b-admin-text-message">';
					$response['message'] .= '<input type="text" value="' . htmlspecialcharsbx($setup->getFileUrl()) . '" size="50" /> ';
					$response['message'] .= '<button class="adm-btn js-plugin-click" type="button" data-plugin="Ui.Input.CopyClipboard">' . Feed\Config::getLang('ADMIN_SETUP_RUN_ACTION_RUN_COPY_LINK') . '</button>';
					$response['message'] .= '</div>';

					// log

					$queryLog = Ligacom\Feed\Logger\Table::getList([
						'filter' => [
							'=ENTITY_TYPE' => [
								Ligacom\Feed\Logger\Table::ENTITY_TYPE_EXPORT_RUN_ROOT,
								Ligacom\Feed\Logger\Table::ENTITY_TYPE_EXPORT_RUN_OFFER,
								Ligacom\Feed\Logger\Table::ENTITY_TYPE_EXPORT_RUN_CATEGORY,
								Ligacom\Feed\Logger\Table::ENTITY_TYPE_EXPORT_RUN_CURRENCY,
							],
							'=ENTITY_PARENT' => $setupId
						],
						'select' => [ 'ENTITY_PARENT' ],
						'limit' => 1,
					]);

					if ($queryLog->fetch())
					{
						$logUrl = 'ligacom_feed_log.php?' . http_build_query([
							'lang' => LANGUAGE_ID,
							'set_filter' => 'Y',
							'find_setup' => $setupId
						]);

						$response['message'] .=
							PHP_EOL
							. '<div class="b-admin-text-message">'
							. Feed\Config::getLang('ADMIN_SETUP_RUN_ACTION_RUN_SUCCESS_LOG', [
								'#URL#' => htmlspecialcharsbx($logUrl)
							])
							. '</div>';
					}

					// publish note

					$response['message'] .= BeginNote('style="position: relative; top: -15px;"');
					$response['message'] .= Feed\Config::getLang('ADMIN_SETUP_RUN_ACTION_RUN_SUCCESS_PUBLISH');
					$response['message'] .= EndNote();
				}
				else if ($processResult->isSuccess())
				{
					$processStepName = $processResult->getStep();
					$readyCountMessage = '';
					$stepList = Feed\Export\Run\Manager::getSteps();
					$isFoundCurrentStep = false;

					foreach ($stepList as $stepName)
					{
						$isCurrentStep = ($stepName === $processStepName || ($processStepName === null && !$isFoundCurrentStep));
						$stepText = null;
						$stepTitle = Feed\Config::getLang('ADMIN_SETUP_RUN_ACTION_RUN_PROGRESS_STEP', [
							'#STEP#' => Feed\Export\Run\Manager::getStepTitle($stepName)
						]);

						if ($isCurrentStep)
						{
							$isFoundCurrentStep = true;
							$readyCount = $processResult->getStepReadyCount();
							$stepText = $stepTitle;

							if ($readyCount !== null)
							{
								$stepText .= Feed\Config::getLang('ADMIN_SETUP_RUN_ACTION_RUN_PROGRESS_READY_COUNT', [
									'#COUNT#' => (int)$readyCount,
									'#LABEL#' => Ligacom\Feed\Utils::sklon($readyCount, [
										Feed\Config::getLang('ADMIN_SETUP_RUN_ACTION_RUN_PROGRESS_READY_COUNT_LABEL_1'),
										Feed\Config::getLang('ADMIN_SETUP_RUN_ACTION_RUN_PROGRESS_READY_COUNT_LABEL_2'),
										Feed\Config::getLang('ADMIN_SETUP_RUN_ACTION_RUN_PROGRESS_READY_COUNT_LABEL_5'),
									])
								]);
							}
							else
							{
								$stepText .= '...';
							}
						}
						else if (!$isFoundCurrentStep) // is ready
						{
							$stepText = '<b>' . $stepTitle . '</b>';
						}

						if ($stepText !== null)
						{
							$readyCountMessage .= '<p>' . $stepText . '</p>';
						}
					}

					$adminMessage = new CAdminMessage(array(
						'TYPE' => 'PROGRESS',
						'MESSAGE' => Feed\Config::getLang('ADMIN_SETUP_RUN_ACTION_RUN_PROGRESS_TITLE'),
						'DETAILS' => $readyCountMessage,
						'HTML' => true,
					));

					$response['status'] = 'progress';
					$response['message'] = $adminMessage->Show();
					$response['state'] = [
						'STEP' => $processResult->getStep(),
						'STEP_OFFSET' => $processResult->getStepOffset(),
						'sessid' => bitrix_sessid(),
						'INIT_TIME' => $initTime->getTimestamp()
					];
				}
				else
				{
					Feed\Export\Run\Admin::release($setupId);

					$errorMessage = $processResult->hasErrors()
						? implode('<br />', $processResult->getErrorMessages())
						: Feed\Config::getLang('ADMIN_SETUP_RUN_ACTION_RUN_ERROR_UNDEFINED');

					$adminMessage = new CAdminMessage(array(
						'TYPE' => 'ERROR',
						'MESSAGE' => Feed\Config::getLang('ADMIN_SETUP_RUN_ACTION_RUN_ERROR_TITLE'),
						'DETAILS' => $errorMessage,
						'HTML' => true,
					));
					
					$response['status'] = 'error';
					$response['message'] = $adminMessage->Show();
				}

			break;

			case 'stop':

				Feed\Export\Run\Admin::release($setupId);

				$processor->clear(true);

				if ($setup->hasFullRefresh())
				{
					$setup->handleRefresh(false);
				}

				if ($setup->isAutoUpdate())
				{
					$setup->handleChanges(false);
				}

				$response['status'] = 'ok';

			break;

			default:
				throw new Main\SystemException(
					Feed\Config::getLang('ADMIN_SETUP_RUN_ACTION_NOT_FOUND')
				);
			break;
		}
	}
	catch (Main\SystemException $exception)
	{
		$adminMessage = new CAdminMessage(array(
			'TYPE' => 'ERROR',
			'MESSAGE' => $exception->getMessage()
		));

		$response['status'] = 'error';
		$response['message'] = $adminMessage->Show();

		if (Ligacom\Feed\Migration\Controller::canRestore($exception))
		{
			$response['message'] .=
				'<a class="adm-btn" href="ligacom_feed_migration.php?lang=' . LANGUAGE_ID . '">'
				. Feed\Config::getLang('ADMIN_SETUP_RUN_GO_MIGRATION')
				. '</a>'
				. '<br /><br />';
		}
	}

	if ($request->isAjaxRequest())
	{
		$APPLICATION->RestartBuffer();
		echo Ligacom\Feed\Utils::jsonEncode($response, JSON_UNESCAPED_UNICODE);
		die();
	}
	else
	{
		$actionMessage = $response['message'];
	}
}

// admin page

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

// load form data

$requestSetup = (int)$request->get('id');
$setupList = [];

$querySetup = Feed\Export\Setup\Table::getList([
	'select' => [ 'ID', 'NAME' ]
]);

while ($setup = $querySetup->fetch())
{
	$setupList[] = $setup;
}

if (empty($setupList))
{
	\CAdminMessage::ShowMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => Feed\Config::getLang('ADMIN_SETUP_RUN_SETUP_LIST_EMPTY')
	]);

	return;
}

// form display

$APPLICATION->SetTitle(Feed\Config::getLang('ADMIN_SETUP_RUN_TITLE'));

CJSCore::Init([ 'jquery' ]);

$APPLICATION->SetAdditionalCSS('/bitrix/css/ligacom.feed/base.css');

$APPLICATION->AddHeadScript('/bitrix/js/ligacom.feed/utils.js');
$APPLICATION->AddHeadScript('/bitrix/js/ligacom.feed/plugin/base.js');
$APPLICATION->AddHeadScript('/bitrix/js/ligacom.feed/plugin/manager.js');
$APPLICATION->AddHeadScript('/bitrix/js/ligacom.feed/ui/admin/exportform.js');
$APPLICATION->AddHeadScript('/bitrix/js/ligacom.feed/ui/input/copyclipboard.js');

Ligacom\Feed\Metrika::reachGoal('generate_YML');

$tabs = [
	[ 'DIV' => 'common', 'TAB' => Feed\Config::getLang('ADMIN_SETUP_RUN_TAB_COMMON') ]
];

$tabControl = new CAdminTabControl('LIGACOM_FEED_ADMIN_SETUP_RUN', $tabs, true, true);

?>
<form class="js-plugin" action="<?= $APPLICATION->GetCurPage() . '?lang=' . LANGUAGE_ID; ?>" method="post" data-plugin="Ui.Admin.ExportForm">
	<div class="js-export-form__message">
		<?= $actionMessage; ?>
	</div>
	<div class="b-admin-text-message is--hidden js-export-form__timer-holder">
		<?= Feed\Config::getLang('ADMIN_SETUP_RUN_TIMER_LABEL'); ?>:
		<span class="js-export-form__timer">00:00</span>
	</div>
	<?
	$tabControl->Begin();

	echo bitrix_sessid_post();

	// common tab

	$tabControl->BeginNextTab([ 'showTitle' => false ]);

	?>
	<tr>
		<td width="40%" align="right"><?= Feed\Config::getLang('ADMIN_SETUP_RUN_FIELD_SETUP_ID'); ?>:</td>
		<td width="60%">
			<select name="SETUP_ID">
				<?
				foreach ($setupList as $setup)
				{
					?>
					<option value="<?= $setup['ID']; ?>" <?= (int)$setup['ID'] === $requestSetup ? 'selected' : ''; ?>>[<?= $setup['ID']; ?>] <?= $setup['NAME']; ?></option>
					<?
				}
				?>
			</select>
		</td>
	</tr>
	<tr>
		<td width="40%" align="right">
			<span class="b-icon icon--question indent--right b-tag-tooltip--holder">
				<span class="b-tag-tooltip--content"><?= Feed\Config::getLang('ADMIN_SETUP_RUN_FIELD_TIME_LIMIT_HELP'); ?></span>
			</span><?
			echo Feed\Config::getLang('ADMIN_SETUP_RUN_FIELD_TIME_LIMIT') . ':';
			?>
		</td>
		<td>
			<input type="text" name="TIME_LIMIT" value="<?= Feed\Export\Run\Admin::getTimeLimit(); ?>" size="2" />
			<?= Feed\Config::getLang('ADMIN_SETUP_RUN_FIELD_TIME_LIMIT_UNIT'); ?>
			<?= Feed\Config::getLang('ADMIN_SETUP_RUN_FIELD_TIME_LIMIT_SLEEP'); ?>
			<input type="text" name="TIME_SLEEP" value="<?= Feed\Export\Run\Admin::getTimeSleep(); ?>" size="2" />
			<?= Feed\Config::getLang('ADMIN_SETUP_RUN_FIELD_TIME_LIMIT_UNIT'); ?>
		</td>
	</tr>
	<?

	// buttons

	$tabControl->Buttons();

	?>
	<input type="button" class="adm-btn adm-btn-save js-export-form__run-button" value="<?= Feed\Config::getLang('ADMIN_SETUP_RUN_BUTTON_START'); ?>" />
	<input type="button" class="adm-btn js-export-form__stop-button" value="<?= Feed\Config::getLang('ADMIN_SETUP_RUN_BUTTON_STOP'); ?>" disabled />
	<?

	$tabControl->End();
	?>
</form>
<?
$jsLang = [
	'LIGACOM_FEED_INPUT_COPY_CLIPBOARD_SUCCESS' => Feed\Config::getLang('ADMIN_SETUP_RUN_CLIPBOARD_SUCCESS'),
	'LIGACOM_FEED_INPUT_COPY_CLIPBOARD_FAIL' => Feed\Config::getLang('ADMIN_SETUP_RUN_CLIPBOARD_FAIL'),
	'LIGACOM_FEED_EXPORT_FORM_QUERY_ERROR_TITLE' => Feed\Config::getLang('ADMIN_SETUP_RUN_QUERY_ERROR_TITLE'),
	'LIGACOM_FEED_EXPORT_FORM_QUERY_ERROR_TEXT' => Feed\Config::getLang('ADMIN_SETUP_RUN_QUERY_ERROR_TEXT'),
];
?>
<script>
	BX.message(<?= Ligacom\Feed\Utils::jsonEncode($jsLang, JSON_UNESCAPED_UNICODE); ?>);
</script>
<?

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';