<?php

\Bitrix\Main\Loader::registerAutoLoadClasses('ligacom.feed', [
	'\Ligacom\Feed\Api\OAuth2\Token\Table' => '/lib/api/oauth2/token/table.php',
	'\Ligacom\Feed\Reference\Storage\Table' => '/lib/reference/storage/table.php',
	'\Ligacom\Feed\Export\Setup\Table' => '/lib/export/setup/table.php',
	'\Ligacom\Feed\Export\IblockLink\Table' => '/lib/export/iblocklink/table.php',
	'\Ligacom\Feed\Export\Param\Table' => '/lib/export/param/table.php',
	'\Ligacom\Feed\Export\ParamValue\Table' => '/lib/export/paramvalue/table.php',
	'\Ligacom\Feed\Export\Filter\Table' => '/lib/export/filter/table.php',
	'\Ligacom\Feed\Export\FilterCondition\Table' => '/lib/export/filtercondition/table.php',
	'\Ligacom\Feed\Export\Track\Table' => '/lib/export/track/table.php',
	'\Ligacom\Feed\Logger\Table' => '/lib/logger/table.php',
]);
