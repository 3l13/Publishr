<?php

class site_menus_WdModule extends system_nodes_WdModule
{
	const OPERATION_ADD = 'add';
	const OPERATION_SEARCH = 'search';

	protected function getOperationsAccessControls()
	{
		return parent::getOperationsAccessControls() + array
		(
			self::OPERATION_SEARCH => array
			(
				self::CONTROL_AUTHENTICATED => true,
				self::CONTROL_VALIDATOR => false
			)
		);
	}

	protected function operation_save(WdOperation $operation)
	{
		$rc = parent::operation_save($operation);

		if ($rc)
		{
			$nid = $rc['key'];
			$params = &$operation->params;

			$songsModel = $this->model('pages');

			$songsModel->execute
			(
				'DELETE FROM {self} WHERE menuid = ?', array
				(
					$nid
				)
			);

			if (isset($params['nodes']))
			{
				$pages = $params['nodes'];

				$weight = 0;

				foreach ($pages as $pageid)
				{
					$songsModel->insert
					(
						array
						(
							'menuid' => $nid,
							'pageid' => $pageid,
							'weight' => $weight++
						)
					);
				}
			}
		}

		return $rc;
	}

	protected function validate_operation_add(WdOperation $operation)
	{
		$params = &$operation->params;

		if (empty($params['nid']))
		{
			return false;
		}

		global $core;

		$nid = $params['nid'];
		$node = $core->getModule('site.pages')->model()->load($nid);

		if (!$node)
		{
			wd_log_error('Unknown entry: %nid', array('%nid' => $nid));

			return false;
		}

		$operation->entry = $node;

		return true;
	}

	protected function operation_add(WdOperation $operation)
	{
		return $this->createEntry($operation->entry);
	}

	/*
	protected function operation_search(WdOperation $operation)
	{
		return $this->createResults($operation->params);
	}

	protected function createResults(array $options=array())
	{
		global $core;

		$options += array
		(
			'page' => 0,
			'limit' => 10,
			'search' => null
		);

		$pagesModel = $core->getModule('site.pages')->model();

		#
		# search
		#

		$page = $options['page'];
		$limit = $options['limit'];

		$where = array('pattern NOT LIKE "%<%"');
		$params = array();

		if ($options['search'])
		{
			$concats = array();

			$words = explode(' ', $options['search']);
			$words = array_map('trim', $words);

			foreach ($words as $word)
			{
				$where[] = 'title LIKE ?';
				$params[] = '%' . $word . '%';
			}
		}

		$where = ' WHERE ' . implode(' AND ', $where);

		$count = $pagesModel->count(null, null, $where, $params);

		$rc = '<div id="song-results">';

		if ($count)
		{
			$entries = $pagesModel->loadRange
			(
				$page * $limit, $limit, $where . ' ORDER BY title', $params
			);

			$rc .= '<ul class="song results">';

			foreach ($entries as $entry)
			{
				$rc .= '<li class="song result" id="sr:' . $entry->nid . '">';
				$rc .= '<button class="add" type="button" onclick="pl.add(\'' . $entry->nid . '\')" title="Ajouter au menu">+</button>';
				$rc .= ' ' . wd_entities($entry->title);
				$rc .= ' <span class="small">&ndash; ' . $entry->url . '</span>';
				$rc .= '</li>' . PHP_EOL;
			}

			$rc .= '</ul>';

			$rc .= new site_menus_WdPager
			(
				'div', array
				(
					WdPager::T_COUNT => $count,
					WdPager::T_LIMIT => $limit,
					WdPager::T_POSITION => $page,
					WdPager::T_URLBASE => 'javascript://',

					'class' => 'pager'
				)
			);
		}
		else
		{
			$rc .= '<p class="no-response">' . t('Aucune page ne correspond aux termes de recherche spécifiés (%search)', array('%search' => $options['search'])) . '</p>';
		}

		$rc .= '</div>';

		return $rc;
	}
	*/

	protected function createEntry($node)
	{
		$rc  = '<li class="song sortable">';
		$rc .= '<input type="hidden" name="nodes[]" value="' . $node->nid . '" />';
		$rc .= '<button type="button" class="remove" onclick="pl.remove(this)" title="Retirer du menu">-</button>';
		$rc .= ' ' . wd_entities($node->title);
		$rc .= ' <span class="small">&ndash; ' . $node->url . '</span>';
		$rc .= '</li>';

		return $rc;
	}

	protected function block_manage()
	{
		return new site_menus_WdManager
		(
			$this, array
			(
				WdManager::T_COLUMNS_ORDER => array
				(
					'title', 'uid', 'is_online'
				)
			)
		);
	}

	protected function block_edit(array $properties, $permission)
	{
		global $document;

		$document->addStyleSheet('public/edit.css');
		$document->addJavaScript('public/edit.js');

		global $core;

		$pagesModel = $core->getModule('site.pages')->model();

		#
		# results
		#

		//$results = $this->createResults();
		$results = $core->getModule('site.pages')->getBlock('adjustResults');

		#
		# songs
		#

		$songs = null;

		if ($properties[Node::NID])
		{
			$entries = $pagesModel->loadAll
			(
				'INNER JOIN {prefix}site_menus_pages AS jn ON nid = pageid
				WHERE menuid = ? ORDER BY jn.weight, title', array
				(
					$properties[Node::NID]
				)
			);

			foreach ($entries as $entry)
			{
				$songs .= $this->createEntry($entry);
			}
		}

		return wd_array_merge_recursive
		(
			parent::block_edit($properties, $permission), array
			(
				WdElement::T_CHILDREN => array
				(
					Node::SLUG => new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'Slug',
							WdElement::T_GROUP => 'node'
						)
					),

					new WdElement
					(
						'div', array
						(
							WdElement::T_CHILDREN => array
							(
								'<div id="song-search">' .
								'<h4>Ajouter des pages</h4>' .
								'<div class="search">' .
								'<input type="text" class="search" />' .
								'</div>' .
								$results .
								'<div class="element-description">' .
								"Voici la liste de toutes les pages qui peuvent être utilisées
								pour composer votre menu. Cliquez sur le bouton
								<em>Ajouter au menu</em> pour ajouter des pages à
								votre menu. Utilisez le champ de recherche pour filter
								les pages selon leur titre." .
								' Rendez-vous dans le module <em>site.pages</em> pour
								<a href="' . WdRoute::encode('/site.pages') . '">gérer les pages</a>.</div>',
								'</div>',

								'<div id="playlist">' .
								'<h4>Menu</h4>' .
								'<ul class="song">' . $songs . '</ul>' .
								'<div class="element-description">Les pages ci-dessus forment
								votre menu. Vous pouvez ajouter d\'autres pages
								depuis le panneau <em>Ajouter des pages</em>, ou en retirer en
								cliquant sur le bouton <em>Retirer du menu</em> situé en
								tête de chaque page.</div>' .
								'</div>'
							)
						)
					)/*,

					'description' => new moo_WdEditorElement
					(
						array
						(
							WdForm::T_LABEL => 'Description'
						)
					)
					*/
				)
			)
		);
	}
}

class site_menus_WdPager extends WdPager
{
	protected function getURL($n)
	{
		return 'javascript:search.page(' . $n . ');';
	}
}