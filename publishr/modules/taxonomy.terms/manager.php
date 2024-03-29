<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
				'label' => 'Name'
			),

			'vid' => array
			(
				'label' => 'Vocabulary'
			),

			'popularity' => array
			(
				'label' => 'Popularity'
			)
		);
	}

	protected function update_options(array $options, array $modifiers)
	{
		$options = parent::update_options($options, $modifiers);

		if (isset($modifiers['by']) && $modifiers['by'] == 'popularity')
		{
			$options['order_by'] = 'popularity';
			$options['order_direction'] = strtolower($modifiers['order']) == 'desc' ? 'desc' : 'asc';
		}

		return $options;
	}

	protected function alter_range_query(WdActiveRecordQuery $query, array $options)
	{
		$order = $options['order'];

		if (isset($order['vid']))
		{
			$query->order('vocabulary ' . ($order['vid'] < 0 ? 'desc' : 'asc'));
		}
		else if (isset($order['popularity']))
		{
			$query->order('popularity ' . ($order['popularity'] < 0 ? 'desc' : 'asc'));
		}

		$query->select('*, (select count(s1.nid) from {self}_nodes as s1 where s1.vtid = term.vtid) AS `popularity`');
		$query->mode(PDO::FETCH_CLASS, 'taxonomy_terms_WdActiveRecord');

		return parent::alter_range_query($query, $options);
	}

	protected function get_cell_term($record, $property)
	{
		$label = $record->term;
		/*
		if ($label != $entry->termslug)
		{
			$label .= ' <small>(' . $entry->termslug . ')</small>';
		}
		*/
		return self::modify_code($label, $record->vtid, $this);
	}

	private $last_rendered_vid;

	protected function get_cell_vid($record, $property)
	{
		$vid = $record->vid;

		if ($this->last_rendered_vid === $vid)
		{
			return self::REPEAT_PLACEHOLDER;
		}

		$this->last_rendered_vid = $vid;

		return parent::render_filter_cell($record, $property, $record->vocabulary);
	}

	private $last_rendered_popularity;

	protected function get_cell_popularity($record, $property)
	{
		$popularity = $record->$property;

		if ($this->last_rendered_popularity === $popularity)
		{
			return self::REPEAT_PLACEHOLDER;
		}

		return $this->last_rendered_popularity = $popularity;
	}
}