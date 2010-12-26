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

	protected function alter_range_query(WdActiveRecordQuery $query)
	{
		if ($this->get(self::BY) == 'vid')
		{
			$query->order('vocabulary ' . $this->get(self::ORDER));
		}
		else if ($this->get(self::BY) == 'popularity')
		{
			$query->order('(select count(s1.nid) from {self}_nodes as s1 where s1.vtid = term.vtid)' . $this->get(self::ORDER));
		}

		$query->select('*, (select count(s1.nid) from {self}_nodes as s1 where s1.vtid = term.vtid) AS `popularity`');
		$query->mode(PDO::FETCH_CLASS, 'taxonomy_terms_WdActiveRecord');

		return parent::alter_range_query($query);
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