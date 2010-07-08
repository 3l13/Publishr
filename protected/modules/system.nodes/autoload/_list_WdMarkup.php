<?php

class system_nodes_list_WdMarkup extends patron_WdMarkup
{
	public function __invoke(array $args, WdPatron $patron, $template)
	{
		$select = $args['select'];
		$page = isset($select['page']) ? $select['page'] : $args['page'];
		$limit = $args['limit'];

		if ($limit === null)
		{
			global $registry;

			$limit = $registry->get(wd_camelCase($this->constructor, '.') . '.listLimit', 10);
		}

		$range = array
		(
			'count' => null,
			'page' => $page,
			'limit' => $limit
		);

		$entries = $this->loadRange($select, $range);

		if (!$entries)
		{
			return;
		}

		$patron->context['self']['range'] = $range;

		return $patron->publish($template, $entries);
	}

	protected function loadRange($select, &$range)
	{
		$page = $range['page'];
		$limit = $range['limit'];

		list($conditions, $args) = $this->parse_conditions($select);

		$where = 'WHERE ' . implode(' AND ', $conditions);

		$range['count'] = $this->model->count(null, null, $where, $args);

		return $this->model->loadRange
		(
			$page * $limit, $limit, $where . ' ORDER BY created DESC, title', $args
		)
		->fetchAll();
	}

	protected function parse_conditions($select)
	{
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
								$value, $this->constructor
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
		$args['constructor'] = $this->constructor;

		return array($conditions, $args);
	}
}