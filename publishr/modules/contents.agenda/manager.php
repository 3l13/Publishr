<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class contents_agenda_WdManager extends contents_news_WdManager
{
	protected function columns()
	{
		return parent::columns() + array
		(
			'finish' => array
			(
				'label' => 'Date de fin',
				'class' => 'date'
			)
		);
	}

	protected function get_cell_finish($entry, $tag)
	{
		if (!(int) $entry->$tag)
		{
			return;
		}

		return parent::get_cell_date($entry, $tag);
	}
}