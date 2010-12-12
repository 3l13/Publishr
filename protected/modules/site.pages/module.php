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
	const OPERATION_NAVIGATION_INCLUDE = 'navigation_include';
	const OPERATION_NAVIGATION_EXCLUDE = 'navigation_exclude';
	const OPERATION_UPDATE_TREE = 'update_tree';

	protected function operation_save(WdOperation $operation)
	{
		global $core;

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

		$operation->handle_booleans(Page::IS_NAVIGATION_EXCLUDED);
		$params = &$operation->params;

		#
		#
		#

		if (!$operation->key && empty($params[Page::WEIGHT]))
		{
			if (!$core->user->has_permission(self::PERMISSION_MODIFY_ASSOCIATED_SITE))
			{
				$params[Node::SITEID] = $core->working_site_id;
			}

			$weight = $this->model->query
			(
				'SELECT MAX(weight) FROM {self_and_related} WHERE siteid = ? AND parentid = ?', array
				(
					$params[Page::SITEID], isset($params[Page::PARENTID]) ? $params[Page::PARENTID] : 0
				)
			)
			->fetchColumnAndClose();

			$params[Page::WEIGHT] = ($weight === null) ? 0 : $weight + 1;
		}

		WdEvent::fire
		(
			'site.pages.save:before', array
			(
				'target' => $this,
				'operation' => $operation
			)
		);

		$rc = parent::operation_save($operation);

		if (!$rc)
		{
			return $rc;
		}

		$nid = $rc['key'];

		#
		# update contents
		#

		//wd_log('params: \1 result: \2', array($params, $rc));

		if (isset($params['contents']))
		{
			$contents_model = $this->model('contents');

			foreach ($params['contents'] as $content_id => $values)
			{
				$editor = $values['editor'];
				$editor_class = $editor . '_WdEditorElement';

				$content = call_user_func(array($editor_class, 'toContents'), $values, $rc['key']);

				#
				# we change the url for the view if the page is not the traduction of another page.
				#

				wd_log('editor: \1, content: \2', array($editor, $content));

				if ($editor == 'view' && strpos($content, '/') !== false)
				{
					$view_target_key = 'views.targets.' . strtr($content, '.', '_');

					wd_log('key: \1, nid: \2', array($view_target_key, $nid));

					$core->working_site->metas[$view_target_key] = $nid;

					wd_log('site (\1): \2', array($core->working_site_id, $core->working_site));
				}

				$values['content'] = $content;

				#
				# if there is no content, the content object is deleted
				#

				if (!$content)
				{
					$contents_model->execute('DELETE FROM {self} WHERE pageid = ? AND contentid = ?', array($nid, $content_id));

					continue;
				}

				$contents_model->insert
				(
					array
					(
						'pageid' => $nid,
						'contentid' => $content_id
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

						'entry' => $entry, // TODO-20101124: update listener to use `target`
						'target' => $entry,
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

	const OPERATION_COPY = 'copy';

	protected function get_operation_copy_controls(WdOperation $operation)
	{
		return array
		(
			self::CONTROL_PERMISSION => PERMISSION_CREATE,
			self::CONTROL_ENTRY => true,
			self::CONTROL_VALIDATOR => false
		);
	}

	protected function operation_copy(WdOperation $operation)
	{
		global $core;

		$entry = $operation->entry;
		$key = $operation->key;
		$title = $entry->title;

		unset($entry->nid);
		unset($entry->is_online);
		unset($entry->created);
		unset($entry->modified);

		$entry->uid = $core->user_id;
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

	protected function get_operation_navigation_include_controls(WdOperation $operation)
	{
		return array
		(
			self::CONTROL_PERMISSION => PERMISSION_MAINTAIN,
			self::CONTROL_OWNERSHIP => true,
			self::CONTROL_VALIDATOR => false
		);
	}

	protected function operation_navigation_include(WdOperation $operation)
	{
		$entry = $operation->entry;
		$entry->is_navigation_excluded = false;
		$entry->save();

		return true;
	}

	protected function get_operation_navigation_exclude_controls(WdOperation $operation)
	{
		return array
		(
			self::CONTROL_PERMISSION => PERMISSION_MAINTAIN,
			self::CONTROL_OWNERSHIP => true,
			self::CONTROL_VALIDATOR => false
		);
	}

	protected function operation_navigation_exclude(WdOperation $operation)
	{
		$entry = $operation->entry;
		$entry->is_navigation_excluded = true;
		$entry->save();

		return true;
	}

	protected function get_operation_update_tree_controls(WdOperation $operation)
	{
		return array
		(
			self::CONTROL_PERMISSION => self::PERMISSION_ADMINISTER,
			self::CONTROL_VALIDATOR => false
		);
	}

	protected function operation_update_tree(WdOperation $operation)
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
		global $core, $document;

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

		$template = $is_alone ? 'home.html' : 'page.html';
		$template_description = "Le <em>gabarit</em> définit un modèle de page dont certains éléments sont éditables.";

		if ($entry)
		{
			$template = $entry->template;

//			wd_log('template: \1, is_home: \2', array($template, $entry->is_home));

			if ($template == 'page.html' && (!$entry->parent || ($entry->parent && $entry->parent->is_home)))
			{
//				wd_log('page parent is home, hence the page.html template');

				$values[Page::TEMPLATE] = null;

				// TODO-20100507: à réviser, parce que la page peut ne pas avoir de parent.

				$template_description .= ' ' . "Parce qu'aucun gabarit n'est défini pour la page,
				elle utilise le gabarit &laquo;&nbsp;page.html&nbsp;&raquo;.";
			}
			else if ($template == 'home.html' && (!$entry->parent && $entry->weight == 0))
			{
				$values[Page::TEMPLATE] = null;

				//$template_description .= ' ' . "Cette page utilise le gabarit &laquo;&nbsp;home.html&nbsp;&raquo;.";
			}
			else
			{
				$inherited = $entry->parent;

//				wd_log_error('parent: \1 (\2)', array($inherited->title, $inherited->template));

				while ($inherited)
				{
//					wd_log('inherited: \1: \2', array($inherited->title, $inherited->template));

					if ($inherited->template != $template)
					{
						break;
					}

					$inherited = $inherited->parent;
				}

//				wd_log_error('inherited: \1', array($inherited));

				if ($inherited && $inherited->template == $template)
				{
	//				wd_log("entry template: $template ($entry->nid), from: $inherited->template ($inherited->nid: $inherited->title)");

					$template_description .= ' ' . t
					(
						'Cette page utilise le gabarit &laquo;&nbsp;:template&nbsp;&raquo; hérité de la page parente &laquo;&nbsp;<a href="!url">!title</a>&nbsp;&raquo;.', array
						(
							':template' => $template,
							'!url' => '/admin/site.pages/' . $inherited->nid . '/edit',
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

		$contents = $this->block_edit_contents($properties[Node::NID], $template);

		if (empty($contents[WdElement::T_CHILDREN]))
		{
			$template_description .= " Le gabarit &laquo;&nbsp;$template&nbsp;&raquo; ne définit pas d'élements éditables.";
		}
		else
		{
			$template_description .= ' Les éléments suivants sont éditables&nbsp;:';
		}

		#
		# parentid
		#

		$parentid_el = null;

		if (!$is_alone)
		{
			$parentid_el = new WdPageSelectorElement
			(
				'select', array
				(
					WdForm::T_LABEL => 'Page parente',
					WdElement::T_OPTIONS_DISABLED => $nid ? array($nid => true) : null,
					WdElement::T_DESCRIPTION => "Les pages peuvent être organisées
					hiérarchiquement. Il n'y a pas de limites à la profondeur de l'arborescence."
				)
			);
		}

		#
		# location element
		#

		$location_el = null;

		if (!$is_alone)
		{
			$location_el = new WdPageSelectorElement
			(
				'select', array
				(
					WdForm::T_LABEL => 'Redirection',
					WdElement::T_GROUP => 'advanced',
					WdElement::T_WEIGHT => 10,
					WdElement::T_OPTIONS_DISABLED => $nid ? array($nid => true) : null,
					WdElement::T_DESCRIPTION => 'Redirection depuis cette page vers une autre page.'
				)
			);
		}

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
						'class' => 'form-section flat',
						'weight' => 10
					),

					'advanced' => array
					(
						'title' => 'Options avancées',
						'class' => 'form-section flat',
						'weight' => 30
					)
				),

				WdElement::T_CHILDREN => array_merge
				(
					array
					(
						Page::PARENTID => $parentid_el,

						Page::IS_NAVIGATION_EXCLUDED => new WdElement
						(
							WdElement::E_CHECKBOX, array
							(
								WdElement::T_LABEL => 'Exclure la page de la navigation principale',
								WdElement::T_GROUP => 'online'
							)
						),

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

						Page::LOCATIONID => $location_el,

						Page::TEMPLATE => new WdAdjustTemplateElement
						(
							array
							(
								WdElement::T_LABEL => 'Gabarit',
								WdElement::T_LABEL_POSITION => 'before',
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
			$editor_config = json_decode($editable['config'], true);
			$editor_description = $editable['description'];

			#
			#
			#

			$contents = $contents_model->loadRange
			(
				0, 1, 'WHERE pageid = ? AND contentid = ?', array
				(
					$nid,
					$id
				)
			)
			->fetchAndClose();

			if ($contents)
			{
				$value = $contents->content;
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
								'!url' => '/admin/' . $this->id . '/' . $inherited->nid . '/edit',
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
		/*DIRTY:MULTISITE
		$root = $_SERVER['DOCUMENT_ROOT'];

		$path = '/protected/templates/' . $name;

		if (!file_exists($root . $path))
		{
			wd_log_error('Uknown template file %name', array('%name' => $name));

			return array();
		}

		$html = file_get_contents($root . $path);
		*/

		global $core;

		$site = $core->working_site;
		$path = $site->resolve_path('templates/' . $name);

		if (!$path)
		{
			wd_log_error('Uknown template file %name', array('%name' => $name));

			return array();
		}

		$html = file_get_contents($_SERVER['DOCUMENT_ROOT'] . $path);
		$parser = new WdHTMLParser();

		return self::get_template_info_callback($html, $parser);
	}

	static protected function get_template_info_callback($html, $parser)
	{
		$styles = array();
		$contents = array();

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

		$contents_collection = WdHTMLParser::collectMarkup($tree, 'page:content');

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

		global $core;

		$site = $core->working_site;
		$root = $_SERVER['DOCUMENT_ROOT'];

		$call_template_collection = WdHTMLParser::collectMarkup($tree, 'call-template');

		foreach ($call_template_collection as $node)
		{
			$template_name = $node['args']['name'];

			$file = $template_name . '.html';
			$path = $site->resolve_path('templates/partials/' . $file);

			if (!$path)
			{
				wd_log_error('Partial template %name not found', array('%name' => $file));

				continue;
			}

			$template = file_get_contents($root . $path);

			list($partial_contents, $partial_styles) = self::get_template_info_callback($template, $parser);

			$contents = array_merge($contents, $partial_contents);

			if ($partial_styles)
			{
				$styles = array_merge($styles, $partial_styles);
			}
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
