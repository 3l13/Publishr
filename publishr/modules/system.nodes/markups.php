<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class system_nodes_view_WdMarkup extends patron_WdMarkup
{
	/**
	 * Publish a template binded with the entry defined by the `select` parameter.
	 *
	 * If the entry failed to be loaded, a WdHTTPException is thrown with a 404 code.
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
		global $core, $page;

		$args += array
		(
			'constructor' => 'system.nodes'
		);

		$this->constructor = $args['constructor'];
		$this->model = $core->models[$this->constructor];

//		var_dump($this->constructor, $args);

		/*
		if (isset($args['constructor']))
		{
			if (!is_array($args['select']))
			{
				if (is_numeric($args['select']))
				{
					$args['select'] = array
					(
						'nid' => $args['select']
					);
				}
				else
				{
					$args['select'] = array
					(
						'slug' => $args['select']
					);
				}
			}

			$args['select']['constructor'] = $args['constructor'];
		}
		*/

		#
		# are we in a view ?
		#

		$body = $page->body;
		$is_view = ($body instanceof site_pages_contents_WdActiveRecord && $body->editor == 'view' && preg_match('#/view$#', $body->content));
		$exception_class = $is_view ? 'WdHTTPException' : 'WdException';

		if (empty($args['select']))
		{
			return;
		}

		$entry = $this->load($args['select']);

		if (!$entry)
		{
			throw new $exception_class
			(
				'The requested entry was not found: !select', array
				(
					'!select' => $args['select']
				),

				404
			);
		}
		else if (!$entry->is_online)
		{
			global $core;

			if (!$core->user->has_permission(WdModule::PERMISSION_ACCESS, $entry->constructor))
			{
				throw new $exception_class
				(
					'The requested entry %uri requires authentication.', array
					(
						'%uri' => $entry->constructor . '/' . $entry->nid
					),

					401
				);
			}

			$entry->title .= ' =!=';
		}

		$rc = $this->publish($patron, $template, $entry);

		#
		# set page node
		#

		if ($is_view && $body->content == $entry->constructor . '/view')
		{
			$page->node = $entry;
			$page->title = $entry->title;
		}

		return $rc;
	}

	protected function load($select)
	{
		$nid = $this->nid_from_select($select);

		$entry = $this->model[$nid];

		if ($entry)
		{
			WdEvent::fire
			(
				'publisher.nodes_loaded', array
				(
					'nodes' => array
					(
						$entry
					)
				)
			);
		}

		return $entry;
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
			global $core;

			$site = $core->site;

			return array
			(
				array
				(
					'(`slug` = ? OR `title` = ?)',
					'(`language` = ? OR `language` = "")',
					'(siteid = ? OR siteid = 0)'
				),

				array
				(
					$conditions, $conditions,
					$site->$language,
					$site->siteid
				)
			);
		}

		// TODO-20100630: The whole point of the inherited markups is to get rid of the
		// WdModel::parseConditions() method.

		return $this->model->parseConditions($conditions);
	}

	protected function nid_from_select($select)
	{
		global $page;

		if (is_numeric($select))
		{
			return $select;
		}
		else if (is_string($select))
		{
			return $this->model->select('nid')
			->where('(slug = ? OR title = ?) AND (siteid = ? OR siteid = 0) AND (language = ? OR language = "")', $select, $select, $page->siteid, $page->site->language)
			->order('language DESC')
			->rc;
		}
		else if (isset($select[Node::NID]))
		{
			return $select[Node::NID];
		}

		list($conditions, $args) = $this->parse_conditions($select);

//		wd_log(__FILE__ . ':: nid from: (\3) \1\2', array($conditions, $args, get_class($this)));

		return $this->model->select('nid')->where(implode(' AND ', $conditions), $args)->order('created DESC')->rc;
	}
}

class system_nodes_list_WdMarkup extends patron_WdMarkup
{
	/*
	protected $constructor = 'system.nodes';
	protected $invoked_constructor;
	*/

