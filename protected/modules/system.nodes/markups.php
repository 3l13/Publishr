<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class system_nodes_WdMarkups extends patron_markups_WdHooks
{
	static protected function model($name='system.nodes')
	{
		return parent::model($name);
	}

	static protected function parseSelect($select)
	{
		list($where, $params) = parent::parseSelect($select);

		foreach ($select as $identifier => $value)
		{
			switch ($identifier)
			{
				case 'nid':
				{
					$where[] = 'nid = ?';
					$params[] = $value;
				}
				break;

				case 'slug':
				{
					$where[] = 'slug = ?';
					$params[] = $value;
				}
				break;
			}
		}

		return array($where, $params);
	}

	static public function node(WdHook $hook, WdPatron $patron, $template)
	{
		$select = $hook->args['select'];

		if (!$select)
		{
			return;
		}

		if (!is_numeric($select))
		{
			$select = self::model()->select
			(
				'nid', 'WHERE (slug = ? OR title = ?) AND (language = ? OR language = "") ORDER BY language DESC LIMIT 1', array
				(
					$select, $select, WdLocale::$language
				)
			)
			->fetchColumnAndClose();
		}

		$entry = self::model()->load($select);

		if (!$entry)
		{
			return;
		}

		return $patron->publish($template, $entry);
	}

	static public function nodes(WdHook $hook, WdPatron $patron, $template)
	{
		$scope = $hook->args['scope'];
		$limit = $hook->args['limit'];
		$page = $hook->args['page'];
		$order = $hook->args['order'];

		list($by, $direction) = explode(':', $order) + array(1 => 'asc');

		$entries = self::model($scope)->loadRange
		(
			$page * $limit, $limit, 'WHERE is_online = 1 ORDER BY ' . $by . ' ' . $direction
		)
		->fetchAll();

		if (!$entries)
		{
			return;
		}

		return $patron->publish($template, $entries);
	}
}