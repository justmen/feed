<?php
namespace Ligacom\Feed\Api\Partner;

use Ligacom\Feed;
use Bitrix\Main\Web\Uri;
use Ligacom\Feed\Api\Receiver;
use Ligacom\Feed\Exceptions\Api\Partner\OAuthException;

class Base extends Receiver
{
	protected $apiVersion = 'v2';
	protected $apiDomain = 'api.partner.market.yandex.ru';

	protected function getServiceUrl($method)
	{
		return 'https://' . $this->apiDomain . '/' . $this->apiVersion . '/' . $method . '.json';
	}

	protected function setAuthorizationTokens()
	{
		$rsUrl = new Uri($this->requestedUrl);

		if ($this->apiToken instanceof Feed\Api\OAuth2\Token\Model)
		{
			$this->apiToken->setToken($rsUrl);
		}
		else
		{
			if (!isset($this->apiToken['token']) || empty($this->apiToken['token']))
			{
				throw new OAuthException('OAuth token not presented', OAuthException::ERROR_NO_TOKEN);
			}

			if (!isset($this->apiToken['client_id']) || empty($this->apiToken['client_id']))
			{
				throw new OAuthException('OAuth client_id not presented', OAuthException::ERROR_NO_CLIENT_ID);
			}

			$rsUrl->addParams([
				'oauth_token' => $this->apiToken['token'],
				'oauth_client_id' => $this->apiToken['client_id'],
			]);
		}

		$this->requestedUrl = $rsUrl->getUri();
	}

	protected function checkErrorsInResponse($arResult)
	{
		return (int)$arResult['status'] >= 400 || $arResult['content']['status'] === 'ERROR';
	}

	protected function getErrorsFromResponse($arResult)
	{
		$arErrors = [];
		foreach ($arResult['content']['errors'] as $arError)
		{
			$arErrors[] = '[' . $arError['code'] . '] ' . $arError['message'];
		}

		return $arErrors;
	}
}
