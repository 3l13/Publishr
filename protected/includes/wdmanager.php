<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
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

			$module = $core->getModule($module_id);
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

		return $columns;
	}

	protected function jobs()
	{
		return array();
	}

	protected function get_cell_raw($entry, $tag)
	{
		return wd_entities($entry->$tag);
	}

	protected function get_cell_date($entry, $tag)
	{
		$value = $entry->$tag;

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

		if ($year == $today_year && $month == $today_month && $day <= $today_day && $day > $today_day - 6)
		{
			$label = null;

			if ($day == $today_day)
			{
				$label = "aujourd'hui";
			}
			else if ($day + 1 == $today_day)
			{
				$label = 'hier';
			}
			else
			{
				$label = strftime('%A', strtotime(substr($value, 0, 10)));
			}

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

		if (!is_numeric(substr(strip_tags($date), -1, 1)))
		{
			$date .= ',';
		}

		$time = $this->get_cell_time($entry, $tag);

		return $date . ($time ? '&nbsp;<span class="small light">' . $time . '</span>' : '');
	}

	static protected $user_cache = array();
	static protected $user_model;

	protected function get_cell_user($entry, $tag)
	{
		$uid = $entry->$tag;

		if (empty(self::$user_cache[$uid]))
		{
			if (empty(self::$user_model))
			{
				global $core;

				self::$user_model = $core->getModule('user.users')->model();
			}

			self::$user_cache[$uid] = self::$user_model->load($uid);
		}

		$user = self::$user_cache[$uid];

		if (!$user)
		{
			return '<span class="error">' . t('Unknown user: %uid', array('%uid' => $uid)) . '</span>';
		}

		return ($user->firstname && $user->lastname) ? $user->firstname . ' ' . $user->lastname : $user->username;
	}
}