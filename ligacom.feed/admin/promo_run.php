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
$isDeleteInactiveByDefault = true;

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
            throw new Main\SystemException(Feed\Config::getLang('ADMIN_PROMO_RUN_ACTION_SESSION_EXPIRED'));
        }

        switch ($requestAction)
        {
            case 'run':
                session_write_close(); // release session

                $promoIdList = $request->getPost('PROMO_LIST');
                $setupIdList = [];
                $isNeedAllSetup = false;
                $primaryPromoIdList = (array)$request->getPost('PROMO_ID');
                $isDeleteInactive = ($request->getPost('DELETE_INACTIVE') === 'Y');
                $initTimestamp = $request->getPost('INIT_TIME');
                $initTime = (
                    $initTimestamp !== null
                        ? Main\Type\DateTime::createFromTimestamp($initTimestamp)
                        : new Main\Type\DateTime()
                );

                if ($promoIdList !== null)
                {
                    $promoIdList = explode(',', $promoIdList);
                    $setupIdList = explode(',', $request->getPost('SETUP_LIST'));
                    $isNeedAllSetup = ($request->getPost('SETUP_ALL') === 'Y');
                }
                else
                {
                    $promoIdList = [];
                    $setupIdList = [];

                    // get promo setup link

                    Main\Type\Collection::normalizeArrayValuesByInt($primaryPromoIdList);

                    if (!empty($primaryPromoIdList))
                    {
                        $primaryPromoList = Feed\Export\Promo\Model::loadList([
                            'filter' => [
                                '=ID' => $primaryPromoIdList
                            ]
                        ]);

                        /** @var Feed\Export\Promo\Model $primaryModel */
                        foreach ($primaryPromoList as $primaryModel)
                        {
                            $promoIdList[$primaryModel->getId()] = true;

                            if ($primaryModel->isExportForAll())
                            {
                                $isNeedAllSetup = true;
                            }
                            else if (!$isNeedAllSetup)
                            {
                                $querySetupLink = Feed\Export\Promo\Internals\SetupLinkTable::getList([
                                    'filter' => [
                                        '=PROMO_ID' => $primaryModel->getId()
                                    ],
                                    'select' => [
                                        'SETUP_ID'
                                    ]
                                ]);

                                while ($setupLink = $querySetupLink->fetch())
                                {
                                    $setupIdList[(int)$setupLink['SETUP_ID']] = true;
                                }
                            }
                        }
                    }

                    // load inactive exported promo

                    if ($isDeleteInactiveByDefault || $isDeleteInactive)
                    {
                    	$nowTimestamp = time();
                    	$needCheckExternalPromoList = [];

                        $queryInactivePromoExported = Feed\Export\Run\Storage\PromoTable::getList([
                            'filter' => [
                                '=STATUS' => Feed\Export\Run\Steps\Promo::STORAGE_STATUS_SUCCESS
                            ],
                            'select' => [
                                'SETUP_ID',
                                'ELEMENT_ID',
								'PROMO_ACTIVE' => 'PROMO.ACTIVE',
								'PROMO_EXTERNAL_ID' => 'PROMO.EXTERNAL_ID',
								'PROMO_START_DATE' => 'PROMO.START_DATE',
								'PROMO_FINISH_DATE' => 'PROMO.FINISH_DATE',
                            ],
                            'runtime' => [
                                new Main\Entity\ReferenceField('PROMO', Feed\Export\Promo\Table::getClassName(), [
                                    '=this.ELEMENT_ID' => 'ref.ID'
                                ])
                            ]
                        ]);

                        while ($exportPromo = $queryInactivePromoExported->fetch())
                        {
                        	$exportPromoId = (int)$exportPromo['ELEMENT_ID'];
                        	$isExportPromoActive = ((string)$exportPromo['PROMO_ACTIVE'] === Feed\Export\Promo\Table::BOOLEAN_Y);
							$hasExportPromoExternalData = ((int)$exportPromo['PROMO_EXTERNAL_ID'] > 0);
                        	$isExportPromoInFuture = (
								$exportPromo['PROMO_START_DATE'] instanceof Main\Type\Date
								&& $exportPromo['PROMO_START_DATE']->getTimestamp() > $nowTimestamp
							);
                        	$isExportPromoInPast = (
								$exportPromo['PROMO_FINISH_DATE'] instanceof Main\Type\Date
								&& $exportPromo['PROMO_FINISH_DATE']->getTimestamp() <= $nowTimestamp
							);

                        	if ($isExportPromoActive && $hasExportPromoExternalData)
							{
								if (!isset($needCheckExternalPromoList[$exportPromoId]))
								{
									$needCheckExternalPromoList[$exportPromoId] = [];
								}

								$needCheckExternalPromoList[$exportPromoId][] = (int)$exportPromo['SETUP_ID'];
							}
                        	else if (!$isExportPromoActive || $isExportPromoInFuture || $isExportPromoInPast)
							{
								$setupIdList[(int)$exportPromo['SETUP_ID']] = true;
								$promoIdList[$exportPromoId] = true;
							}
                        }

						if (!empty($needCheckExternalPromoList))
						{
							$promoModelList = Feed\Export\Promo\Model::loadList([
								'filter' => [ '=ID' => array_keys($needCheckExternalPromoList) ]
							]);

							/** @var Feed\Export\Promo\Model $promoModel */
							foreach ($promoModelList as $promoModel)
							{
								if (!$promoModel->isActive() || !$promoModel->isActiveDate())
								{
									$promoId = $promoModel->getId();

									foreach ($needCheckExternalPromoList[$promoId] as $setupId)
									{
										$setupIdList[$setupId] = true;
										$promoIdList[$promoId] = true;
									}
								}
							}
						}
					}

                    $promoIdList = array_keys($promoIdList);
                    $setupIdList = array_keys($setupIdList);
                }

                if (empty($promoIdList))
                {
                	if ($isDeleteInactive)
					{
						$isAllReady = true;
					}
                	else
					{
						throw new Main\SystemException(Feed\Config::getLang('ADMIN_PROMO_RUN_ACTION_RUN_PROMO_NOT_FOUND'));
                	}
                }
                else if (!$isNeedAllSetup && empty($setupIdList))
                {
                    throw new Main\SystemException(Feed\Config::getLang('ADMIN_PROMO_RUN_ACTION_RUN_SETUP_NOT_FOUND'));
                }

                if (!$isAllReady)
				{
					$setupList = Feed\Export\Setup\Model::loadList([
						'filter' => $isNeedAllSetup ? [] : [ '=ID' => $setupIdList ]
					]);
					$setupCount = count($setupList);
					$setupOffset = (int)$request->getPost('SETUP_OFFSET');
					$setupIndex = 0;
					$isAllReady = true;
					$startTime = microtime(true);
					$progressMessage = '';

					/** @var Feed\Export\Setup\Model $setup */
					foreach ($setupList as $setup)
					{
						$setupDisplayName = '[' . $setup->getId() . '] ' . $setup->getField('NAME');

						if ($setupIndex < $setupOffset)
						{
							$progressMessage .= '<p><b>' . Feed\Config::getLang('ADMIN_PROMO_RUN_ACTION_RUN_PROGRESS_SETUP', [
								'#NAME#' => $setupDisplayName
							]) . '</b></p>';
						}
						else if (!$setup->isFileReady())
						{
							$setupOffset++;
						}
						else
						{
							$isTargetOffset = ($setupIndex === $setupOffset);

							$processor = new Feed\Export\Run\Processor($setup, [
								'step' => $isTargetOffset ? $request->getPost('STEP') : null,
								'stepOffset' => $isTargetOffset ? $request->getPost('STEP_OFFSET') : null,
								'timeLimit' => $request->getPost('TIME_LIMIT'),
								'initTime' => $initTime,
								'startTime' => $startTime,
								'usePublic' => true,
								'progressCount' => true,
								'changes' => [
									Feed\Export\Run\Manager::ENTITY_TYPE_PROMO => $promoIdList
								]
							]);

							$processResult = $processor->run('change');
							$isSetupFinished = $processResult->isFinished();

							if ($isSetupFinished)
							{
								$setupOffset++;
							}

							if ($processResult->isSuccess() && (!$isSetupFinished || $processor->isTimeExpired()))
							{
								$isAllReady = false;
								$processStepName = $processResult->getStep();

								$progressMessage .= '<p>';
								$progressMessage .= Feed\Config::getLang('ADMIN_PROMO_RUN_ACTION_RUN_PROGRESS_SETUP', [
									'#NAME#' => $setupDisplayName
								]);

								if ($processStepName !== null)
								{
									$progressMessage .= Feed\Config::getLang('ADMIN_PROMO_RUN_ACTION_RUN_PROGRESS_STEP', [
										'#STEP#' => Feed\Export\Run\Manager::getStepTitle($processStepName)
									]);
									$readyCount = $processResult->getStepReadyCount();

									if ($readyCount !== null)
									{
										$progressMessage .= Feed\Config::getLang('ADMIN_PROMO_RUN_ACTION_RUN_PROGRESS_READY_COUNT', [
											'#COUNT#' => (int)$readyCount,
											'#LABEL#' => Feed\Utils::sklon($readyCount, [
												Feed\Config::getLang('ADMIN_PROMO_RUN_ACTION_RUN_PROGRESS_READY_COUNT_LABEL_1'),
												Feed\Config::getLang('ADMIN_PROMO_RUN_ACTION_RUN_PROGRESS_READY_COUNT_LABEL_2'),
												Feed\Config::getLang('ADMIN_PROMO_RUN_ACTION_RUN_PROGRESS_READY_COUNT_LABEL_5'),
											])
										]);
									}
									else
									{
										$progressMessage .= '...';
									}
								}

								$progressMessage .= '</p>';

								$adminMessage = new CAdminMessage(array(
									'TYPE' => 'PROGRESS',
									'MESSAGE' => Feed\Config::getLang('ADMIN_PROMO_RUN_ACTION_RUN_PROGRESS_TITLE'),
									'DETAILS' => $progressMessage,
									'HTML' => true,
								));

								$response['status'] = 'progress';
								$response['message'] = $adminMessage->Show();
								$response['state'] = [
									'SETUP_LIST' => implode(',', $setupIdList),
									'SETUP_ALL' => $isNeedAllSetup ? 'Y' : 'N',
									'PROMO_LIST' => implode(',', $promoIdList),
									'SETUP_OFFSET' => $setupOffset,
									'sessid' => bitrix_sessid(),
									'INIT_TIME' => $initTime->getTimestamp()
								];

								if (!$isSetupFinished)
								{
									$response['state'] += [
										'STEP' => $processResult->getStep(),
										'STEP_OFFSET' => $processResult->getStepOffset(),
									];
								}

								break;
							}
							else if (!$isSetupFinished)
							{
								$isAllReady = false;
								$errorMessage = $processResult->hasErrors()
									? implode('<br />', $processResult->getErrorMessages())
									: Feed\Config::getLang('ADMIN_PROMO_RUN_ACTION_RUN_ERROR_UNDEFINED');

								$adminMessage = new CAdminMessage(array(
									'TYPE' => 'ERROR',
									'MESSAGE' => Feed\Config::getLang('ADMIN_PROMO_RUN_ACTION_RUN_ERROR_TITLE'),
									'DETAILS' => $errorMessage,
									'HTML' => true,
								));

								$response['status'] = 'error';
								$response['message'] = $adminMessage->Show();

								break;
							}
						}

						$setupIndex++;
					}
                }

                if ($isAllReady)
                {
                    // update listener

					if (!empty($promoIdList))
					{
						$promoCollection = Feed\Export\Promo\Model::loadList([
							'filter' => [
								'=ID' => $promoIdList
							]
						]);

						/** @var $promo Feed\Export\Promo\Model */
						foreach ($promoCollection as $promo)
						{
							$promo->updateListener();
						}
                    }

                    // show message

                    $adminResultUrl = 'ligacom_feed_promo_result.php?' . http_build_query([
                        'lang' => LANGUAGE_ID,
                        'set_filter' => 'Y',
                        'find_timestamp_x_from' => $initTime->toString()
                    ]);

                    $adminMessage = new CAdminMessage(array(
                        'MESSAGE' => Feed\Config::getLang('ADMIN_PROMO_RUN_ACTION_RUN_SUCCESS_TITLE'),
                        'TYPE' => 'OK',
                        'HTML' => true,
                        'DETAILS' => Feed\Config::getLang('ADMIN_PROMO_RUN_ACTION_RUN_SUCCESS_DETAILS', [
                            '#URL#' => $adminResultUrl
                        ])
                    ));

                    $response['status'] = 'ok';
                    $response['message'] = $adminMessage->Show();

                    // log

                    $queryLog = Feed\Logger\Table::getList([
                        'filter' => [
                            '=ENTITY_TYPE' => [
                                Feed\Logger\Table::ENTITY_TYPE_EXPORT_RUN_PROMO,
                                Feed\Logger\Table::ENTITY_TYPE_EXPORT_RUN_PROMO_GIFT,
                                Feed\Logger\Table::ENTITY_TYPE_EXPORT_RUN_PROMO_PRODUCT,
                                Feed\Logger\Table::ENTITY_TYPE_EXPORT_RUN_GIFT,
                            ],
                            '>=TIMESTAMP_X' => $initTime
                        ],
                        'select' => [ 'ENTITY_TYPE' ],
                        'limit' => 1,
                    ]);

                    if ($queryLog->fetch())
                    {
                        $logUrl = 'ligacom_feed_log.php?' . http_build_query([
                            'lang' => LANGUAGE_ID,
                            'set_filter' => 'Y',
                            'find_timestamp_x_from' => $initTime->toString()
                        ]);

                        $response['message'] .=
                            PHP_EOL
                            . '<div class="b-admin-text-message">'
                            . Feed\Config::getLang('ADMIN_PROMO_RUN_ACTION_RUN_SUCCESS_LOG', [
                                '#URL#' => htmlspecialcharsbx($logUrl)
                            ])
                            . '</div>';
                    }
                }
            break;

            case 'stop':
                $response['status'] = 'ok';
            break;

            default:
                throw new Main\SystemException(
                    Feed\Config::getLang('ADMIN_PROMO_RUN_ACTION_NOT_FOUND')
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

		if (Feed\Migration\Controller::canRestore($exception))
		{
			$response['message'] .=
				'<a class="adm-btn" href="ligacom_feed_migration.php?lang=' . LANGUAGE_ID . '">'
				. Feed\Config::getLang('ADMIN_PROMO_RUN_GO_MIGRATION')
				. '</a>'
				. '<br /><br />';
		}
    }

    if ($request->isAjaxRequest())
    {
        $APPLICATION->RestartBuffer();
        echo Feed\Utils::jsonEncode($response, JSON_UNESCAPED_UNICODE);
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

$requestPromoList = array_flip((array)$request->get('id'));
$requestDeleteInactive = ($request->get('deleteInactive') === 'Y');
$isShowPromoChoose = empty($requestPromoList);
$promoList = [];
$existPromoList = [];
$promoModelList = Feed\Export\Promo\Model::loadList();
$notifyGroupList = [
	'READY' => [],
	'INACTIVE' => [],
	'IN_FUTURE' => [],
	'IN_PAST' => [],
	'DELETE' => [],
];

/** @var Feed\Export\Promo\Model $promoModel */
foreach ($promoModelList as $promoModel)
{
	$promoId = $promoModel->getId();
	$isActive = $promoModel->isActive();
	$isActiveDate = $promoModel->isActiveDate();

	$existPromoList[$promoId] = true;

	if ($isActive && $isActiveDate)
	{
		$promoList[] = [
			'ID' => $promoId,
			'NAME' => $promoModel->getField('NAME')
		];
	}

	if (isset($requestPromoList[$promoId]))
	{
		$promoLangFields = [
			'#ID#' => $promoId,
			'#NAME#' => $promoModel->getField('NAME')
		];

		if (!$isActive)
		{
			$requestDeleteInactive = true;

			$notifyGroupList['INACTIVE'][$promoId] = $promoLangFields;
		}
		else if (!$isActiveDate)
		{
			$nextDate = $promoModel->getNextActiveDate();

			if ($nextDate)
			{
				$notifyGroupList['IN_FUTURE'][$promoId] = $promoLangFields + [
					'#DATE#' => $nextDate->toString()
				];
			}
			else
			{
				$requestDeleteInactive = true;
				$notifyGroupList['IN_PAST'][$promoId] = $promoLangFields;
			}
		}
		else
		{
			$isShowPromoChoose = true;
			$notifyGroupList['READY'][$promoId] = $promoLangFields;
		}
	}
}

foreach ($requestPromoList as $promoId => $dummy)
{
	if (!isset($existPromoList[$promoId]))
	{
		$requestDeleteInactive = true;

		$notifyGroupList['DELETE'][] = [
			'#ID#' => $promoId
		];
	}
}

if (!empty($notifyGroupList['INACTIVE']) || !empty($notifyGroupList['IN_PAST']))
{
	$inactivePromoList = array_merge(
		array_keys($notifyGroupList['INACTIVE']),
		array_keys($notifyGroupList['IN_PAST'])
	);

	$inactivePromoList = array_unique($inactivePromoList, SORT_NUMERIC);
	$existInactivePromoList = [];

	$queryInactivePromoExported = Feed\Export\Run\Storage\PromoTable::getList([
		'filter' => [
			'=STATUS' => Feed\Export\Run\Steps\Promo::STORAGE_STATUS_SUCCESS,
			'=ELEMENT_ID' => $inactivePromoList
		],
		'select' => [
			'ELEMENT_ID',
		]
	]);

	while ($promoExport = $queryInactivePromoExported->fetch())
	{
		$existInactivePromoList[$promoExport['ELEMENT_ID']] = true;
	}

	foreach ($inactivePromoList as $promoId)
	{
		if (!isset($existInactivePromoList[$promoId]))
		{
			if (isset($notifyGroupList['INACTIVE'][$promoId]))
			{
				unset($notifyGroupList['INACTIVE'][$promoId]);
			}

			if (isset($notifyGroupList['IN_PAST'][$promoId]))
			{
				unset($notifyGroupList['IN_PAST'][$promoId]);
			}
		}
	}
}

foreach ($notifyGroupList as $notifyGroup => $groupPromoMessages)
{
	$groupPromoMessagesCount = count($groupPromoMessages);
	$notifyGroupType = ($notifyGroup === 'READY' ? 'OK' : 'ERROR');

	if ($groupPromoMessagesCount > 1)
	{
		$list = '<ul>';

		foreach ($groupPromoMessages as $groupPromoMessage)
		{
			$list .=
				'<li>'
				. Feed\Config::getLang('ADMIN_PROMO_RUN_REQUEST_PROMO_' . $notifyGroup . '_GROUP_ITEM', $groupPromoMessage)
				. '</li>';
		}

		$list .= '</ul>';

		$adminMessage = new CAdminMessage(array(
			'TYPE' => $notifyGroupType,
			'MESSAGE' => Feed\Config::getLang('ADMIN_PROMO_RUN_REQUEST_PROMO_' . $notifyGroup . '_GROUP'),
			'DETAILS' => $list . Feed\Config::getLang('ADMIN_PROMO_RUN_REQUEST_PROMO_' . $notifyGroup . '_GROUP_DETAILS'),
			'HTML' => true
		));

		$actionMessage .= $adminMessage->Show();
	}
	else if ($groupPromoMessagesCount === 1)
	{
		$groupPromoMessage = reset($groupPromoMessages);

		$adminMessage = new CAdminMessage(array(
			'TYPE' => $notifyGroupType,
			'MESSAGE' => Feed\Config::getLang('ADMIN_PROMO_RUN_REQUEST_PROMO_' . $notifyGroup, $groupPromoMessage),
			'DETAILS' => Feed\Config::getLang('ADMIN_PROMO_RUN_REQUEST_PROMO_' . $notifyGroup . '_DETAILS', $groupPromoMessage)
		));

		$actionMessage .= $adminMessage->Show();
	}
}

if ($actionMessage !== '')
{
	$actionMessage = '<div class="b-admin-message-list b-admin-text-message">' . $actionMessage . '</div>';
}

// form display

$APPLICATION->SetTitle(Feed\Config::getLang('ADMIN_PROMO_RUN_TITLE'));

CJSCore::Init([ 'jquery' ]);

$APPLICATION->SetAdditionalCSS('/bitrix/css/ligacom.feed/base.css');

$APPLICATION->AddHeadScript('/bitrix/js/ligacom.feed/utils.js');
$APPLICATION->AddHeadScript('/bitrix/js/ligacom.feed/plugin/base.js');
$APPLICATION->AddHeadScript('/bitrix/js/ligacom.feed/plugin/manager.js');
$APPLICATION->AddHeadScript('/bitrix/js/ligacom.feed/ui/admin/exportform.js');
$APPLICATION->AddHeadScript('/bitrix/js/ligacom.feed/ui/input/copyclipboard.js');

Feed\Metrika::reachGoal('generate_YML');

$tabs = [
    [ 'DIV' => 'common', 'TAB' => Feed\Config::getLang('ADMIN_PROMO_RUN_TAB_COMMON') ]
];

$tabControl = new CAdminTabControl('LIGACOM_FEED_ADMIN_PROMO_RUN', $tabs, true, true);

?>
    <form class="js-plugin" action="<?= $APPLICATION->GetCurPage() . '?lang=' . LANGUAGE_ID; ?>" method="post" data-plugin="Ui.Admin.ExportForm">
        <div class="js-export-form__message">
            <?= $actionMessage; ?>
        </div>
        <div class="b-admin-text-message is--hidden js-export-form__timer-holder">
            <?= Feed\Config::getLang('ADMIN_PROMO_RUN_TIMER_LABEL'); ?>:
            <span class="js-export-form__timer">00:00</span>
        </div>
        <?
        $tabControl->Begin();

        echo bitrix_sessid_post();

        if ($isDeleteInactiveByDefault && $requestDeleteInactive)
		{
			?>
			<input type="hidden" name="DELETE_INACTIVE" value="Y" />
			<?
		}

        // common tab

        $tabControl->BeginNextTab([ 'showTitle' => false ]);

        if ($isShowPromoChoose)
		{
			$isSelectPromoMultiple = (count($notifyGroupList['READY']) > 1);

			?>
			<tr>
				<td width="40%" align="right" valign="top"><?= Feed\Config::getLang('ADMIN_PROMO_RUN_FIELD_PROMO_ID'); ?>:</td>
				<td width="60%">
					<select name="PROMO_ID[]" <?= $isSelectPromoMultiple ? 'multiple size="5"' : ''; ?>>
						<?
						foreach ($promoList as $promo)
						{
							?>
							<option value="<?= $promo['ID']; ?>" <?= isset($requestPromoList[$promo['ID']]) ? 'selected' : ''; ?>>[<?= $promo['ID']; ?>] <?= $promo['NAME']; ?></option>
							<?
						}
						?>
					</select>
				</td>
			</tr>
			<?
		}

		if (!$isDeleteInactiveByDefault)
		{
			?>
			<tr>
				<td width="40%" align="right" valign="top"><?= Feed\Config::getLang('ADMIN_PROMO_RUN_FIELD_DELETE_INACTIVE'); ?>:</td>
				<td width="60%">
					<input type="hidden" name="DELETE_INACTIVE" value="N" />
					<input type="checkbox" name="DELETE_INACTIVE" value="Y" <?= $requestDeleteInactive ? 'checked' : ''; ?> />
				</td>
			</tr>
			<?
		}
		?>
        <tr>
            <td width="40%" align="right">
				<span class="b-icon icon--question indent--right b-tag-tooltip--holder">
					<span class="b-tag-tooltip--content"><?= Feed\Config::getLang('ADMIN_PROMO_RUN_FIELD_TIME_LIMIT_HELP'); ?></span>
				</span><?
				echo Feed\Config::getLang('ADMIN_PROMO_RUN_FIELD_TIME_LIMIT') . ':';
				?>
			</td>
            <td>
                <input type="text" name="TIME_LIMIT" value="<?= Feed\Export\Run\Admin::getTimeLimit(); ?>" size="2" />
                <?= Feed\Config::getLang('ADMIN_PROMO_RUN_FIELD_TIME_LIMIT_UNIT'); ?>
                <?= Feed\Config::getLang('ADMIN_PROMO_RUN_FIELD_TIME_LIMIT_SLEEP'); ?>
                <input type="text" name="TIME_SLEEP" value="<?= Feed\Export\Run\Admin::getTimeSleep(); ?>" size="2" />
                <?= Feed\Config::getLang('ADMIN_PROMO_RUN_FIELD_TIME_LIMIT_UNIT'); ?>
            </td>
        </tr>
        <?

        // buttons

        $tabControl->Buttons();

        ?>
        <input type="button" class="adm-btn adm-btn-save js-export-form__run-button" value="<?= Feed\Config::getLang('ADMIN_PROMO_RUN_BUTTON_START'); ?>" />
        <input type="button" class="adm-btn js-export-form__stop-button" value="<?= Feed\Config::getLang('ADMIN_PROMO_RUN_BUTTON_STOP'); ?>" disabled />
        <?

        $tabControl->End();
        ?>
    </form>
    <?
    $jsLang = [
        'LIGACOM_FEED_EXPORT_FORM_QUERY_ERROR_TITLE' => Feed\Config::getLang('ADMIN_PROMO_RUN_QUERY_ERROR_TITLE'),
        'LIGACOM_FEED_EXPORT_FORM_QUERY_ERROR_TEXT' => Feed\Config::getLang('ADMIN_PROMO_RUN_QUERY_ERROR_TEXT'),
    ];
    ?>
    <script>
        BX.message(<?= Feed\Utils::jsonEncode($jsLang, JSON_UNESCAPED_UNICODE); ?>);
    </script>
<?

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';