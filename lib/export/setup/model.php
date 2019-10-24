<?php

namespace Ligacom\Feed\Export\Setup;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Ligacom\Feed;

Loc::loadMessages(__FILE__);

class Model extends Feed\Reference\Storage\Model
{
	/** @var Feed\Export\Xml\Format\Reference\Base */
	protected $format = null;

	public static function getDataClass()
	{
		return Table::getClassName();
	}

	public static function normalizeFileName($fileName, $primary = null)
	{
		$fileName = basename(trim($fileName), '.xml');

		if ($fileName === '' && !empty($primary))
		{
			$fileName = 'setup_' . $primary;
		}

		return ($fileName !== '' ? $fileName . '.xml' : null);
	}

	public function onBeforeRemove()
    {
        if ($this->isAutoUpdate())
        {
            $this->handleChanges(false);
        }

        if ($this->hasFullRefresh())
        {
            $this->handleRefresh(false);
        }
    }

	public function onAfterSave()
	{
	    $isAutoUpdate = $this->isAutoUpdate();
	    $hasFullRefresh = $this->hasFullRefresh();

        $this->handleChanges($isAutoUpdate);
        $this->handleRefresh($hasFullRefresh);
	}

	public function handleChanges($direction)
	{
		if (!$direction || $this->isFileReady())
		{
		    $entityType = Feed\Export\Track\Table::ENTITY_TYPE_SETUP;
		    $entityId = $this->getId();

            if ($direction)
            {
                $trackSourceList = $this->getTrackSourceList();

                Feed\Export\Track\Registry::addEntitySources($entityType, $entityId, $trackSourceList);
            }
            else
            {
                Feed\Export\Track\Registry::removeEntitySources($entityType, $entityId);
            }
		}
	}


	public function getTrackSourceList()
    {
        $result = [];

        foreach ($this->getIblockLinkCollection() as $iblockLink)
        {
            $result = array_merge($result, $iblockLink->getTrackSourceList());
        }

        return $result;
    }

	public function handleRefresh($direction)
	{
		$interval = $this->getRefreshPeriod();

		$agentParams = [
			'method' => 'refreshStart',
			'arguments' => [ (int)$this->getId() ],
			'interval' => $interval,
			'next_exec' => ConvertTimeStamp(time() + $interval, 'FULL')
		];

		if ($direction)
		{
			if ($this->isFileReady())
			{
				Feed\Export\Run\Agent::register($agentParams);
			}
		}
		else
		{
			Feed\Export\Run\Agent::unregister($agentParams);
			Feed\Export\Run\Agent::unregister([
				'method' => 'refresh',
				'arguments' => [ (int)$this->getId() ]
			]);

			Feed\Export\Run\Agent::releaseState('refresh', (int)$this->getId());
		}
	}

	/**
	 * @return array
	 */
	public function getContext()
	{
		$format = $this->getFormat();
		$result = [
			'SETUP_ID' => $this->getId(),
			'EXPORT_SERVICE' => $this->getField('EXPORT_SERVICE'),
			'EXPORT_FORMAT' => $this->getField('EXPORT_FORMAT'),
			'EXPORT_FORMAT_TYPE' => $format->getType(),
			'ENABLE_AUTO_DISCOUNTS' => $this->isAutoDiscountsEnabled(),
			'DOMAIN_URL' => $this->getDomainUrl(),
			'USER_GROUPS' => [2], // support only public
			'HAS_CATALOG' => Main\ModuleManager::isModuleInstalled('catalog'),
			'HAS_SALE' => Main\ModuleManager::isModuleInstalled('sale'),
			'SHOP_DATA' => $this->getShopData()
		];


		return $result;
	}


	public function getShopData()
	{
		$fieldValue = $this->getField('SHOP_DATA');

		return is_array($fieldValue) ? $fieldValue : null;
	}

	public function getFormat()
	{
		if (!isset($this->format))
		{
			$this->format = $this->loadFormat();
		}

		return $this->format;
	}

	protected function loadFormat()
	{
		$service = $this->getField('EXPORT_SERVICE');
		$format = $this->getField('EXPORT_FORMAT');

		return Feed\Export\Xml\Format\Manager::getEntity($service, $format);
	}

	public function getFileName()
	{
		return static::normalizeFileName($this->getField('FILE_NAME'), $this->getId());
	}

	public function getFileRelativePath()
	{
		return BX_ROOT . '/catalog_export/' . $this->getFileName();
	}

	public function getFileAbsolutePath()
	{
		$relativePath = $this->getFileRelativePath();

		return Main\IO\Path::convertRelativeToAbsolute($relativePath);
	}

	public function isFileReady()
	{
		$path = $this->getFileAbsolutePath();

		return Main\IO\File::isFileExists($path);
	}

	public function getFileUrl()
	{
		return $this->getDomainUrl() . $this->getFileRelativePath();
	}

	public function getDomainUrl()
	{
		return 'http' . ($this->isHttps() ? 's' : '') . '://' . $this->getDomain();
	}

	public function getDomain()
	{
		return $this->getField('DOMAIN');
	}

	public function isHttps()
	{
		return ($this->getField('HTTPS') === '1');
	}

	public function isAutoDiscountsEnabled()
	{
		return ($this->getField('ENABLE_AUTO_DISCOUNTS') === '1');
	}

	public function isAutoUpdate()
	{
		return ($this->getField('AUTOUPDATE') === '1');
	}

	public function hasFullRefresh()
	{
		return $this->getRefreshPeriod() !== null;
	}

	public function getRefreshPeriod()
	{
		$period = (int)$this->getField('REFRESH_PERIOD');
		$result = null;

		if ($period > 0)
		{
			$result = $period;
		}

		return $result;
	}

    /**
     * @return Feed\Reference\Storage\Collection
     * @throws Main\SystemException
     */
	public function getIblockLinkCollection()
	{
		return $this->getChildCollection('IBLOCK_LINK');
	}


	protected function getChildCollectionReference($fieldKey)
	{
		$result = null;

		switch ($fieldKey)
		{
			case 'IBLOCK_LINK':
				$result = Feed\Export\IblockLink\Collection::getClassName();
			break;

		}

		return $result;
	}

    protected function getChildCollectionQueryParameters($fieldKey)
    {
        $result = [];

        switch ($fieldKey)
        {

            default:
                $result = parent::getChildCollectionQueryParameters($fieldKey);
            break;
        }

        return $result;
    }
}
