<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class contents_agenda_home_WdMarkup extends contents_home_WdMarkup
{
	protected $constructor = 'contents.agenda';

	protected function parse_conditions($select)
	{
		list($conditions, $args) = parent::parse_conditions($select);

		$conditions[] = 'date >= CURRENT_DATE';

		return array($conditions, $args);
	}

	protected function loadRange($select, &$range, $order='date:asc')
	{
		$entries = parent::loadRange($select, $range, $order);

		if (!$entries)
		{
			return;
		}

		$by_month = array();

		foreach ($entries as $entry)
		{
			$month = substr($entry->date, 0, 7) . '-01';

			$by_month[$month][] = $entry;
		}

		return $by_month;
	}
}

class contents_agenda_list_WdMarkup extends contents_list_WdMarkup
{
	protected $constructor = 'contents.agenda';

	protected function parse_conditions($select)
	{
		list($conditions, $args) = parent::parse_conditions($select);

		$conditions[] = 'date >= CURRENT_DATE';

		return array($conditions, $args);
	}

	protected function loadRange($select, &$range, $order='date:asc')
	{
		return parent::loadRange($select, $range, $order);
	}
}