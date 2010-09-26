<?php

class system_nodes_list_WdMarkup extends patron_WdMarkup
{
	protected $constructor = 'system.nodes';
	protected $invoked_constructor;

	public function __invoke(array $args, WdPatron $patron, $template)
	{
		$this->invoked_constructor = null;

		if (isset($args['constructor']))
		{
			$this->invoked_constructor = $args['constructor'];
		}

		$select = isset($args['select']) ? $args['select'] : array();
		$order = isset($args['order']) ? $args['order'] : 'created:desc';
		$range = $this->get_range($select, $args);

		$entries = $this->loadRange($select, $range, $order);

		if (!$entries)
		{
			return;
		}

		$patron->context['self']['range'] = $range;

		return $this->publish($patron, $template, $entries);
	}

	protected function get_range($select, array $args)
	{
		// TODO-20100817: move this to invoke, and maybe create a parse_select function ?

		$page = isset($select['page']) ? $select['page'] : (isset($args['page']) ? $args['page'] : 0);
		$limit = isset($args['limit']) ? $args['limit'] : null;

		if ($limit === null)
		{
			$limit = $this->get_limit();
		}

		return array
		(
			'count' => null,
			'page' => $page,
			'limit' => $limit
		);
	}

	protected function get_limit($which='list', $default=10)
	{
		global $registry;

		$constructor = $this->invoked_constructor ? $this->invoked_constructor : $this->constructor;

		return $registry->get(strtr($constructor, '.', '_') . '.limits.' . $which, $default);
	}

	protected function loadRange($select, &$range, $order='created:desc')
	{
		$page = $range['page'];
		$limit = $range['limit'];

		list($conditions, $args) = $this->parse_conditions($select);

		$where = 'WHERE ' . implode(' AND ', $conditions);

		$model = $this->model;

		if ($this->invoked_constructor)
		{
			global $core;

			$model = $core->models[$this->invoked_constructor];
		}

		$range['count'] = $model->count(null, null, $where, $args);

		list($by, $direction) = explode(':', $order) + array(1 => 'asc');

		return $model->loadRange
		(
			$page * $limit, $limit, $where . " ORDER BY `$by` $direction, title", $args
		)
		->fetchAll();
	}

	protected function parse_conditions($select)
	{
		$constructor = $this->invoked_constructor ? $this->invoked_constructor : $this->constructor;

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
						global $core;

						$ids = $core->models['taxonomy.terms/nodes']->select
						(
							'nid', 'INNER JOIN {prefix}taxonomy_vocabulary_scope scope USING(vid) WHERE termslug = ? AND scope.scope = ?', array
							(
								$value, $constructor
							)
						)
						->fetchAll(PDO::FETCH_COLUMN);

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
		$args['language'] = WdLocale::$language;

		$conditions['constructor'] = 'constructor = :constructor';
		$args['constructor'] = $constructor;

		return array($conditions, $args);
	}
}