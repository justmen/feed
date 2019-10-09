<?php

namespace Ligacom\Feed\Component\ExportOffer;

use Ligacom\Feed;

class GridList extends Feed\Component\Data\GridList
{
	public function getDefaultFilter()
	{
		$elementId = (int)$this->getComponentParam('ELEMENT_ID') ?: -1;

		return [
			[
				'LOGIC' => 'OR',
				[ '=ELEMENT_ID' => $elementId ],
				[ '=PARENT_ID' => $elementId ]
			]
		];
	}
}