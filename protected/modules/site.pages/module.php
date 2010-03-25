<?php

class site_pages_WdModule extends system_nodes_WdModule
{
	const OPERATION_COPY = 'copy';
	const OPERATION_NAVIGATION_INCLUDE = 'navigationInclude';
	const OPERATION_NAVIGATION_EXCLUDE = 'navigationExclude';
	const OPERATION_UPDATE_TREE = 'updateTree';

	protected function getOperationsAccessControls()
	{
		return array
		(
			self::OPERATION_COPY => array
			(
				self::CONTROL_PERMISSION => PERMISSION_CREATE,
				self::CONTROL_ENTRY => true,
				self::CONTROL_VALIDATOR => false
			),

			self::OPERATION_NAVIGATION_INCLUDE => array
			(
				self::CONTROL_PERMISSION => PERMISSION_MAINTAIN,
				self::CONTROL_OWNERSHIP => true,
				self::CONTROL_VALIDATOR => false
			),

			self::OPERATION_NAVIGATION_EXCLUDE => array
			(
				self::CONTROL_PERMISSION => PERMISSION_MAINTAIN,
				self::CONTROL_OWNERSHIP => true,
				self::CONTROL_VALIDATOR => false
			),

			self::OPERATION_UPDATE_TREE => array
			(
				self::CONTROL_PERMISSION => PERMISSION_ADMINISTER,
				self::CONTROL_VALIDATOR => false
			)
		)

		+ parent::getOperationsAccessControls();
	}

	protected function validate_operation_save(WdOperation $operation)
	{
		if (isset($operation->params['contents']))
		{
			foreach ($operation->params['contents'] as $contentsId => &$contents)
			{
//				wd_log('contents: \1 \2', array($contentsId, $contents));

				$class = $contents['editor'] . '_WdEditorElement';

				$contents['contents'] = call_user_func(array($class, 'toContents'), $contents);

//				wd_log('toContents: \1', array($contents['contents']));
			}
		}

		return parent::validate_operation_save($operation);
	}

	protected function operation_save(WdOperation $operation)
	{
		$entry = null;
		$oldurl = null;

		if ($operation->entry)
		{
			$entry = $operation->entry;
			$pattern = $entry->urlPattern;

			if (strpos($pattern, '<') === false)
			{
				$oldurl = $pattern;
			}
		}

		#
		#
		#

		$operation->handleBooleans(array(Page::IS_NAVIGATION_EXCLUDED));

		$params = &$operation->params;

		#
		# weight
		#

		if (!$operation->key && empty($params[Page::WEIGHT]))
		{
			$params[Page::WEIGHT] = $this->model()->query
			(
				'SELECT MAX(weight) FROM {self} WHERE parentid = ?', array
				(
					isset($params[Page::PARENTID]) ? $params[Page::PARENTID] : 0
				)
			)
			->fetchColumnAndClose() + 1;
		}

		#
		#
		#

		$rc = parent::operation_save($operation);

		if (!$rc)
		{
			return $rc;
		}

		#
		# update contents
		#

		//wd_log('params: \1, response: \2', array($operation->params, $response));

		$nid = $rc['key'];

		if (isset($operation->params['contents']))
		{
			$contents_model = $this->model('contents');

			foreach ($operation->params['contents'] as $contents_id => $values)
			{
				#
				# clean the contents
				#

				$editor = $values['editor'];
				$contents = empty($values['contents']) ? null : $values['contents'];

				/*
				if ($editor == 'moo')
				{
					$contents = preg_replace('#\<font[^\>]*\>#', '', $contents);
					$contents = str_replace('</font>', '', $contents);
				}
				*/

				#
				# if there is no contents, the contents object is deleted
				#

				if (!$contents)
				{
					$contents_model->execute('DELETE FROM {self} WHERE pageid = ? AND contentsid = ?', array($nid, $contents_id));

					continue;
				}

				$contents_model->insert
				(
					array
					(
						'pageid' => $nid,
						'contentsid' => $contents_id
					)

					+ $values,

					array
					(
						'on duplicate' => $values
					)
				);
			}
		}

		#
		# trigger `site.pages.url.change` event
		#

		if ($entry && $oldurl)
		{
			$entry = $this->model()->load($entry->nid);
			$newurl = $entry->url;

			if ($oldurl != $newurl)
			{
				WdEvent::fire
				(
					'site.pages.url.change', array
					(
						'path' => array
						(
							$oldurl,
							$newurl
						),

						'entry' => $entry,
						'module' => $this
					)
				);
			}
		}


		return $rc;
	}

