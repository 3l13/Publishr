<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

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

	protected function operation_save(WdOperation $operation)
	{
		global $registry;

		$entry = null;
		$oldurl = null;

		if ($operation->entry)
		{
			$entry = $operation->entry;
			$pattern = $entry->url_pattern;

			if (strpos($pattern, '<') === false)
			{
				$oldurl = $pattern;
			}
		}

		#
		#
		#

		$operation->handle_booleans(array(Page::IS_NAVIGATION_EXCLUDED));
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

		WdEvent::fire('site.pages.save:before', array('operation' => $operation));

		#
		#
		#

		$rc = parent::operation_save($operation);

		if (!$rc)
		{
			return $rc;
		}

		$nid = $rc['key'];

		#
		# update contents
		#

//		wd_log('params: \1, result: \2', array($operation->params, $rc));

		if (isset($params['contents']))
		{
			$contents_model = $this->model('contents');

			foreach ($params['contents'] as $contents_id => $values)
			{
				$editor = $values['editor'];
				$editor_class = $editor . '_WdEditorElement';

				$contents = call_user_func(array($editor_class, 'toContents'), $values, $rc['key']);

				#
				# we change the url for the view if the page is not the traduction of another page.
				#

				if ($editor == 'view' && empty($params['tnid']))
				{
					if (strpos($contents, '/') !== false)
					{
						$view_target_key = 'views.targets.' . strtr($contents, '.', '_');

//						wd_log("$view_target_key = $nid");

						$registry->set($view_target_key, $nid);
					}
				}

//				wd_log('contents: \1', array($contents));

				$values['contents'] = $contents;

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

		// TODO-20100526: else, delete all contents associated with the page.

		#
		# trigger `site.pages.url.change` event
		#

		if ($entry && $oldurl)
		{
			$entry = $this->model()->load($nid);
			$newurl = $entry->url;

			//wd_log('oldurl: \1, newurl: \2', array($oldurl, $newurl));

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

	protected function operation_query_delete(WdOperation $operation)
	{
		$entries = array();

		foreach ($operation->params['entries'] as $id)
		{
			$entry = $this->model()->load($id);

			if (!$entry)
			{
				continue;
			}

			$entries = array_merge(self::get_all_children_ids($entry), $entries);
		}

		$entries = array_unique($entries);

		$operation->params['entries'] = $entries;

		return parent::operation_query_delete($operation);
	}

	private function get_all_children_ids($entry)
	{
		$ids = array();

		if ($entry->children)
		{
			// FIXME-20100504: `children` only returns online children !

			foreach ($entry->children as $child)
			{
				$ids = array_merge(self::get_all_children_ids($child), $ids);
			}
		}

		$ids[] = $entry->nid;

		return $ids;
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
		$w = 0;
		$update = $this->model()->prepare('UPDATE {self} SET `parentid` = ?, `weight` = ? WHERE `{primary}` = ? LIMIT 1');
		$parents = $operation->params['parents'];

		foreach ($parents as $nid => $parentid)
		{
			// FIXME-20100429: cached entries are not updated here, we should flush the cache.

			$update->execute(array($parentid, $w++, $nid));
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
					'title', 'url', 'infos', 'uid', 'is_online', 'modified'
				),

				WdManager::T_ORDER_BY => null
			)
		);
	}

	protected function block_edit(array $properties, $permission)
	{
		global $document;

		$document->js->add('public/edit.js');

		#
		#
		#

		$nid = $properties[Node::NID];
		$entry = $nid ? $this->model()->load($nid) : null;
		$values = array();

		#
		# layout more
		#

		$template = 'page.html';
		$template_description = "Le <em>gabarit</em> définit un modèle de page dans lequel certains éléments
		sont éditables.";

		if ($entry)
		{
			$template = $entry->template;

//			wd_log('template: \1', array($template));

			if ($template == 'page.html' && (!$entry->parent || ($entry->parent && $entry->parent->is_home)))
			{
//				wd_log('page parent is home, hence the page.html template');

				$values[Page::TEMPLATE] = null;

				// TODO-20100507: à réviser, parce que la page peut ne pas avoir de parent.

				$template_description .= ' ' . "Parce qu'aucun gabarit n'est défini pour la page et que
				son parent est une page d'accueil, la page utilise le gabarit &laquo;&nbsp;page.html&nbsp;&raquo;.";
			}
			else if ($template == 'home.html')
			{
				$template_description .= ' ' . "Cette page utilise le gabarit &laquo;&nbsp;home.html&nbsp;&raquo;.";
			}
			else
			{
				$inherited = $entry->parent;

//				wd_log('parent: \1', array($inherited));

				while ($inherited)
				{
//					wd_log('inherited: \1: \2', array($inherited->title, $inherited->template));

					if (!$inherited->parent || ($inherited->parent && $inherited->parent->template != $template))
					{
						break;
					}

					$inherited = $inherited->parent;
				}

	//			wd_log('inherited: \1', array($inherited));

				if ($inherited && $inherited->template == $template)
				{
	//				wd_log("entry template: $template ($entry->nid), from: $inherited->template ($inherited->nid: $inherited->title)");

					$template_description .= ' ' . t
					(
						'Cette page utilise le gabarit &laquo;&nbsp;:template&nbsp;&raquo; hérité de la page parente &laquo;&nbsp;<a href="!url">!title</a>&nbsp;&raquo;.', array
						(
							':template' => $template,
							'!url' => WdRoute::encode('/site.pages/' . $inherited->nid . '/edit'),
							'!title' => $inherited->title
						)
					);

					#
					# If the template is inherited, we remove the value in order to have a clean
					# inheritence, easier to manage.
					#

					$values[Page::TEMPLATE] = null;
				}
			}
		}

		$template_description .= ' Les éléments suivants sont éditables&nbsp;:';

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
					WdElement::T_GROUP => 'node',
					WdElement::T_OPTIONS_DISABLED => $nid ? array($nid) : null,
					WdElement::T_DESCRIPTION => "Les pages peuvent être organisées
					hiérarchiquement. Il n'y a pas de limites à la profondeur de l'arborescence."
				)
			);
		}

		#
		#
		#

		$contents = $this->block_edit_contents($properties, $template);

		#
		# elements
		#

		return wd_array_merge_recursive
		(
			parent::block_edit($properties, $permission), array
			(
				WdForm::T_VALUES => $values,

				WdElement::T_GROUPS => array
				(
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
								WdElement::T_GROUP => 'advanced',
								WdElement::T_DESCRIPTION => "L'étiquette permet de remplacer le
								titre de la page, utilisé pour créer les liens des menus ou du fil
								d'ariane, par une version plus concise."
							)
						),

						Page::PATTERN => new WdElement
						(
							WdElement::E_TEXT, array
							(
								WdForm::T_LABEL => 'Motif',
								WdElement::T_GROUP => 'advanced',
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
								WdElement::T_GROUP => 'online'
							)
						),

						Page::LOCATIONID => new WdPageSelectorElement
						(
							'select', array
							(
								WdForm::T_LABEL => 'Redirection',
								WdElement::T_GROUP => 'advanced',
								WdElement::T_WEIGHT => 10,
								WdElement::T_OPTIONS_DISABLED => $nid ? array($nid) : null,
								WdElement::T_DESCRIPTION => 'Redirection depuis cette page vers une autre URL.'
							)
						),

						Page::TEMPLATE => new WdAdjustTemplateElement
						(
							array
							(
								WdForm::T_LABEL => 'Gabarit',
								WdElement::T_GROUP => 'contents',
								WdElement::T_DESCRIPTION => $template_description
							)
						)
					)
				)
			),

			$contents
		);
	}

	protected function block_edit_contents($properties, $layout)
	{
		$info = self::get_template_info($layout);

		if (!$info)
		{
			return array();
		}

		list($editables, $styles) = $info;

		#
		#
		#

		$elements = array();
		$hiddens = array();

		global $core;

		$contents_model = $this->model('contents');
		$nid = $properties[Page::NID];

		foreach ($editables as $editable)
		{
			$id = $editable['id'];
			$title = $editable['title'];

			$name = 'contents[' . $id . ']';
			$value = null;

			$editor = $editable['editor'];
			$editor_config = json_decode($editable['config']);
			$editor_description = $editable['description'];

			#
			#
			#

			$contents = $contents_model->loadRange
			(
				0, 1, 'WHERE pageid = ? AND contentsid = ?', array
				(
					$nid,
					$id
				)
			)
			->fetchAndClose();

			if ($contents)
			{
				$value = $contents->contents;
				$editor = $contents->editor;
			}

			if (isset($editable['inherit']))
			{
				$editor_description .= " Ce contenu est hérité, s'il n'est pas défini, le contenu
				d'une des pages parentes sera utilisé.";

				if (!$contents & $nid)
				{
					$inherited = null;
					$node = $this->model()->load($nid);

					while ($node)
					{
						$node_contents = $node->contents;

						if (isset($node_contents[$id]))
						{
							$inherited = $node;

							break;
						}

						$node = $node->parent;
					}

					if ($inherited)
					{
						$editor_description .= t
						(
							' Le contenu est actuellement hérité de la 	page &laquo;&nbsp;<a href="!url">!title</a>&nbsp;&raquo;.', array
							(
								'!url' => WdRoute::encode('/' . $this . '/' . $inherited->nid . '/edit'),
								'!title' => $inherited->title
							)
						);
					}
					else
					{
						$editor_description .= " Actuellement, aucune page parente ne défini ce contenu.";
					}
				}
			}

			/*
			 * each editor as a base name `contents[<editable_id>]` and much at least define two
			 * values :
			 *
			 * - `contents[<editable_id>][editor]`: The editor used to edit the contents
			 * - `contents[<editable_id>][contents]`: The content being edited.
			 *
			 */

			if ($editable['editor'])
			{
				$class = $editable['editor'] . '_WdEditorElement';

				$elements[$name . '[contents]'] = new $class
				(
					array
					(
						WdForm::T_LABEL => $title,

						WdEditorElement::T_STYLESHEETS => $styles,
						WdEditorElement::T_CONFIG => $editor_config,

						WdElement::T_GROUP => 'contents',
						WdElement::T_DESCRIPTION => $editor_description,

						'id' => 'editor:' . $id,
						'name' => $name,
						'value' => $value
					)
				);

				#
				# we add the editor's id as a hidden field
				#

				$hiddens[$name . '[editor]'] = $editable['editor'];
			}
			else
			{
				$elements[$name . '[contents]'] = new WdMultiEditorElement
				(
					$editor, array
					(
						WdForm::T_LABEL => $title,

						WdMultiEditorElement::T_NOT_SWAPPABLE => isset($editable['editor']),
						WdMultiEditorElement::T_SELECTOR_NAME => $name . '[editor]',
						WdMultiEditorElement::T_EDITOR_TAGS => array
						(
							WdEditorElement::T_STYLESHEETS => $styles,
							WdEditorElement::T_CONFIG => $editor_config
						),

						WdElement::T_GROUP => 'contents',
						WdElement::T_DESCRIPTION => $editor_description,

						'id' => 'editor:' . $id,
						'value' => $value
					)
				);
			}
		}

		return array
		(
			WdForm::T_HIDDENS => $hiddens,
			WdElement::T_CHILDREN => $elements
		);
	}

	static public function get_template_info($name)
	{
		$root = $_SERVER['DOCUMENT_ROOT'];

		$path = '/protected/templates/' . $name;

		if (!file_exists($root . $path))
		{
			wd_log_error('Uknown template file %name', array('%name' => $name));

			return array();
		}

		$html = file_get_contents($root . $path);

		return self::get_template_info_callback($html);
	}

	static protected function get_template_info_callback($html, $parser=null)
	{
		$styles = array();
		$contents = array();

		if (!$parser)
		{
			$parser = new WdHTMLParser();
		}

		#
		# search css files
		#

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

		#
		#
		#

		$tree = $parser->parse($html, 'wdp:');

		//wd_log('tree: \1', array($tree));

		#
		# contents
		#

		$contents_collection = WdHTMLParser::collectMarkup($tree, 'page:contents');

//		wd_log('contents collection: \1', array($contents_collection));

		foreach ($contents_collection as $node)
		{
			if (isset($node['children']))
			{
				foreach ($node['children'] as $child)
				{
					if (!is_array($child))
					{
						continue;
					}

					if ($child['name'] != 'with-param')
					{
						continue;
					}

					$param = $child['args']['name'];

					// TODO: what about arrays ? we should create a tree to string function

					$value = '';

					foreach ($child['children'] as $cv)
					{
						$value .= $cv;
					}

					$node['args'][$param] =	$value;
				}
			}

//			wd_log('found content: \1', array($node));

			$contents[] = $node['args'] + array
			(
				'editor' => null,
				'config' => null,
				'description' => null
			);
		}

		#
		# recurse on templates
		#

		$root = $_SERVER['DOCUMENT_ROOT'] . '/protected/templates/partials/';

//		wd_log('madonna: \1', array($tree));

		$call_template_collection = WdHTMLParser::collectMarkup($tree, 'call-template');

		foreach ($call_template_collection as $node)
		{
			$template_name = $node['args']['name'];

//			wd_log('node: \1', array($node));

			$file = $node['args']['name'] . '.html';

			if (!file_exists($root . $file))
			{
				wd_log_error('Template %name not found', array('%name' => $file));

				continue;
			}

			$template = file_get_contents($root . $file);

			list($partial_contents, $partial_styles) = self::get_template_info_callback($template, $parser);

			$contents = array_merge($contents, $partial_contents);

			if ($partial_styles)
			{
				$styles = array_merge($styles, $partial_styles);
			}

			//$contents = array_merge($contents, self::get_template_info_callback($template, $parser));
		}

		return array($contents, $styles);
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
