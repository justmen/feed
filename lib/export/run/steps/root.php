<?php

namespace Ligacom\Feed\Export\Run\Steps;

use Bitrix\Main;
use Ligacom\Feed;

class Root extends Base
{
	public function getName()
	{
		return 'root';
	}

	public function clear($isStrict = false)
	{
		parent::clear($isStrict);

		if ($isStrict)
		{
			$writer = $this->getWriter();

			$writer->lock(true);
			$writer->unlock();
			$writer->remove();
		}
	}

	public function run($action, $offset = null) :Feed\Result\Step
	{
		$result = new Feed\Result\Step();

		$this->setRunAction($action);

		if ($action === 'full') // on full export reset file
		{
			$context = $this->getContext();
			$tagValuesList = [
				$this->createTagValue($context)
			];
			$elementList = [ [] ]; // one empty array

			$this->writeData($tagValuesList, $elementList, $context);
		}
		else if ($action === 'refresh')
		{
			$publicWriter = $this->getPublicWriter();

			if ($publicWriter)
			{
				$writer = $this->getWriter();

				$writer->copy($publicWriter->getPath());
				$publicWriter->refresh();
			}
		}

		return $result;
	}

	public function updateDate()
	{
		$tagName = $this->getTag()->getName();
		$date = new Main\Type\DateTime();
		$dateType = Feed\Type\Manager::getType(Feed\Type\Manager::TYPE_DATE);
		$writer = $this->getPublicWriter() ?: $this->getWriter();

		$writer->updateAttribute($tagName, 0, [ 'date' => $dateType->format($date) ], '');
	}

    /**
     * @param Feed\Result\XmlNode[] $tagResultList
     * @param array $storageResultList
     * @param array $context
     */
	protected function writeDataFile(array $tagResultList, array $storageResultList, array $context) :void
	{
		$tagResult = reset($tagResultList);

		if ($tagResult->isSuccess())
		{
			$header = $this->getFormat()->getHeader();
			$xmlElement = $tagResult->getXmlContents();

			$this->getWriter()->writeRoot($xmlElement, $header);
		}
	}

	protected function getDataLogEntityType()
	{
		return Feed\Logger\Table::ENTITY_TYPE_EXPORT_RUN_ROOT;
	}

	public function getFormatTag(Feed\Export\Xml\Format\Reference\Base $format, $type = null)
	{
		return $format->getRoot();
	}

	public function getFormatTagParentName(Feed\Export\Xml\Format\Reference\Base $format)
	{
		return null;
	}

	protected function createTagValue($context)
	{
		$result = new Feed\Result\XmlValue();

		if (isset($context['SHOP_DATA']['NAME']))
		{
			$shopName = trim($context['SHOP_DATA']['NAME']);

			if ($shopName !== '')
			{
				$result->addTag('name', $shopName);
			}
		}

		if (isset($context['SHOP_DATA']['COMPANY']))
		{
			$shopCompany = trim($context['SHOP_DATA']['COMPANY']);

			if ($shopCompany !== '')
			{
				$result->addTag('company', $shopCompany);
			}
		}

		return $result;
	}
}