<?php

class taxonomy_support_WdMarkups extends patron_markups_WdHooks
{
	static protected function model($name='taxonomy.vocabulary')
	{
		return parent::model($name);
	}

	static public function popularity(WdHook $hook, WdPatron $patron, $template)
	{
		extract($hook->params, EXTR_PREFIX_ALL, 'p');

		$where = array();
		$params = array();

		#
		# vocabulary
		#

		if ($p_vocabulary)
		{
			$where[] = '(v.vocabulary = ? OR v.vocabularyslug = ?)';
			$params[] = $p_vocabulary;
			$params[] = $p_vocabulary;
		}

		#
		# scope of the vocabulary
		#

		if ($p_scope)
		{
			$parts = explode(',', $p_scope);
			$parts = array_map('trim', $parts);

			if (count($parts) > 1)
			{
				$where[] = 'scope IN (' . implode(', ', array_pad(array(), count($parts), '?')) . ')';
				$params = array_merge($params, $parts);
			}
			else
			{
				$where[] = 'scope = ?';
				$params[] = $p_scope;
			}
		}

		#
		# query
		#

//		echo t('where: <code>\1</code> \2', array($where, $params));

		global $core;

		$entries = $core->db()->query
		(
			'SELECT t.*,

			(SELECT COUNT(nid) FROM {prefix}taxonomy_terms_nodes tn WHERE tn.vtid = t.vtid) AS `used`

			FROM {prefix}taxonomy_vocabulary v
			INNER JOIN {prefix}taxonomy_vocabulary_scope vs USING(vid)
			INNER JOIN {prefix}taxonomy_terms t USING(vid)

			' . ($where ? 'WHERE ' . implode(' AND ', $where) : '') . '

			GROUP BY vtid ORDER BY term',

			$params
		)
		->fetchAll(PDO::FETCH_ASSOC);

		#
		# remove used entries
		#

		foreach ($entries as $i => $entry)
		{
			if ($entry['used'])
			{
				continue;
			}

			unset($entries[$i]);
		}

		#
		# scale popularities
		#

		if ($p_scale)
		{
			$min = 0xFFFFFFFF;
			$max = 0;

			foreach ($entries as $entry)
			{
				$min = min($min, $entry['used']);
				$max = max($max, $entry['used']);
			}

			$range = max($max - $min, 1);

			//echo "min: $min, max: $max, range: $range<br />";

			foreach ($entries as &$entry)
			{
				$entry['popularity'] = 1 + round(($entry['used'] - $min) / $range * ($p_scale - 1));
			}
		}

		return $patron->publish($template, $entries);
	}

	static public function terms(WdHook $hook, WdPatron $patron, $template)
	{
		$where = array();
		$params = array();

		$inner = ' INNER JOIN {prefix}taxonomy_terms term USING(vid)';

		$scope = $hook->params['scope'];

		if ($scope)
		{
			$where[] = 'scope = ?';
			$params[] = $scope;

			$inner .= ' INNER JOIN {prefix}taxonomy_vocabulary_scope USING(vid)';
		}

		$vocabulary = $hook->params['vocabulary'];

		if ($vocabulary)
		{
			if (is_numeric($vocabulary))
			{
				$where[] = 'vid = ?';
				$params[] = $vocabulary;
			}
			else
			{
				$where[] = '(vocabulary = ? OR vocabularyslug = ?)';
				$params[] = $vocabulary;
				$params[] = $vocabulary;
			}
		}


		$where[] = '(SELECT COUNT(node.nid) FROM {prefix}system_nodes node INNER JOIN {prefix}taxonomy_terms_nodes WHERE vtid = term.vtid AND is_online = 1)';


		$where = $where ? 'WHERE ' . implode(' AND ', $where) : null;

		#
		#
		#

		global $core;

		$entries = $core->db()->query
		(
			'SELECT * FROM {prefix}taxonomy_vocabulary' . $inner . $where . ' ORDER BY term',

			$params
		)
		->fetchAll(PDO::FETCH_ASSOC);

		return $patron->publish($template, $entries);
	}

	/*

	Charge des noeuds 'complets' selon un _vocabulaire_ et/ou une _portée_.

	Parce qu'un même vocabulaire peut-être utilisé sur plusieurs modules, si 'scope' est
	définit le constructeur du noeud doit être connu et égal à 'scope'. Pour cela il nous faut
	joindre la table du module `system.nodes`.

	Si scope est défini c'est plus simple, parce que toutes les entrées sont chargées depuis un
	même module.

	Si scope est défini, il faudrait peut-être modifier 'self' pour qu'il contienne les données du
	terme. Ou alors utiliser un autre marqueur pour l'occasion... hmm ce serait peut-être le mieux.
	<wdp:taxonomy:term select="" vacabulary="" scope="" />

	Les options de 'range' ne doivent pas être appliquée aux termes mais au noeud chargés dans un
	second temps. Notamment les options d'ordre.

	*/