	protected function operation_query_copy(WdOperation $operation)
	{
		$entries = $operation->params['entries'];

		return array
		(
			'title' => 'Copy entries',
			'message' => t('Are you sure you want to copy the :count selected entries ?', array(':count' => count($entries))),
			'confirm' => array('Don\'t copy', 'Copy'),
			'params' => array
			(
				'entries' => $entries
			)
		);
	}

	protected function operation_copy(WdOperation $operation)
	{
		$entry = $operation->entry;
		$key = $operation->key;
		$title = $entry->title;

		unset($entry->nid);
		unset($entry->is_online);
		unset($entry->created);
		unset($entry->modified);

		$entry->uid = $operation->user->uid;
		$entry->title .= ' (copie)';
		$entry->slug .= '-copie';

		$contentsModel = $this->model('contents');
		$contents = $contentsModel->loadAll('WHERE pageid = ?', array($key))->fetchAll();

		$nid = $this->model()->save((array) $entry);

		if (!$nid)
		{
			wd_log_error('Unable to copy page %title (#:nid)', array('%title' => $title, ':nid' => $key));

			return;
		}

		wd_log_done('Page %title was copied to %copy', array('%title' => $title, '%copy' => $entry->title));

		foreach ($contents as $entry)
		{
			$entry->pageid = $nid;
			$entry = (array) $entry;

			$contentsModel->insert
			(
				$entry,

				array
				(
					'on duplicate' => $entry
				)
			);
		}

		return array($key, $nid);
	}

	protected function operation_navigationInclude(WdOperation $operation)
	{
		$entry = $operation->entry;
		$entry->is_navigation_excluded = false;
		$entry->save();

		return true;
	}

	protected function operation_navigationExclude(WdOperation $operation)
	{
		$entry = $operation->entry;
		$entry->is_navigation_excluded = true;
		$entry->save();

		return true;
	}

	protected function operation_updateTree(WdOperation $operation)
	{
		$parents = $operation->params['parents'];
		$weights = $operation->params['weights'];

		#
		# FIXME-20100201: weights are overwritten and serialized, this is because the weight
		# doesn't seam to be handled correctly on the client side.
		#

		$w = 0;

		foreach ($weights as $nid => &$weight)
		{
			$weight = $w++;
		}

		#
		#
		#

		$update = $this->model()->prepare('UPDATE {self} SET `parentid` = ?, `weight` = ? WHERE `{primary}` = ? LIMIT 1');

		foreach ($parents as $nid => $parentid)
		{
			$update->execute(array($parentid, $weights[$nid], $nid));
		}

		return true;
	}

	protected function block_manage()
	{
		return new site_pages_WdManager
		(
			$this, array
			(
				WdManager::T_COLUMNS_ORDER => array
				(
					'title', 'i18n', 'url', 'infos', 'uid', 'modified', 'is_online'
				),

				WdManager::T_ORDER_BY => null
			)
		);
	}

