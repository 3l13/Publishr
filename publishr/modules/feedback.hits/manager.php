<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class feedback_hits_WdManager extends WdManager
{
	protected function columns()
	{
		return array
		(
			'name' => array
			(
				'label' => 'Name'
			),

			'hits' => array
			(
				'label' => 'Count',
				'class' => 'size'
			),

			'first' => array
			(
				'label' => 'First',
				'class' => 'date',
				self::COLUMN_HOOK => array($this, 'render_cell_datetime')
			),

			'last' => array
			(
				'label' => 'Last',
				'class' => 'date',
				self::COLUMN_SORT => WdResume::ORDER_DESC,
				self::COLUMN_HOOK => array($this, 'render_cell_datetime')
			)
		);
	}

	protected function get_cell_name($record, $property)
	{
		global $core;

		try
		{
			$node = $core->models['system.nodes'][$record->nid];
		}
		catch (Exception $e)
		{
			return '<em class="danger">Missing record: ' . $record->nid . '</em>';
		}

		$name = $node->title;

		if (!$name)
		{
			$name = '<em>' . $record->resource . '</em>';
		}

		return wd_entities($name);
	}
}