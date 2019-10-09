<?php

$MESS['LIGACOM_FEED_ACCESS_DENIED'] = 'Доступ запрещен';
$MESS['LIGACOM_FEED_MODULE_NOT_INSTALLED'] = 'Модуль не установлен';

$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_ACTION_SESSION_EXPIRED'] = 'Истекла сессия';

$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_ACTION_RUN_PROMO_NOT_FOUND'] = 'Не найдены &laquo;Акции&raquo; для выгрузки';
$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_ACTION_RUN_SETUP_NOT_FOUND'] = 'Не найдены &laquo;Прайс-листы&raquo; для выгрузки';
$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_ACTION_RUN_SUCCESS_TITLE'] = 'Выгрузка выполнена';
$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_ACTION_RUN_SUCCESS_DETAILS'] = '<a href="#URL#" target="_blank">Результаты выгрузки</a>';
$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_ACTION_RUN_SUCCESS_LOG'] = '<a href="#URL#" target="_blank">Журнал ошибок</a>';

$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_ACTION_RUN_PROGRESS_TITLE'] = 'Выполняем выгрузку';
$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_ACTION_RUN_PROGRESS_SETUP'] = 'Профиль &laquo;#NAME#&raquo;';
$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_ACTION_RUN_PROGRESS_STEP'] = ' шаг &laquo;#STEP#&raquo;';
$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_ACTION_RUN_PROGRESS_READY_COUNT'] = ' обработано: #COUNT# #LABEL#';
$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_ACTION_RUN_PROGRESS_READY_COUNT_LABEL_1'] = 'элемент';
$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_ACTION_RUN_PROGRESS_READY_COUNT_LABEL_2'] = 'элемента';
$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_ACTION_RUN_PROGRESS_READY_COUNT_LABEL_5'] = 'элементов';

$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_ACTION_RUN_ERROR_TITLE'] = 'Произошла ошибка';
$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_ACTION_RUN_ERROR_UNDEFINED'] = 'Получен неизвестный статус, обратитесь к администратору.';

$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_ACTION_NOT_FOUND'] = 'Действие не найдено';

$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_TITLE'] = 'Выгрузка акций';
$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_TAB_COMMON'] = 'Параметры';
$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_FIELD_PROMO_ID'] = 'Акция';
$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_FIELD_PROMO_ID_CHOOSE'] = 'Выберите...';
$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_FIELD_DELETE_INACTIVE'] = 'Удалить неактивные';
$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_FIELD_TIME_LIMIT'] = 'Длительность шага';
$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_FIELD_TIME_LIMIT_UNIT'] = 'сек.';
$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_FIELD_TIME_LIMIT_SLEEP'] = ', интервал:';
$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_FIELD_TIME_LIMIT_HELP'] = 'Эти параметры определяют нагрузку на сервер. Она тем выше, чем больше длительность шага и меньше интервал. Если выгрузка проходит нормально, лучше их не менять. Подробнее &mdash; в <a href="https://yandex.ru/support/market-cms/YML_and_errors.html" target="_blank">Помощи</a>';
$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_BUTTON_START'] = 'Запустить выгрузку';
$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_BUTTON_STOP'] = 'Остановить';
$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_TIMER_LABEL'] = 'Затраченное время';

$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_QUERY_ERROR_TITLE'] = 'Произошла ошибка';
$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_QUERY_ERROR_TEXT'] = "HTTP-статус: #HTTP_STATUS# \nТекст ошибки: #TEXT_STATUS# \nОтвет: #RESPONSE#";

$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_REQUEST_PROMO_READY'] = 'Акция &laquo;#NAME#&raquo; готова к выгрузке';
$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_REQUEST_PROMO_READY_DETAILS'] = 'Запустите выгрузку, чтобы акция была добавлена в выбранные для неё прайс-листы или информация о ней обновилась';
$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_REQUEST_PROMO_READY_GROUP'] = 'Акции готовые к выгрузке:';
$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_REQUEST_PROMO_READY_GROUP_ITEM'] = '#NAME#';
$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_REQUEST_PROMO_READY_GROUP_DETAILS'] = 'Запустите выгрузку, чтобы акции были добавлены в выбранные для них прайс-листы или информация о них обновилась';
$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_REQUEST_PROMO_INACTIVE'] = 'Акция &laquo;#NAME#&raquo; неактивна';
$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_REQUEST_PROMO_INACTIVE_DETAILS'] = 'Если вы хотите убрать из прайс-листов информацию об этой акции, нажмите кнопку &laquo;Запустить выгрузку&raquo;.';
$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_REQUEST_PROMO_INACTIVE_GROUP'] = 'Неактивные акции:';
$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_REQUEST_PROMO_INACTIVE_GROUP_ITEM'] = '#NAME#';
$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_REQUEST_PROMO_INACTIVE_GROUP_DETAILS'] = 'Если вы хотите убрать из прайс-листов информацию об этих акциях, нажмите кнопку &laquo;Запустить выгрузку&raquo;.';
$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_REQUEST_PROMO_IN_FUTURE'] = 'Дата начала акции &laquo;#NAME#&raquo; еще не наступила';
$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_REQUEST_PROMO_IN_FUTURE_DETAILS'] = 'Акция будет выгружена в выбранные для неё прайс-листы на агентах автоматически #DATE#';
$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_REQUEST_PROMO_IN_FUTURE_GROUP'] = 'Дата начала еще не наступила для акций:';
$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_REQUEST_PROMO_IN_FUTURE_GROUP_ITEM'] = '#NAME#: #DATE#';
$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_REQUEST_PROMO_IN_FUTURE_GROUP_DETAILS'] = 'Акции будут выгружены в выбранные для них прайс-листы на агентах автоматически';
$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_REQUEST_PROMO_IN_PAST'] = 'Дата завершения акции &laquo;#NAME#&raquo; уже прошла';
$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_REQUEST_PROMO_IN_PAST_DETAILS'] = 'Если вы хотите убрать из прайс-листов информацию об этой акции, нажмите кнопку &laquo;Запустить выгрузку&raquo;.';
$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_REQUEST_PROMO_IN_PAST_GROUP'] = 'Дата завершения уже прошла для акций:';
$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_REQUEST_PROMO_IN_PAST_GROUP_ITEM'] = '#NAME#';
$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_REQUEST_PROMO_IN_PAST_GROUP_DETAILS'] = 'Если вы хотите убрать из прайс-листов информацию об этих акциях, нажмите кнопку &laquo;Запустить выгрузку&raquo;.';
$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_REQUEST_PROMO_DELETE'] = 'Акция [#ID#] удалена';
$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_REQUEST_PROMO_DELETE_DETAILS'] = 'Если вы хотите убрать из прайс-листов информацию об этой акции, нажмите кнопку &laquo;Запустить выгрузку&raquo;.';
$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_REQUEST_PROMO_DELETE_GROUP'] = 'Акции, которые были удалены:';
$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_REQUEST_PROMO_DELETE_GROUP_ITEM'] = '[#ID#]';
$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_REQUEST_PROMO_DELETE_GROUP_DETAILS'] = 'Если вы хотите убрать из прайс-листов информацию об этих акциях, нажмите кнопку &laquo;Запустить выгрузку&raquo;.';

$MESS['LIGACOM_FEED_ADMIN_PROMO_RUN_GO_MIGRATION'] = 'Попробуем исправить?';