	static public function nodes(WdHook $hook, WdPatron $patron, $template)
	{
		$where = array();
		$params = array();

		$inner = ' INNER JOIN {prefix}taxonomy_terms USING(vid)';

		#
		#
		#

		$scope = $hook->params['scope'];

		if ($scope)
		{
			$where[] = 'scope = ?';
			$params[] = $scope;

			$inner .= ' INNER JOIN {prefix}taxonomy_vocabulary_scope USING(vid)';
		}

		$vocabulary = $hook->params['vocabulary'];

		if ($vocabulary)
		{
			if (is_numeric($vocabulary))
			{
				$where[] = 'vid = ?';
				$params[] = $vocabulary;
			}
			else
			{
				$where[] = '(vocabulary = ? OR vocabularyslug = ?)';
				$params[] = $vocabulary;
				$params[] = $vocabulary;
			}
		}

		$term = $hook->params['term'];

		if ($term)
		{
			if (is_numeric($term))
			{
				$where[] = 'vtid = ?';
				$params[] = $term;
			}
			else
			{
				$where[] = '(term = ? OR termslug = ?)';
				$params[] = $term;
				$params[] = $term;
			}
		}

		//$where = $where ? 'WHERE ' . implode(' AND ', $where) : null;

		#
		#
		#

		/*

		$entries = self::load
		(
			$hook, $patron, $inner, $where, $params
		);

		$ids = array();

		foreach ($entries as $entry)
		{
			$ids[$entry->nid] = $entry;
		}
		*/

		$terms = self::model()->select('*', $inner . ($where ? ' WHERE ' . implode(' AND ', $where) : ''), $params)->fetchAll();

		if ($term)
		{
			$patron->context['self']['terms'] = $terms;
		}

		$patron->context['self']['vocabulary'] = array_shift($terms);

		$inner .= ' INNER JOIN {prefix}taxonomy_terms_nodes USING(vtid)';
		$inner .= ' INNER JOIN {prefix}system_nodes USING(nid)';

		$where[] = 'is_online = 1';

		if ($scope)
		{
			$where[] = 'constructor = ?';
			$params[] =  $scope;
		}

		$ids = self::model()->select('nid', $inner . ($where ? ' WHERE ' . implode(' AND ', $where) : ''), $params)->fetchAll(PDO::FETCH_COLUMN);

		if (empty($ids))
		{
			return;
		}

		#
		#
		#

		if ($scope)
		{
			$query = 'WHERE nid IN(' . implode(',', $ids) . ')';
			$order = null;

			if ($hook->params['by'])
			{
				$order = ' ORDER BY ' . $hook->params['by'] . ' ' . $hook->params['order'];
			}

			$limit = $hook->params['limit'];

			if ($limit)
			{
				$count = self::model($scope)->count(null, null, $query);
				$page = isset($hook->params['page']) ? $hook->params['page'] : 0;

				$entries = self::model($scope)->loadRange
				(
					$page * $limit, $limit, $query . $order
				)
				->fetchAll();
			}
			else
			{
				$entries = self::model($scope)->loadAll($query . $order)->fetchAll();

				$count = count($entries);
				$limit = null;
				$page = null;
			}

			$patron->context['self']['range'] = array
			(
				'count' => $count,
				'limit' => $limit,
				'page' => $page
			);

			return $patron->publish($template, $entries);
		}
		else
		{
			WdDebug::trigger('Multiple scopes is not ready yet');

			$constructors = $core->db()->query
			(
				'SELECT constructor, nid FROM {prefix}system_nodes WHERE nid IN (' . implode(', ', array_keys($ids)) . ')'
			)
			->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_GROUP);

			foreach ($constructors as $constructor => $n_ids)
			{
				$nodes = $core->getModule($constructor)->model()->loadAll
				(
					'WHERE is_online = 1 AND nid IN(' . implode(', ', $n_ids) . ')'
				)
				->fetchAll();

				foreach ($nodes as $node)
				{
	//				$ids[$node->nid]->node = $node;
					$ids[$node->nid] = $node;
				}
			}

			return $patron->publish($template, array_values($ids));
		}
	}

	static protected function load(WdHook $hook, WdPatron $patron, $query, $where, $params)
	{
		#
		# build query
		#

		$query .= $where ? ' WHERE ' . implode(' AND ', $where) : null;

		$order = null;

		if (isset($hook->params['by']))
		{
			$order = ' ORDER BY ' . $hook->params['by'];

			if (isset($hook->params['order']))
			{
				$order .= ' ' . $hook->params['order'];
			}
		}

		#
		# load
		#

		global $core;

		$page = isset($hook->params['page']) ? $hook->params['page'] : 0;
		$limit = $hook->params['limit'];

		if ($limit)
		{
			$entries = self::model()->loadRange($page * $limit, $limit, $query . $order, $params);
			$count = self::model()->count(null, null, $query, $params);
		}
		else
		{
			$entries = self::model()->loadAll($query . $order, $params);
			$count = count($entries);
		}

		$entries = $entries->fetchAll();

		$patron->context['self']['range'] = array
		(
			'count' => $count,
			'limit' => $limit,
			'page' => $page
		);

		return $entries;
	}
}