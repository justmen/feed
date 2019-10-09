<?php

namespace Ligacom\Feed\Export\Entity\Reference;

use Ligacom\Feed;

abstract class Event
{
	protected $type;

	public function setType($type)
	{
		$this->type = $type;
	}

	public function getType()
	{
		return $this->type;
	}

	public function getSourceParams($context)
    {
        return null;
    }

	public function handleChanges($direction, $params)
	{
		$events = $this->getEvents($params);

		foreach ($events as $event)
		{
			$managerEvent = $event;
			$managerArguments = [
				$this->getType(),
				isset($event['method']) ? $event['method'] : $event['event']
			];

			if (isset($event['arguments']))
			{
				foreach ($event['arguments'] as $argument)
				{
					$managerArguments[] = $argument;
				}
			}

			$managerEvent['arguments'] = $managerArguments;
			$managerEvent['method'] = 'callExportSource';

			if ($direction)
			{
				Feed\EventManager::register($managerEvent);
			}
			else
			{
				Feed\EventManager::unregister($managerEvent);
			}
		}
	}

	protected function getEvents($params)
	{
		return [];
	}
}