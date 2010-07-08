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

			'vid' => array
			(
				self::COLUMN_LABEL => 'Vocabulary'
			),

			'popularity' => array
			(
				self::COLUMN_LABEL => 'Popularity'
			)
		);
	}

	protected function loadRange($offset, $limit, array $where, $order, array $params)
	{
		$query = $where ? ' WHERE ' . implode(' AND ', $where) : '';

		if ($this->get(self::BY) == 'vid')
		{
			$order = 'ORDER BY `vocabulary` ' . $this->get(self::ORDER);
		}
		else if ($this->get(self::BY) == 'popularity')
		{
			$order = 'ORDER BY (select count(s1.nid) from {self}_nodes as s1 where s1.vtid = t1.vtid) ' . $this->get(self::ORDER);
		}

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
		/*
		if ($label != $entry->termslug)
		{
			$label .= ' <small>(' . $entry->termslug . ')</small>';
		}
		*/
		return self::modify_code($label, $entry->vtid, $this);
	}

	protected function get_cell_vid($entry, $tag)
	{
		return parent::select_code($tag, $entry->$tag, $entry->vocabulary, $this);
	}
}