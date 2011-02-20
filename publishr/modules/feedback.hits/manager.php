<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class feedback_hits_WdManager extends WdManager
{
	protected function columns()
	{
		return array
		(
			'name' => array
			(
				self::COLUMN_LABEL => 'Name'
			),

			'hits' => array
			(
				self::COLUMN_LABEL => 'Count',
				self::COLUMN_CLASS => 'size'
			),

			'first' => array
			(
				self::COLUMN_LABEL => 'First',
				self::COLUMN_CLASS => 'date',
				self::COLUMN_HOOK => array($this, 'get_cell_datetime')
			),

			'last' => array
			(
				self::COLUMN_LABEL => 'Last',
				self::COLUMN_CLASS => 'date',
				self::COLUMN_SORT => WdResume::ORDER_DESC,
				self::COLUMN_HOOK => array($this, 'get_cell_datetime')
			)
		);
	}

	protected function get_cell_name(WdActiveRecord $record, $property)
	{
		global $core;

		$node = $core->models['system.nodes'][$record->nid];

		if (!$node)
		{
			return;
		}

		$name = $record->title;

		if (!$name)
		{
			$name = '<em>' . $record->resource . '</em>';
		}

		return wd_entities($name);
	}
}