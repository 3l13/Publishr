<?php

class site_pages_WdManager extends system_nodes_WdManager
{
	public function __construct($module, $tags)
	{
		parent::__construct($module, $tags);

		global $document;

		$document->addStyleSheet('public/manage.css');
		$document->addJavascript('public/manage.js');
	}

	protected function columns()
	{
		return parent::columns() + array
		(
			'i18n' => array
			(
				self::COLUMN_LABEL => null,
				self::COLUMN_CLASS => 'i18n'
			),

			'url' => array
			(
				self::COLUMN_LABEL => null,
				self::COLUMN_CLASS => 'url'
			),

			'infos' => array
			(
				self::COLUMN_LABEL => null,
				self::COLUMN_CLASS => 'infos'
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

	protected function loadRange($offset, $limit, array $where, $order, array $params)
	{
		# i18n

		//$where[] = 'tnid = 0';

		//$where[] = 'language = "fr"';

		# /i18n


		$query = $where ? ' WHERE ' . implode(' AND ', $where) : '';

		$entries = $this->model->loadRange($offset, null, $query . ' ORDER BY weight, created', $params)->fetchAll();

		$tree = self::entriesTreefy($entries);

		$flatten = $this->module->flattenTree2($tree);

		return $flatten;
	}

	/*
	 * La transformation en arbre est assez simple si l'on se sert du référencement
	 * des objets.
	 */

	static function entriesTreefy($entries)
	{
		#
		# we need to build an array of parents so that the key can be used as parentid
		#

		$parents = array();

		foreach ($entries as $entry)
		{
			$entry->children = array();

			$parents[$entry->nid] = $entry;
		}

		#
		#
		#

		$tree = array();

		foreach ($parents as $entry)
		{
			if (!$entry->parentid || empty($parents[$entry->parentid]))
			{
				$tree[] = $entry;

				continue;
			}

			$parents[$entry->parentid]->children[] = $entry;
		}

		return $tree;
	}

	protected function getJobs()
	{
		$rc = '<div class="update" style="float: left"><button name="update">Enregistrer les modifications</button>&nbsp;</div>' . parent::getJobs();

		return $rc;
	}

	protected function getLimiter()
	{
		return;
	}

	protected function getContents()
	{
		global $user;

		$rc = '';
		$count = count($this->entries);

		foreach ($this->entries as $i => $entry)
		{
			$class = 'entry draggable';

			$ownership = $user->hasOwnership($this->module, $entry);

			if ($ownership === false)
			{
				$class .= ' no-ownership';
			}

			if ($i + 1 == $count)
			{
				$class .= ' last';
			}

			#
			# create rows, with a special 'even' class for even rows
			#

			$rc .= '<tr class="' . $class . '" id="nid:' . $entry->nid . '">';

			#
			# if the id tag was provided, we had a column for the ids
			#

			$rc .= $this->get_cell_key($entry, $entry->nid);

			#
			# create user defined columns
			#

			foreach ($this->columns as $tag => $opt)
			{
				$rc .= $this->get_cell($entry, $tag, $opt) . PHP_EOL;
			}

			#
			# end row
			#

			$rc .= '</tr>';
		}

		return $rc;
	}

	protected function get_cell_title($entry, $tag)
	{
		$sortable = empty($this->tags[self::WHERE]) && empty($this->tags[self::SEARCH]);
		$rc = '';

		#
		#
		#

		if ($sortable)
		{
			$rc .= str_repeat('<div class="indentation">&nbsp;</div>', $entry->level);
			$rc .= '<div class="handle">&nbsp;</div>';

			if (0)
			{
				$rc .= new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdElement::T_LABEL => 'w',
						WdElement::T_LABEL_POSITION => 'left',
						'name' => 'weights[' . $entry->nid . ']',
						'value' => $entry->weight,
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
						WdElement::T_LABEL_POSITION => 'left',
						'name' => 'parents[' . $entry->nid . ']',
						'value' => $entry->parentid,
						'size' => 3,
						'style' => 'border: none; background: transparent; color: green'
					)
				);
			}
			else
			{
				$rc .= new WdElement
				(
					WdElement::E_HIDDEN, array
					(
						'name' => 'weights[' . $entry->nid . ']',
						'value' => $entry->weight
					)
				);

				$rc .= '&nbsp;';

				$rc .= new WdElement
				(
					WdElement::E_HIDDEN, array
					(
						'name' => 'parents[' . $entry->nid . ']',
						'value' => $entry->parentid
					)
				);
			}
		}

		$title = wd_entities($entry->title);

		if (strpos($entry->pattern, '<') !== false)
		{
			$title = '<span class="preg">' . $title . '</span>';
		}

		/*

		$location = $entry->location;

		if ($location)
		{
			$rc .= '<span class="location" title="Cette page est redirigée vers&nbsp;: ' . wd_entities($location->title) . ' (' . $location->url . ')">&nbsp;</span>';
		}
		else if (strpos($entry->urlPattern, '<') === false)
		{
			$rc .= '<a href="' . WdRoute::encode('/' . $this->module . '/' . $entry->nid . '/view') . '" class="view" title="Aller à la page">Aller à la page</a>';
		}
		else
		{
			$rc .= '<span class="place-holder">&nbsp;</span>';
		}

		$rc .= ' ';

		*/


		$rc .= WdResume::modify_code($title, $entry->nid, $this);

		if ($entry->language)
		{
			$rc .= ' <span class="language">:' . $entry->language . '</span>';
		}

		if (0)
		{
			$rc .= ' <small style="color: green">:' . $entry->nid . '</small>';
		}

		//$rc .= ' <small><a href="' . WdRoute::encode('/' . $this->module . '/' . $entry->nid . '/view') . '" class="view" target="_blank">Voir la page</a></small>';

		return $rc;
	}

	protected function get_cell_infos($entry)
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

	protected function get_cell_i18n($entry)
	{
		$entries = null;

		if ($entry->tnid)
		{
			$native = $entry->native;

			if ($native)
			{
				$entries = array($native->nid => $native->language);
			}
		}
		else
		{
			$entries = $this->model->select
			(
				array('nid', 'language'), 'WHERE tnid = ? ORDER BY language', array
				(
					$entry->nid
				)
			)
			->fetchPairs();
		}

		if (!$entries)
		{
			return '&nbsp;';
		}

		$rc = array();

		foreach ($entries as $nid => $language)
		{
			$rc[] = '<a href="' . WdRoute::encode('/' . $this->module . '/' . $nid . '/edit') . '">' . $language . '</a>';
		}

		return '<span class="translations">[' . implode(', ', $rc) . ']</span>';
	}

	protected function get_cell_url($entry)
	{
		$pattern = $entry->urlPattern;

		if ($this->getTag(self::SEARCH) || $this->getTag(self::WHERE))
		{
			if (strpos($pattern, '<') !== false)
			{
				return;
			}

			return '<span class="small"><a href="' . $entry->url . '" class="out left">' . $entry->url . '</a></small>';
		}

		$rc = '';

		$location = $entry->location;

		if ($location)
		{
			$rc .= '<a class="location" title="Cette page est redirigée vers&nbsp;: ' . wd_entities($location->title) . ' (' . $location->url . ')">&nbsp;</a>';
		}
		else if (strpos($pattern, '<') === false)
		{
			$url = $entry->url;

			$title = t('Aller à la page : !url', array('!url' => $url));

			$rc .= '<a href="' . $url . '" class="view" title="' . $title . '">' . $title . '</a>';
		}

		return $rc;
	}
}