	protected function block_edit(array $properties, $permission)
	{
		global $document;

		$document->addJavascript('public/edit.js');

		#
		#
		#

		$nid = $properties[Node::NID];

		#
		# parentid
		#

		$parentid_el = null;

		if ($this->model()->query('SELECT count(nid) FROM {self}')->fetchColumnAndClose())
		{
			$parentid_el = new WdPageSelectorElement
			(
				'select', array
				(
					WdForm::T_LABEL => 'Page parente',
					WdElement::T_GROUP => 'structure',
					WdElement::T_OPTIONS_DISABLED => $nid ? array($nid) : null,
					WdElement::T_DESCRIPTION => "Les pages peuvent être organisées
					hiérarchiquement. Il n'y a pas de limites à la profondeur de l'arborescence."
				)
			);
		}

		#
		#
		#

		$contents = $this->block_edit_contents($properties);

		#
		# layouts
		#

		$path = '/protected/layouts';
		$layout_element = array();

		if (!is_dir($_SERVER['DOCUMENT_ROOT'] . $path))
		{
			$layout_element = new WdElement
			(
				'p', array
				(
					WdForm::T_LABEL => 'Gabarit',
					WdElement::T_MANDATORY => true,
					WdElement::T_INNER_HTML => t('The directory %path does not exists !', array('%path' => $path))
				)
			);
		}
		else
		{
			$layouts = $this->getLayouts();

			$layout_element = new WdElement
			(
				'select', array
				(
					WdForm::T_LABEL => 'Gabarit',
					WdElement::T_OPTIONS => array(null => '') + $layouts,
					WdElement::T_MANDATORY => true,
					WdElement::T_DESCRIPTION => t("Le gabarit définit un modèle de page dans lequel
					certains éléments sont modifiables (le contenu).")
				)
			);
		}

		$layout_element->setTag(WdElement::T_GROUP, 'contents');

		#
		# elements
		#

		return wd_array_merge_recursive
		(
			parent::block_edit($properties, $permission), array
			(
				WdElement::T_GROUPS => array
				(
					'structure' => array
					(
						'title' => 'Structure',
						'weight' => 20,
					),

					'contents' => array
					(
						'title' => 'Contenu',
						'weight' => 10
					),

					'advanced' => array
					(
						'title' => 'Options avancées',
						'weight' => 30
					)
				),

				WdElement::T_CHILDREN => array_merge
				(
					array
					(
						Page::LABEL => new WdElement
						(
							WdElement::E_TEXT, array
							(
								WdForm::T_LABEL => 'Étiquette de la page',
								WdElement::T_GROUP => 'node',
								WdElement::T_DESCRIPTION => "L'étiquette permet de remplacer le
								titre de la page utilisé pour créer les liens des menus ou du fil
								d'ariane, par une version plus concise."
							)
						),

						Page::PATTERN => new WdElement
						(
							WdElement::E_TEXT, array
							(
								WdForm::T_LABEL => 'Motif',
								WdElement::T_GROUP => 'node',
								WdElement::T_DESCRIPTION => "Le « motif » permet de redistribuer
								les paramètres d'une URL dynamique pour la transformer en URL
								sémantique."
							)
						),

						Page::PARENTID => $parentid_el,

						Page::IS_NAVIGATION_EXCLUDED => new WdElement
						(
							WdElement::E_CHECKBOX, array
							(
								WdElement::T_LABEL => 'Exclure la page de la navigation principale',
								WdElement::T_GROUP => 'structure'
							)
						),

						Page::LOCATIONID => new WdPageSelectorElement
						(
							'select', array
							(
								WdForm::T_LABEL => 'Redirection',
								WdElement::T_GROUP => 'advanced',
								WdElement::T_OPTIONS_DISABLED => $nid ? array($nid) : null,
								WdElement::T_DESCRIPTION => 'Redirection depuis cette page vers une autre URL.'
							)
						),

						Page::LAYOUT => $layout_element
					),

					$contents
				)
			)
		);
	}

	protected function block_edit_contents($properties)
	{
		if (empty($properties['layout']))
		{
			return array
			(
				new WdElement
				(
					'p', array
					(
						WdElement::T_GROUP => 'contents',
						WdElement::T_INNER_HTML => 'You need to choose a layout first to edit its contents.'
					)
				)
			);
		}

		$layout = $properties['layout'];
		$nid = $properties[Page::NID];

		// TODO: use $layout only, the path should be managed by the function

		$contents = $this->getLayoutEditables('/protected/layouts/' . $layout . '.html');
		$styles = $this->getLayoutStyleSheets('/protected/layouts/' . $layout . '.html');

		#
		#
		#

		$elements = array();

		global $core;

		$contents_model = $this->model('contents');

		foreach ($contents as $content)
		{
			$contents_id = $content['id'];
			$contents_title = $content['title'];

			$contents = $contents_model->loadRange
			(
				0, 1, 'WHERE pageid = ? AND contentsid = ?', array
				(
					$nid,
					$contents_id
				)
			)
			->fetchAndClose();

			$value = null;
			$editor = isset($content['editor']) ? $content['editor'] : null; // TODO: default editor

			if ($contents)
			{
				$value = $contents->contents;
				$editor = $contents->editor;
			}

			//wd_log('key: \1-\2, value: \3', array($nid, $contents_id, $contents));

			$name = 'contents[' . $contents_id . ']';

			$elements[$name] = new WdMultiEditorElement
			(
				$editor, array
				(
					WdForm::T_LABEL => $contents_title,
					WdElement::T_GROUP => 'contents',
					WdEditorElement::T_STYLESHEETS => $styles,

					'id' => 'editor:' . $contents_id,
					'value' => $value
				)
			);
		}

		return $elements;
	}

	/**
	 * Find the page matching an URL.
	 *
	 * @param string $url
	 * @return site_pages_WdActiveRecord
	 */

	public function find($url)
	{
		if (!$url)
		{
			#
			# The url is empty if the root index is defined, in this case we return
			# the first page of the tree structure.
			#

			return $this->model()->loadRange
			(
				0, 1, 'WHERE is_online = 1 AND parentid = 0 ORDER BY weight, created'
			)
			->fetchAndClose();
		}

		$parts = explode('/', $url);

		array_shift($parts);

		$parts_n = count($parts);

		//wd_log('find page for url: %url :parts', array('%url' => $url, ':parts' => $parts));

		$parent = null;
		$parentid = 0;

		$vars = array();
		$url = null;

		for ($i = 0 ; $i < $parts_n ; $i++)
		{
			$part = $parts[$i];

			$page = $this->model()->loadRange
			(
				0, 1, 'WHERE parentid = ? AND slug = ? AND pattern = ""', array
				(
					$parentid,
					$part
				)
			)
			->fetchAndClose();

			if (!$page)
			{
				#
				# we didn't find the corresponding page, we try for patterns
				#

				$pages = $this->model()->loadAll
				(
					'WHERE parentid = ? AND pattern != ""', array
					(
						$parentid
					)
				);

				foreach ($pages as $try)
				{
					$pattern = $try->pattern;

					$nparts = substr_count($pattern, '/') + 1;

					$local_url = implode('/', array_slice($parts, $i, $nparts));

					$match = WdRoute::match($local_url, $pattern);

					//wd_log('try pattern: %pattern with %url, match: !match', array('%pattern' => $try->pattern, '%url' => $url, '!match' => $match));

					if ($match === false)
					{
						continue;
					}

					$page = $try;

					$i += $nparts - 1;

					$url .= '/' . $local_url;

					#
					# even if the pattern matched, $match is not garanteed to be an array,
					# 'feed.xml' is a valid pattern.
					#

					if (is_array($match))
					{
						$vars = $match + $vars;
					}

					$page->url = $url;
					$page->url_vars = $vars;

					$page->url_local = $local_url;
					$page->url_local_vars = $match;

					//wd_log('found page for pattern: %pattern !page', array('%pattern' => $try->pattern, '!page' => $page));

					break;
				}

				$pages->closeCursor();
			}

			if (!$page)
			{
				break;
			}

			$page->parent = $parent;

			if ($parent && !$parent->is_online)
			{
				$page->is_online = false;
			}

			$parent = $page;
			$parentid = $page->nid;
		}

		//wd_log('page: \1', array($page));

		return $page;
	}

	public function getTree()
	{
		$entries = $this->model()->loadAll('ORDER BY weight, created')->fetchAll();

		$tree = site_pages_WdManager::entriesTreefy($entries);

		return $tree;
	}

	public function flattenTree($pages, $level=0)
	{
		$flatten = array();

		foreach ($pages as $page)
		{
			$title = str_repeat('-- ', $level) . $page->title;

			$flatten[$page->nid] = $title;

			if (isset($page->children))
			{
				$flatten += $this->flattenTree($page->children, $level + 1);
			}
		}

		return $flatten;
	}

	public function flattenTree2($pages, $level=0)
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
				$flatten = array_merge($flatten, $this->flattenTree2($page->children, $level + 1));
			}
		}

		return $flatten;
	}

	protected function getLayouts()
	{
		$path = '/protected/layouts';

		if (!is_dir($_SERVER['DOCUMENT_ROOT'] . $path))
		{
			WdDebug::trigger('The directory %path does not exists', array('%path' => $path));

			return false;
		}

		$dh = opendir($_SERVER['DOCUMENT_ROOT'] . $path);

		if (!$dh)
		{
			WdDebug::trigger('Unable to open directory %path', array('%path' => $path));

			return false;
		}

		$files = array();

		while (($file = readdir($dh)) !== false)
		{
			if (substr($file, -5, 5) != '.html')
			{
				continue;
			}

			$file = basename($file, '.html');

			$files[$file] = ucfirst($file);
		}

		//wd_log('files: \1', array($files));

		return $files;
	}

	protected function getLayoutEditables($layout)
	{
		$html = file_get_contents($_SERVER['DOCUMENT_ROOT'] . $layout);

		$parser = new WdHTMLParser();

		$tree = $parser->parse($html, 'wdp:page:');
		$collected = WdHTMLParser::collectMarkup($tree, 'contents');

		$contents = array();

		foreach ($collected as $node)
		{
			$contents[] = $node['args'];
		}

		//wd_log('collected: \1', array($collected));

		return $contents;
	}

	protected function getLayoutStyleSheets($layout)
	{
		$styles = array();
		$html = file_get_contents($_SERVER['DOCUMENT_ROOT'] . $layout);

		preg_match_all('#<link.*type="text/css".*>#', $html, $matches);

		foreach ($matches[0] as $match)
		{
			preg_match_all('#(\S+)="([^"]+)"#', $match, $attributes, PREG_SET_ORDER);

			foreach ($attributes as $attribute)
			{
				list(, $attribute, $value) = $attribute;

				if ($attribute == 'href')
				{
					$styles[] = $value;
				}
			}
		}

		return $styles;
	}

	protected function adjust_loadRange(array $where, array $values, $limit, $page)
	{
		$where[] = 'pattern = ""';

		return parent::adjust_loadRange($where, $values, $limit, $page);
	}

	public function adjust_createEntry($entry)
	{
		return parent::adjust_createEntry($entry) . ' <span class="small">&ndash; ' . $entry->url . '</span>';
	}
}
