<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class WdManager extends WdResume
{
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

	protected function parseColumns($columns)
	{
		foreach ($columns as $tag => &$column)
		{
			if (!is_array($column))
			{
				$column = array();
			}

			if (isset($column[self::COLUMN_HOOK]))
			{
				continue;
			}

			$callback = 'get_cell_' . $tag;

			if (method_exists($this, $callback))
			{
				$column[self::COLUMN_HOOK] = array($this, $callback);
			}
			else
			{
				$column[self::COLUMN_HOOK] = array($this, 'get_cell_raw');
			}
		}

		#
		# key
		#

		if ($this->idtag)
		{
			$columns = array_merge
			(
				array
				(
					$this->idtag => array
					(
						self::COLUMN_LABEL => null,
						self::COLUMN_CLASS => 'key',
						self::COLUMN_HOOK => array($this, 'get_cell_key')
					)
				),

				$columns
			);

//			var_dump($columns);
		}

		return $columns;
	}

	protected function jobs()
	{
		return array();
	}

	protected function alter_query(WdActiveRecordQuery $query)
	{
		return $query;
	}

	static protected $user_cache = array();

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
				self::$user_cache = $core->models['user.users']->find(array_keys($keys));
			}
		}

		return $records;
	}

	protected function get_cell_raw($entry, $tag)
	{
		return wd_entities($entry->$tag);
	}

	private $last_date_value;

	protected function get_cell_date($entry, $tag)
	{
		$value = substr($entry->$tag, 0, 10);

		if (isset($this->last_date_value[$tag]) && $value == $this->last_date_value[$tag])
		{
			return '<span class="lighter">―</span>';
		}

		$this->last_date_value[$tag] = $value;

		if (!(int) $value || !preg_match('#(\d{4})-(\d{2})-(\d{2})#', $value, $date))
		{
			return;
		}

		list(, $year, $month, $day) = $date;

		$display_where = $this->tags[self::WHERE];
		$display_is = $this->tags[self::IS];

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
			$label = isset(WdI18n::$conventions['dates']['fields']['day_relative'][$diff_days])
			? WdI18n::$conventions['dates']['fields']['day_relative'][$diff_days]
			: strftime('%A', strtotime(substr($value, 0, 10)));

		/*

		$date = new DateTime($value);
		$today = new DateTime();
		$diff = $today->diff($date);

		$select = $parts[2][1];
		$diff_days = $diff->days;

		if ($diff->invert == 1 && $diff_days < 7)
		{
			$label = null;
			$now_hour = date('H');

			if (!$diff_days && $diff->h <= $now_hour)
			{
				$label = "aujourd'hui";
			}
			else if ($diff_days == 1 || (!$diff_days && $diff->h > $now_hour))
			{
				$label = "hier";
			}
			else
			{
				$label = strftime('%A', strtotime(substr($value, 0, 10)));
			}
			*/

			$label = ucfirst($label);

			if ($display_where == $tag && $display_is == $today)
			{
				$rc = $label;
			}
			else
			{
				$ttl = t('Display only: :identifier', array(':identifier' => $label));

				$url = $this->getURL
				(
					array
					(
						self::WHERE => $tag,
						self::IS => $select
					)
				);

				$rc = '<a href="' . $url . '" title="' . $ttl . '" class="filter">' . $label . '</a>';
			}
		}
		else
		{
			$rc = '';

			foreach ($parts as $i => $part)
			{
				list($value, $select) = $part;

				if ($display_where == $tag && $display_is == $select)
				{
					$rc .= $value;
				}
				else
				{
					$ttl = t('Display only: :identifier', array(':identifier' => $select));

					$url = $this->getURL
					(
						array
						(
							self::WHERE => $tag,
							self::IS => $select
						)
					);

					$rc .= '<a class="filter" href="' . $url . '" title="' . $ttl . '">' . $value . '</a>';
				}

				if ($i < 2)
				{
					$rc .= '–';
				}
			}
		}

		return $rc;
	}

	protected function get_cell_time($entry, $tag)
	{
		$value = $entry->$tag;

		if (preg_match('#(\d{2})\:(\d{2})\:(\d{2})#', $value, $time))
		{
			return $time[1] . ':' . $time[2];
		}
	}

	protected function get_cell_datetime($entry, $tag)
	{
		$date = $this->get_cell_date($entry, $tag);
		$time = $this->get_cell_time($entry, $tag);

		/*
		if ($time && !is_numeric(substr(strip_tags($date), -1, 1)))
		{
			$date .= ',';
		}
		*/

		return $date . ($time ? '&nbsp;<span class="small light">' . $time . '</span>' : '');
	}

	protected function get_cell_user($entry, $tag)
	{
		$uid = $entry->$tag;

		$user = self::$user_cache[$uid];

		if (!$user)
		{
			return '<span class="error">' . t('Unknown user: %uid', array('%uid' => $uid)) . '</span>';
		}

		return ($user->firstname && $user->lastname) ? $user->firstname . ' ' . $user->lastname : $user->username;
	}
}