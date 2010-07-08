<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class system_nodes_view_WdMarkup extends patron_WdMarkup
{
	protected $constructor = 'system.nodes';

	/**
	 * Publish a template binded with the entry defined by the `select` parameter.
	 *
	 * If the entry failed to be loaded, a WdHTTPException is thrown with the 404 code.
	 *
	 * If the entry is offline and the user has no permission to access it, a WdHTTPException is
	 * thrown with the 401 code.
	 *
	 * If the entry is offline and the user has permission to acces it, the title of the entry is
	 * marked with '=!='.
	 *
	 * @param array $args
	 * @param WdPatron $patron
	 * @param unknown_type $template
	 */

	public function __invoke(array $args, WdPatron $patron, $template)
	{
		$entry = $this->load($args['select']);

		if (!$entry)
		{
			throw new WdHTTPException
			(
				'The requested entry was not found.', array
				(

				),

				404
			);
		}
		else if (!$entry->is_online)
		{
			global $app;

			if (!$app->user->has_permission(PERMISSION_ACCESS, $entry))
			{
				throw new WdHTTPException
				(
					'The requested entry %uri requires authentification.', array
					(
						'%uri' => $entry->constructor . '/' . $entry->nid
					),

					401
				);
			}

			$entry->title .= ' =!=';
		}

		return $patron->publish($template, $entry);
	}

	protected function load($select)
	{
		$nid = $this->nid_from_select($select);

		return $this->model()->load($nid);
	}

	protected function parse_conditions($conditions)
	{
		if (is_numeric($conditions))
		{
			return array
			(
				array('`nid` = ?'),
				array($conditions)
			);
		}
		else if (is_string($conditions))
		{
			return array
			(
				array
				(
					'(`slug` = ? OR `title` = ?)',
					'(`language` = ? OR `language` = "")'
				),

				array
				(
					$conditions, $conditions,
					WdLocale::$language
				)
			);
		}

		// TODO-20100630: The whole point of the inherited markups is to get rid of the
		// WdModel::parseConditions() method.

		return $this->model()->parseConditions($conditions);
	}

	protected function nid_from_select($select)
	{
		if (is_numeric($select))
		{
			return $select;
		}
		else if (is_string($select))
		{
			list($conditions, $args) = $this->parse_conditions($select);

//			wd_log(__FILE__ . ':: using string: \1\2', array($conditions, $args));

			return $this->model()->select
			(
				'nid', 'WHERE (slug = ? OR title = ?) AND (language = ? OR language = "") ORDER BY language DESC LIMIT 1', array
				(
					$select, $select, WdLocale::$language
				)
			)
			->fetchColumnAndClose();
		}
		else if (isset($select[Node::NID]))
		{
			return $select[Node::NID];
		}

		list($conditions, $args) = $this->parse_conditions($select);

//		wd_log(__FILE__ . ':: nid from: (\3) \1\2', array($conditions, $args, get_class($this)));

		return $this->model()->select
		(
			'nid', ($conditions ? 'WHERE ' . implode(' AND ', $conditions) : '') . 'ORDER BY created DESC LIMIT 1', $args
		)
		->fetchColumnAndClose();
	}
}







class system_nodes_WdMarkups extends patron_markups_WdHooks
{
	static protected function model($name='system.nodes')
	{
		return parent::model($name);
	}

	/*
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
	*/

	/*
	static public function node(array $args, WdPatron $patron, $template)
	{
		$select = $args['select'];

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
	*/

	static public function nodes(array $args, WdPatron $patron, $template)
	{
		$scope = $args['scope'];
		$limit = $args['limit'];
		$page = $args['page'];
		$order = $args['order'];

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