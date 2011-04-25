<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class WdManager extends WdResume
{
	const REPEAT_PLACEHOLDER = '<span class="lighter">―</span>';

	/**
	 * @var array Currently applyed filters.
	 */
	protected $filters = array();

	public function __construct($module, array $tags=array())
	{
		$model_id = 'primary';

		if (is_string($module))
		{
			global $core;

			list($module_id, $model_id) = explode('/', $module) + array(1 => $model_id);

			$module = $core->modules[$module_id];
		}

		$model = $module->model($model_id);

		#
		# Set the properties here so that they are available to the columns() method, and others.
		#

		$this->module = $module;
		$this->model = $model;

		#
		# columns
		#

		$columns = $this->columns();

		if (isset($tags[self::T_COLUMNS_ORDER]))
		{
			$columns = wd_array_sort_and_filter($tags[self::T_COLUMNS_ORDER], $columns);
		}

		/* TODO-20101019: move parse columns else where */

		if (isset($tags[self::T_KEY]))
		{
			$this->idtag = $tags[self::T_KEY];
		}

		$columns = $this->parseColumns($columns);

		parent::__construct
		(
			$module, $model, $tags + array
			(
				self::T_BLOCK => 'manage',
				self::T_COLUMNS => $columns
			)
		);

		#
		# TODO: move this to WdResume somewhere
		#

		$jobs = $this->jobs();

		foreach ($jobs as $operation => $label)
		{
			$this->addJob($operation, $label);
		}
	}

	protected function columns()
	{
		return array();
	}

	protected function jobs()
	{
		return array();
	}

	/**
	 * Alters the initial query with the specified filters.
	 *
	 * @param WdActiveRecordQuery $query
	 * @param array $filters
	 *
	 * @return WdActiveRecordQuery The altered query.
	 */
	protected function alter_query(WdActiveRecordQuery $query, array $filters)
	{
		return $query;
	}

	protected $user_cache = array();

	/**
	 * Alters records.
	 *
	 * If the 'uid' column exists a cache is prepared for the {@link render_cell_user()} method
	 * with the users objects associated with the displayed records.
	 *
	 * @see WdResume::alter_records()
	 */
	protected function alter_records(array $records)
	{
		global $core;

		if (isset($this->columns['uid']))
		{
			$keys = array();

			foreach ($records as $record)
			{
				if (!$record->uid)
				{
					continue;
				}

				$keys[$record->uid] = true;
			}

			if ($keys)
			{
				$this->user_cache = $core->models['user.users']->find(array_keys($keys));
			}
		}

		return $records;
	}

	/**
	 * @var mixed Last rendered property value. Because the method can be used by multiple other
	 * rendrering methods, the last rendered value is stored by property.
	 */
	protected $last_rendered_filter;

	protected function render_filter_cell($record, $property, $label=null)
	{
		$value = $record->$property;

		if ($label === null)
		{
			$label = wd_entities($value);
		}

		if (isset($this->last_rendered_filter[$property]) && $this->last_rendered_filter[$property] === $label)
		{
			return self::REPEAT_PLACEHOLDER;
		}

		$this->last_rendered_filter[$property] = $label;

		if (isset($this->options['filters'][$property]))
		{
			return $label;
		}

		$ttl = t('Display only: :identifier', array(':identifier' => strip_tags($label)));

		return '<a class="filter" href="' . wd_entities("?$property=$value") . '" title="' . $ttl . '">' . $label . '</a>';
	}

	protected function render_raw_cell($record, $property)
	{
		return wd_entities($record->$property);
	}

	protected $last_rendered_size;

	protected function render_cell_size($record, $property)
	{
		$label = wd_format_size($record->$property);

		if (isset($this->last_rendered_size[$property]) && $this->last_rendered_size[$property] === $label)
		{
			return self::REPEAT_PLACEHOLDER;
		}

		$this->last_rendered_size[$property] = $label;

		return $label;
	}

	private $last_date_value;

	protected function render_cell_date($record, $property)
	{
		$tag = $property;
		$value = substr($record->$property, 0, 10);

		if (isset($this->last_date_value[$property]) && $value == $this->last_date_value[$property])
		{
			return self::REPEAT_PLACEHOLDER;
		}

		$this->last_date_value[$property] = $value;

		if (!(int) $value || !preg_match('#(\d{4})-(\d{2})-(\d{2})#', $value, $date))
		{
			return;
		}

		list(, $year, $month, $day) = $date;


		$filtering = false;
		$filter = null;

		if (isset($this->options['filters'][$property]))
		{
			$filtering = true;
			$filter = $this->options['filters'][$property];
		}

		$parts = array
		(
			array($year, $year),
			array($month, "$year-$month"),
			array($day, "$year-$month-$day")
		);

		$today = date('Y-m-d');
		$today_year = substr($today, 0, 4);
		$today_month = substr($today, 5, 2);
		$today_day = substr($today, 8, 2);

		$select = $parts[2][1];
		$diff_days = $day - $today_day;

		if ($year == $today_year && $month == $today_month && $day <= $today_day && $day > $today_day - 6)
		{
			$label = wd_date_period($value);
			$label = ucfirst($label);

			if ($filtering && $filter == $today)
			{
				$rc = $label;
			}
			else
			{
				$ttl = t('Display only: :identifier', array(':identifier' => $label));

				$rc = <<<EOT
<a href="?$property=$select" title="$ttl" class="filter">$label</a>
EOT;
			}
		}
		else
		{
			$rc = '';

			foreach ($parts as $i => $part)
			{
				list($value, $select) = $part;

				if ($filtering && $filter == $select)
				{
					$rc .= $value;
				}
				else
				{
					$ttl = t('Display only: :identifier', array(':identifier' => $select));

					$rc .= <<<EOT
<a class="filter" href="?$property=$select" title="$ttl">$value</a>
EOT;
				}

				if ($i < 2)
				{
					$rc .= '–';
				}
			}
		}

		return $rc;
	}

	protected function render_cell_time($entry, $tag)
	{
		$value = $entry->$tag;

		if (preg_match('#(\d{2})\:(\d{2})\:(\d{2})#', $value, $time))
		{
			return $time[1] . ':' . $time[2];
		}
	}

	protected function render_cell_datetime($record, $property)
	{
		$date = $this->render_cell_date($record, $property);
		$time = $this->render_cell_time($record, $property);

		return $date . ($time ? '&nbsp;<span class="small light">' . $time . '</span>' : '');
	}

	protected function render_cell_user($record, $property)
	{
		$uid = $record->$property;

		if (empty($this->user_cache[$uid]))
		{
			return '<em class="error">' . t('Unknown user: %uid', array('%uid' => $uid)) . '</em>';
		}

		$user = $this->user_cache[$uid];

		return ($user->firstname && $user->lastname) ? $user->firstname . ' ' . $user->lastname : $user->username;
	}
}