	public function __invoke(array $args, WdPatron $patron, $template)
	{
		global $core;
		/*
		$this->invoked_constructor = null;

		if (isset($args['constructor']))
		{
			$this->invoked_constructor = $args['constructor'];
		}
		*/

		$args += array
		(
			'constructor' => 'system.nodes'
		);

		$this->constructor = $args['constructor'];
		$this->model = $core->models[$this->constructor];

		$select = isset($args['select']) ? $args['select'] : array();
		$order = isset($args['order']) ? $args['order'] : 'created DESC';
		$range = $this->get_range($select, $args);

		$entries = $this->loadRange($select, $range, $order);

		if (!$entries)
		{
			return;
		}

		$patron->context['self']['range'] = $range;

		return $this->publish($patron, $template, $entries);
	}

	/*
	protected function __get_model()
	{
		global $core;

		return $core->models[$this->invoked_constructor ? $this->invoked_constructor : $this->constructor];
	}
	*/

	protected function get_range($select, array $args)
	{
		// TODO-20100817: move this to invoke, and maybe create a parse_select function ?

		$limit = isset($args['limit']) ? $args['limit'] : null;

		if ($limit === null)
		{
			$limit = $this->get_limit();
		}

		$rc = array
		(
			'count' => null,
			'limit' => $limit
		);

		if (!empty($select['page']))
		{
			//$page = isset($select['page']) ? $select['page'] : (isset($args['page']) ? $args['page'] : 0);

			$rc['page'] = $select['page'];
		}
		else if (!empty($args['page']))
		{
			$rc['page'] = $args['page'];
		}
		else if (isset($args['offset']))
		{
			$rc['offset'] = $args['offset'];
		}

		return $rc;
	}

	protected function get_limit($which='list', $default=10)
	{
		global $core;

		$constructor = /*$this->invoked_constructor ? $this->invoked_constructor :*/ $this->constructor;

		return $core->site->metas->get(strtr($constructor, '.', '_') . '.limits.' . $which, $default);
	}

	protected function loadRange($select, &$range, $order='created desc')
	{
		list($conditions, $args) = $this->parse_conditions($select);

		$model = $this->model;

		/*
		if ($this->invoked_constructor)
		{
			global $core;

			$model = $core->models[$this->invoked_constructor];
		}
		*/

		$arq = $model->where(implode(' AND ', $conditions), $args);

		$range['count'] = $arq->count;

		$offset = 0;
		$limit = $range['limit'];

		if (isset($range['page']))
		{
			$offset = $range['page'] * $limit;
		}
		else if (isset($range['offset']))
		{
			$offset = $range['offset'];
		}

		$entries = $arq->order("$order, title")->limit($offset, $limit)->all;

		if ($entries)
		{
			WdEvent::fire
			(
				'publisher.nodes_loaded', array
				(
					'nodes' => $entries
				)
			);
		}

		return $entries;
	}

	protected function parse_conditions($select)
	{
		global $core;

		$constructor = /*$this->invoked_constructor ? $this->invoked_constructor :*/ $this->constructor;

		$conditions = array();
		$args = array();

		if (is_array($select))
		{
			foreach ($select as $identifier => $value)
			{
				switch ($identifier)
				{
					case 'categoryslug':
					{
						$ids = $core->models['taxonomy.terms/nodes']
						->select('nid')
						->joins(':taxonomy.vocabulary')
						->joins('INNER JOIN {prefix}taxonomy_vocabulary_scopes scope USING(vid)')
						->where('termslug = ? AND scope.constructor = ?', $value, $constructor)
						->all(PDO::FETCH_COLUMN);

						if (!$ids)
						{
							throw new WdException('There is no entry in the %category category', array('%category' => $value));
						}

						$conditions[] = 'nid IN(' . implode(',', $ids) . ')';
					}
					break;
				}
			}
		}

		#
		#
		#

		$conditions['is_online'] = 'is_online = 1';

		$conditions['language'] = '(language = "" OR language = :language)';
		$args['language'] = WdI18n::$language;

		$conditions['constructor'] = 'constructor = :constructor';
		$args['constructor'] = $constructor;

		return array($conditions, $args);
	}
}