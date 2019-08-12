<?php

namespace Ligacom\Feed\Api\OAuth2\Token;

use Bitrix\Main;
use Ligacom\Feed;

class Table extends Feed\Reference\Storage\Table
{
	public static function getTableName()
	{
		return 'ligacom_feed_api_oauth2_token';
	}

	public static function getUfId()
	{
		return 'LIGACOM_FEED_API_OAUTH2_TOKEN';
	}

	public static function getMap()
	{
		return [
			new Main\Entity\IntegerField('ID', [
				'autocomplete' => true,
				'primary' => true,
			]),
			new Main\Entity\StringField('TOKEN_TYPE', [
				'required' => true,
			]),
			new Main\Entity\StringField('ACCESS_TOKEN', [
				'required' => true,
			]),
			new Main\Entity\StringField('REFRESH_TOKEN', [
				'required' => true,
			]),
			new Main\Entity\DatetimeField('EXPIRES_AT', [
				'required' => true,
			]),
			new Main\Entity\StringField('SCOPE', [
				'required' => true,
			]),
		];
	}
}
