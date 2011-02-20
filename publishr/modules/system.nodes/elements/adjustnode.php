<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class WdAdjustNodeElement extends WdElement
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

				'class' => 'widget-adjust-node adjust'
			)
		);

		$this->dataset['adjust'] = 'adjustnode';

		$document = $core->document;

		$document->css->add('adjustnode.css');
		$document->js->add('adjustnode.js');
	}

	protected function getInnerHTML()
	{
		global $core;

		$rc = parent::getInnerHTML();
		$constructor = $this->get(self::T_CONSTRUCTOR);

		$rc .= '<div class="search">';
		$rc .= '<input type="text" class="search" />';
		$rc .= $this->get_results($constructor, array('selected' => $this->get('value')));
		$rc .= '</div>';

		$this->dataset['constructor'] = $constructor;

		return $rc;
	}

	protected function get_results($constructor, array $options=array())
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

		$query->where('(siteid = 0 OR siteid = ?) AND (language = "" OR language = ?)', $core->site->siteid, $core->site->language);

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

	static public function operation_get(WdOperation $operation)
	{
		global $core;

		$params = &$operation->params;

		$el = (string) new WdAdjustNodeElement
		(
			array
			(
				self::T_CONSTRUCTOR => $params['constructor'],
				'value' => isset($params['selected']) ? $params['selected'] : null
			)
		);

		$document = $core->document;

		$operation->response->assets = array
		(
			'css' => $document->css->get(),
			'js' => $document->js->get()
		);

		return $el;
	}

	static public function operation_results(WdOperation $operation)
	{
		$params = &$operation->params;

		$el = new WdAdjustNodeElement
		(
			array
			(
				'value' => isset($params['selected']) ? $params['selected'] : null
			)
		);

		return $el->get_results($params['constructor'], $_GET);
	}

	static public function operation_popup(WdOperation $operation)
	{
		$params = &$operation->params;

		$el = (string) new WdAdjustImageElement
		(
			array
			(
				'value' => isset($params['selected']) ? $params['selected'] : null
			)
		);

		$label_cancel = t('label.cancel');
		$label_use = t('label.use');
		$label_remove = t('label.remove');

		return <<<EOT
<div class="popup">

$el

<div class="confirm">
<button type="button" class="cancel">$label_cancel</button>
<button type="button" class="none warn">$label_remove</button>
<button type="button" class="continue">$label_use</button>
</div>

<div class="arrow"><div>&nbsp;</div></div>

</div>
EOT;
	}
}