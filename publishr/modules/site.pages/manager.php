<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class site_pages_WdManager extends system_nodes_WdManager
{
	public function __construct($module, $tags)
	{
		global $core;

		parent::__construct($module, $tags);

		$core->document->css->add('public/manage.css');
		$core->document->js->add('public/manage.js');
	}

	protected function columns()
	{
		return parent::columns() + array
		(
			'url' => array
			(
				'label' => null,
				'class' => 'url'
			),

			'is_navigation_excluded' => array
			(
				'label' => null,
				'class' => 'infos'
			)
		);
	}

	protected function jobs()
	{
		return parent::jobs() + array
		(
			'copy' => 'Copier'
		);
	}

	protected $mode = 'tree';
	protected $expand_highlight;

	/**
	 * Overrides the method to add support for expanded tree nodes.
	 *
	 * The methods adds the `expanded` option which is used to store expanded tree nodes. The
	 * option is initialized with first level pages.
	 *
	 * @see WdResume::retrieve_options()
	 */
	protected function retrieve_options($name)
	{
		global $core;

		$options = parent::retrieve_options($name) + array
		(
			'expanded' => array()
		);

		if (!$options['expanded'])
		{
			$options['expanded'] = $this->model->select('nid')->where('parentid = 0 AND siteid = ?', $core->site_id)->all(PDO::FETCH_COLUMN);
		}

		return $options;
	}

	protected function update_options(array $options, array $modifiers)
	{
		global $core;

		$options = parent::update_options($options, $modifiers);

		if (isset($modifiers['expand']) || isset($modifiers['collapse']))
		{
			$expanded = array_flip($options['expanded']);

			if (isset($modifiers['expand']))
			{
				$nid = $this->expand_highlight = filter_var($modifiers['expand'], FILTER_VALIDATE_INT);
				$expanded[$nid] = true;
			}

			if (isset($modifiers['collapse']))
			{
				unset($expanded[filter_var($modifiers['collapse'], FILTER_VALIDATE_INT)]);
			}

			$options['expanded'] = array_keys($expanded);
		}

		if (isset($options['order']['title']))
		{
			$options['order'] = array();
		}

		if ($options['filters'] || $options['order'] || $options['search'])
		{
			$this->mode = 'flat';
		}

		return $options;
	}

	protected function load_range(WdActiveRecordQuery $query)
	{
		global $core;

		if ($this->mode != 'tree')
		{
			return parent::load_range($query);
		}

		if ($this->options['expanded'])
		{
			$query->where('parentid = 0 OR parentid IN (' . implode(',', $this->options['expanded']) . ')');
		}

		$keys = $query->select('nid')->order('weight, created')->limit(null, null)->all(PDO::FETCH_COLUMN);

		if (!$keys)
		{
			return array();
		}

		$records = $this->model->find($keys);

		$tree = $this->model->nestNodes($records);

		$entries_by_ids = array();

		foreach ($tree as $entry)
		{
			$entries_by_ids[$entry->nid] = $entry;
		}

		$filtered = array();

		foreach ($tree as $entry)
		{
			if ($entry->parentid && empty($entries_by_ids[$entry->parentid]))
			{
				continue;
			}

			$filtered[] = $entry;
		}

		$records = self::flattenTree2($filtered);

		return $records;
	}

	static protected function flattenTree2($pages, $level=0)
	{
		$flatten = array();

		if (!is_array($pages))
		{
			throw new WdException('should be an array: \1', array($pages));
		}

		foreach ($pages as $page)
		{
			$page->level = $level;

			$flatten[] = $page;

			if (isset($page->children) && $page->children)
			{
				$flatten = array_merge($flatten, self::flattenTree2($page->children, $level + 1));
			}
		}

		return $flatten;
	}

	protected function getJobs()
	{
		$rc = '<div class="update" style="float: left"><button name="update">Enregistrer les modifications</button>&nbsp;</div>' . parent::getJobs();

		return $rc;
	}

	protected function getLimiter()
	{
		if ($this->mode == 'tree')
		{
			$rc  = '<div class="limiter"><span class="wdranger">';
			$rc .= '<select style="visibility: hidden"><option>&nbsp;</option></select>'; // to have the same height as the jobs div
			$rc .= 'De 1 à ' . count($this->entries) . ' sur ' . $this->count;
			$rc .= '</span></div>';

			return $rc;
		}

		return parent::getLimiter();
	}

	protected function render_body()
	{
		global $core;

		$view_ids = $this->module->model('contents')
		->select('pageid, content')
		->where('contentid = "body" AND editor = "view"')
		->pairs;

		$user = $core->user;
		$count = count($this->entries);

		$rc = '';

		foreach ($this->entries as $entry)
		{
			$class = 'entry draggable';

			$ownership = $user->has_ownership($this->module, $entry);

			if ($ownership === false)
			{
				$class .= ' no-ownership';
			}

			if ($this->expand_highlight && $entry->parentid == $this->expand_highlight)
			{
				$class .= ' volatile-highlight';
			}

			if (isset($view_ids[$entry->nid]))
			{
				$class .= ' view';
			}

			if ($entry->pattern)
			{
				$class .= ' pattern';
			}

			if ($entry->locationid)
			{
				$class .= ' location';
			}

			#
			# create rows, with a special 'even' class for even rows
			#

			$rc .= '<tr class="' . $class . '" id="nid:' . $entry->nid . '">';

			#
			# create user defined columns
			#

			foreach ($this->columns as $tag => $column)
			{
				$rc .= $this->render_cell($entry, $tag, $column);
			}

			$rc .= '</tr>';
		}

		return $rc;
	}

	protected function extend_column_is_navigation_excluded(array $column, $id)
	{
		return array
		(
			'filters' => array
			(
				'options' => array
				(
					'=1' => 'Excluded from navigation',
					'=0' => 'Included in navigation'
				)
			),

			'sortable' => false
		)

		+ parent::extend_column($column, $id);
	}

	protected function render_cell_is_navigation_excluded($record, $property)
	{
		$checkbox = new WdElement
		(
			WdElement::E_CHECKBOX, array
			(
				'class' => 'navigation',
				'checked' => !empty($record->is_navigation_excluded),
				'value' => $record->nid
			)
		);

		return <<<EOT
<label class="checkbox-wrapper navigation" title="Inclure ou exclure la page du menu de navigation principal">$checkbox</label>
EOT;
	}

	protected function render_cell_title($record, $property)
	{
		$rc = '';

		if ($this->mode == 'tree')
		{
			$rc .= str_repeat('<div class="indentation">&nbsp;</div>', $record->depth);
			$rc .= '<div class="handle">&nbsp;</div>';

			if (0)
			{
				$rc .= new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdElement::T_LABEL => 'w',
						WdElement::T_LABEL_POSITION => 'before',
						'name' => 'weights[' . $record->nid . ']',
						'value' => $record->weight,
						'size' => 3,
						'style' => 'border: none; background: transparent; color: green'
					)
				);

				$rc .= '&nbsp;';

				$rc .= new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdElement::T_LABEL => 'p',
						WdElement::T_LABEL_POSITION => 'before',
						'name' => 'parents[' . $record->nid . ']',
						'value' => $record->parentid,
						'size' => 3,
						'style' => 'border: none; background: transparent; color: green'
					)
				);
			}
			else
			{
				/*
				$rc .= new WdElement
				(
					WdElement::E_HIDDEN, array
					(
						'name' => 'weights[' . $entry->nid . ']',
						'value' => $entry->weight
					)
				);

				$rc .= '&nbsp;';
				*/

				$rc .= new WdElement
				(
					WdElement::E_HIDDEN, array
					(
						'name' => 'parents[' . $record->nid . ']',
						'value' => $record->parentid
					)
				);
			}
		}

		$rc .= self::modify_code(wd_entities($record->title), $record->nid, $this);

		if (0)
		{
			$rc .= ' <small style="color: green">:' . $record->nid . '</small>';
		}

		if ($this->mode == 'tree' && isset($record->depth) && $record->depth > 0 && $record->has_child)
		{
			$expanded = in_array($record->nid, $this->options['expanded']);

			$rc .= ' <a class="ajaj treetoggle" href="?' . ($expanded ? 'collapse' : 'expand') . '=' . $record->nid . '">' . ($expanded ? '-' : '+' . $record->child_count) . '</a>';
		}

		#
		# modified
		#

		$now = time();
		$modified = strtotime($record->modified);

		if ($now - $modified < 60 * 60 * 2)
		{
			$rc .= ' <sup style="vertical-align: text-top; color: red;">Récemment modifié</sup>';
		}

		return $rc;
	}

	/*
	protected function render_cell_infos($entry)
	{
		$rc = '<label class="checkbox-wrapper navigation" title="Inclure ou exclure la page du menu de navigation principal">';

		$rc .= new WdElement
		(
			WdElement::E_CHECKBOX, array
			(
				'class' => 'navigation',
				'checked' => !empty($entry->is_navigation_excluded),
				'value' => $entry->nid
			)
		);

		$rc .= '</label>';

		#
		#
		#

		return $rc;
	}
	*/

	protected function render_cell_url($record)
	{
		global $core;

		$t = $this->t;
		$options = $this->options;

		$rc = '';
		$pattern = $record->url_pattern;

		if ($options['search'] || $options['filters'])
		{
			if (WdRoute::is_pattern($pattern))
			{
				return;
			}

			$url = $record->url;

			// DIRTY-20100507

			if ($record->location)
			{
				$location = $record->location;

				$rc .= '<a class="location" title="' . $t('This page is redirected to: !title (!url)', array('!title' => $location->title, '!url' => $location->url)) . '">&nbsp;</a>';
				$rc .= '<span class="small"><a href="' . $url . '" class="left">' . $url . '</a></span>';

				return $rc;
			}

			$rc .= '<span class="small"><a href="' . $url . '" class="out left">' . $url . '</a></span>';

			return $rc;
		}

		$location = $record->location;

		if ($location)
		{
			$rc .= '<a class="location" title="' . $t('This page is redirected to: !title (!url)', array('!title' => $location->title, '!url' => $location->url)) . '">&nbsp;</a>';
		}
		else if (!WdRoute::is_pattern($pattern))
		{
			$url = ($core->site_id == $record->siteid) ? $record->url : $record->absolute_url;

			$title = $t('Go to the page: !url', array('!url' => $url));

			$rc .= '<a href="' . $url . '" class="view" title="' . $title . '">' . '&nbsp;' . '</a>';
		}

		return $rc;
	}
}