<?php

class taxonomy_terms_WdManager extends WdManager
{
	public function __construct($module, array $tags=array())
	{
		parent::__construct
		(
			$module, $tags += array
			(
				self::T_KEY => 'vtid'
			)
		);
	}

	protected function columns()
	{
		return array
		(
			'term' => array
			(
				self::COLUMN_LABEL => 'Name'
			),

			'vocabulary' => array
			(
				self::COLUMN_LABEL => 'Vocabulary',
				self::COLUMN_HOOK => array(__CLASS__, 'select_callback')
			),

			'popularity' => array
			(
				self::COLUMN_LABEL => 'Popularity',
				self::COLUMN_HOOK => array(__CLASS__, 'select_callback')
			)
		);
	}

	protected function loadRange($offset, $limit, array $where, $order, array $params)
	{
		$query = $where ? ' WHERE ' . implode(' AND ', $where) : '';
		$query .= ' ' . $order;

		return $this->model->query
		(
			'SELECT *,
			(select count(s1.nid) from {self}_nodes as s1 where s1.vtid = t1.vtid) AS `popularity`
			FROM {self} AS t1
			INNER JOIN {prefix}taxonomy_vocabulary USING(vid) ' . $query . " LIMIT $offset, $limit",

			$params
		)
		->fetchAll(PDO::FETCH_OBJ);
	}

	protected function get_cell_term($entry, $key)
	{
		$label = $entry->term;

		if ($label != $entry->termslug)
		{
			$label .= ' <small>(' . $entry->termslug . ')</small>';
		}

		return self::modify_code($label, $entry->vtid, $this);
	}
}