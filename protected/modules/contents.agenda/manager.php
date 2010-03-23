<?php

class contents_agenda_WdManager extends contents_WdManager
{
	protected function columns()
	{
		return parent::columns() + array
		(
			'date' => array
			(
				self::COLUMN_LABEL => 'Date',
				self::COLUMN_CLASS => 'date'
			),

			'finish' => array
			(
				self::COLUMN_LABEL => 'Date de fin',
				self::COLUMN_CLASS => 'date'
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