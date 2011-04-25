<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class WdAdjustNodeWidget extends WdWidget
{
	const T_CONSTRUCTOR = '#adjust-constructor';

	public function __construct($tags=array(), $dummy=null)
	{
		global $core;

		parent::__construct
		(
			'div', $tags + array
			(
				self::T_CONSTRUCTOR => 'system.nodes',

				'class' => 'adjust'
			)
		);

		$this->dataset['adjust'] = 'adjust-node';

		$document = $core->document;

		$document->css->add('adjust-node.css');
		$document->js->add('adjust-node.js');
	}

	protected function getInnerHTML()
	{
		global $core;

		$rc = parent::getInnerHTML();
		$constructor = $this->get(self::T_CONSTRUCTOR);

		$rc .= '<div class="search">';
		$rc .= '<input type="text" class="search" data-placeholder="' . t('Search') . '" />';
		$rc .= $this->get_results(array('selected' => $this->get('value')), $constructor);
		$rc .= '</div>';

		$this->dataset['constructor'] = $constructor;

		return $rc;
	}

	public function get_results(array $options=array(), $constructor='system.nodes')
	{
		$options += array
		(
			'page' => null,
			'search' => null,
			'selected' => null
		);

		list($records, $range) = $this->get_records($constructor, $options);

		$rc = $records ? $this->format_records($records, $range, $options) : $this->get_placeholder($options);

		return '<div class="results">' . $rc . '</div>';
	}

	protected function get_records($constructor, array $options, $limit=10)
	{
		global $core;

		$model = $core->models[$constructor];

		if ($constructor == 'system.nodes')
		{
			$query = new WdActiveRecordQuery($model);
		}
		else
		{
			$query = $model->find_by_constructor($constructor);
		}

		$search = $options['search'];

		if ($search)
		{
			$conditions = '';
			$conditions_args = array();
			$words = explode(' ', trim($options['search']));
			$words = array_map('trim', $words);

			foreach ($words as $word)
			{
				$conditions .= ' AND title LIKE ?';
				$conditions_args[] = '%' . $word . '%';
			}

			$query->where(substr($conditions, 4), $conditions_args);
		}

		$query->visible;

		$count = $query->count;
		$page = $options['page'];
		$selected = $options['selected'];

		if ($selected && $page === null)
		{
			$ids = $query->select('nid')->order('modified DESC')->all(PDO::FETCH_COLUMN);
			$positions = array_flip($ids);
			$pos = isset($positions[$selected]) ? $positions[$selected] : 0;
			$page = floor($pos / $limit);
			$ids = array_slice($ids, $page * $limit, $limit);
			$records = $ids ? $model->find($ids) : null;
		}
		else
		{
			$records = $query->order('modified DESC')->limit($page * $limit, $limit)->all;
		}

		return array
		(
			$records, array
			(
				WdPager::T_COUNT => $count,
				WdPager::T_LIMIT => $limit,
				WdPager::T_POSITION => $page
			)
		);
	}

	protected function format_records($records, array $range, array $options)
	{
		$selected = $options['selected'];

		$rc = '<ul>';

		foreach ($records as $record)
		{
			$rc .= $this->format_record($record, $selected, $range, $options);
		}

		$n = count($records);
		$limit = $range[WdPager::T_LIMIT];

		if ($n < $limit)
		{
			$rc .= str_repeat('<li class="empty">&nbsp;</li>', $limit - $n);
		}

		$rc .= '</ul>';

		$rc .= new system_nodes_adjust_WdPager
		(
			'div', $range + array
			(
				'class' => 'pager'
			)
		);

		return $rc;
	}

	protected function format_record(system_nodes_WdActiveRecord $record, $selected, array $range, array $options)
	{
		$recordid = $record->nid;

		return new WdElement
		(
			'li', array
			(
				WdElement::T_INNER_HTML => wd_shorten($record->title),
				WdElement::T_DATASET => array
				(
					Node::NID => $recordid,
					Node::TITLE => $record->title
				),

				'class' => $recordid == $selected ? 'selected' : null
			)
		);
	}

	protected function get_placeholder(array $options)
	{
		$search = $options['search'];

		return '<p class="no-response">' .

		(
			$search
			? t('Aucun enregistrement ne correspond aux termes de recherche spécifiés (%search)', array('%search' => $search))
			: t("Il n'y a pas d'enregistrements")
		)

		. '</p>';
	}
}