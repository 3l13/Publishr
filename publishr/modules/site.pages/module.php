<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class site_pages_WdModule extends system_nodes_WdModule
{
	const OPERATION_NAVIGATION_INCLUDE = 'navigation_include';
	const OPERATION_NAVIGATION_EXCLUDE = 'navigation_exclude';
	const OPERATION_UPDATE_TREE = 'update_tree';

	protected function control_properties_for_operation_save(WdOperation $operation)
	{
		global $core;

		$properties = parent::control_properties_for_operation_save($operation);

		if (!$operation->key)
		{
			$siteid = $core->working_site_id;
			$properties[Node::SITEID] = $siteid;

			if (empty($properties[Page::WEIGHT]))
			{
				$weight = $this->model
				->where('siteid = ? AND parentid = ?', $siteid, isset($properties[Page::PARENTID]) ? $properties[Page::PARENTID] : 0)
				->maximum('weight');

				$properties[Page::WEIGHT] = ($weight === null) ? 0 : $weight + 1;
			}
		}

		if (isset($properties[Page::LABEL]))
		{
			$properties[Page::LABEL] = trim($properties[Page::LABEL]);
		}

		if (isset($properties[Page::PATTERN]))
		{
			$properties[Page::PATTERN] = trim($properties[Page::PATTERN]);
		}

		return $properties;
	}

	protected function operation_save(WdOperation $operation)
	{
		global $core;

		$record = null;
		$oldurl = null;

		if ($operation->record)
		{
			$record = $operation->record;
			$pattern = $record->url_pattern;

			if (!WdRoute::is_pattern($pattern))
			{
				$oldurl = $pattern;
			}
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
		$nid = $rc['key'];

		#
		# update contents
		#

		$content_ids = array();
		$contents_model = $this->model('contents');

		if (isset($operation->params['contents']))
		{
			$contents = $operation->params['contents'];
			$content_ids = array_keys($contents);

			foreach ($contents as $content_id => $values)
			{
				$editor = $values['editor'];
				$editor_class = $editor . '_WdEditorElement';
				$content = call_user_func(array($editor_class, 'to_content'), $values, $content_id, $nid);

				#
				# if the content is made of an array of values, the values are serialized in JSON.
				#

				if (is_array($content))
				{
					$content = json_encode($content);
				}

				#
				# if there is no content, the content object is deleted
				#

				if (!$content)
				{
					$contents_model->where(array('pageid' => $nid, 'contentid' => $content_id))->delete();

					continue;
				}

				$values['content'] = $content;

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

		#
		# we delete possible remaining content for the page
		#

		$arr = $contents_model->find_by_pageid($nid);

		if ($content_ids)
		{
			$arr->where(array('!contentid' => $content_ids));
		}

		$arr->delete();

		#
		# trigger `site.pages.url.change` event
		#

		if ($record && $oldurl)
		{
			$record = $this->model[$nid];
			$newurl = $record->url;

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

						'entry' => $record, // TODO-20101124: update listener to use `target`
						// TODO-20110105: rename 'entry' as 'record'
						'target' => $record,
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
			$record = $this->model[$id];

			if (!$record)
			{
				continue;
			}

			$entries = array_merge(self::get_all_children_ids($record), $entries);
		}

		$entries = array_unique($entries);

		$operation->params['entries'] = $entries;

		return parent::operation_query_delete($operation);
	}

	private function get_all_children_ids($record)
	{
		$ids = array();

		if ($record->children)
		{
			// FIXME-20100504: `children` only returns online children !

			foreach ($record->children as $child)
			{
				$ids = array_merge(self::get_all_children_ids($child), $ids);
			}
		}

		$ids[] = $record->nid;

		return $ids;
	}

	const OPERATION_COPY = 'copy';

	protected function controls_for_operation_copy(WdOperation $operation)
	{
		return array
		(
			self::CONTROL_PERMISSION => PERMISSION_CREATE,
			self::CONTROL_RECORD => true,
			self::CONTROL_VALIDATOR => false
		);
	}

	protected function operation_copy(WdOperation $operation)
	{
		global $core;

		$record = $operation->record;
		$key = $operation->key;
		$title = $record->title;

		unset($record->nid);
		unset($record->is_online);
		unset($record->created);
		unset($record->modified);

		$record->uid = $core->user_id;
		$record->title .= ' (copie)';
		$record->slug .= '-copie';

		$contentsModel = $this->model('contents');
		$contents = $contentsModel->where(array('pageid' => $key))->all;

		$nid = $this->model->save((array) $record);

		if (!$nid)
		{
			wd_log_error('Unable to copy page %title (#:nid)', array('%title' => $title, ':nid' => $key));

			return;
		}

		wd_log_done('Page %title was copied to %copy', array('%title' => $title, '%copy' => $record->title));

		foreach ($contents as $record)
		{
			$record->pageid = $nid;
			$record = (array) $record;

			$contentsModel->insert
			(
				$record,

				array
				(
					'on duplicate' => $record
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

	protected function controls_for_operation_navigation_include(WdOperation $operation)
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
		$record = $operation->record;
		$record->is_navigation_excluded = false;
		$record->save();

		return true;
	}

	protected function controls_for_operation_navigation_exclude(WdOperation $operation)
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
		$record = $operation->record;
		$record->is_navigation_excluded = true;
		$record->save();

		return true;
	}

	protected function controls_for_operation_update_tree(WdOperation $operation)
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
		$update = $this->model->prepare('UPDATE {self} SET `parentid` = ?, `weight` = ? WHERE `{primary}` = ? LIMIT 1');
		$parents = $operation->params['parents'];

		foreach ($parents as $nid => $parentid)
		{
			// FIXME-20100429: cached entries are not updated here, we should flush the cache.

			$update->execute(array($parentid, $w++, $nid));
		}

		return true;
	}

	protected function controls_for_operation_template_editors(WdOperation $operation)
	{
		return array
		(
			self::CONTROL_PERMISSION => self::PERMISSION_CREATE,
			self::CONTROL_VALIDATOR => false
		);
	}

	/**
	 * Returns a sectionned form with the editors to use to edit the contents of a template.
	 *
	 * The function alters the operation object by adding the `template` property, which holds an
	 * array with the following keys:
	 *
	 * - `name`: The name of the template.
	 * - `description`: The description for the template.
	 * - `inherited`: Whether or not the template is inherited.
	 *
	 * The function also alters the operation object by adding the `assets` property, which holds
	 * an array with the following keys:
	 *
	 * - `css`: An array of CSS files URL.
	 * - `js`: An array of Javascript files URL.
	 *
	 * @param WdOperation $operation
	 * @return string The HTML code for the form.
	 */

	protected function operation_template_editors(WdOperation $operation)
	{
		global $core;

		$params = &$operation->params;

		$template = isset($params['template']) ? $params['template'] : null;
		$pageid = isset($params['pageid']) ? $params['pageid'] : null;

		list($contents_tags, $template_info) = $this->get_contents_section($pageid, $template);

		$operation->response->template = $template_info;

		$form = (string) new WdSectionedForm($contents_tags);

		$operation->response->assets = $core->document->get_assets();

		return $form;
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

		$document->css->add('public/edit.css');
		$document->js->add('public/edit.js');

		$nid = $properties[Node::NID];
		$is_alone = !$this->model->select('nid')->where(array('siteid' => $core->working_site_id))->rc;

		list($contents_tags, $template_info) = $this->get_contents_section($properties[Node::NID], $properties[Page::TEMPLATE]);

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
					WdForm::T_LABEL => '.parentid',
					WdElement::T_OPTIONS_DISABLED => $nid ? array($nid => true) : null,
					WdElement::T_DESCRIPTION => '.parentid'
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
					WdForm::T_LABEL => '.location',
					WdElement::T_GROUP => 'advanced',
					WdElement::T_WEIGHT => 10,
					WdElement::T_OPTIONS_DISABLED => $nid ? array($nid => true) : null,
					WdElement::T_DESCRIPTION => '.location'
				)
			);
		}

		return wd_array_merge_recursive
		(
			parent::block_edit($properties, $permission), array
			(
				WdForm::T_HIDDENS => array
				(
					Page::SITEID => $core->working_site_id,
					Page::LANGUAGE => $core->working_site->language
				),

				WdElement::T_GROUPS => array
				(
					'advanced' => array
					(
						'title' => '.advanced',
						'class' => 'form-section flat',
						'weight' => 30
					)
				),

				WdElement::T_CHILDREN => array
				(
					Page::PARENTID => $parentid_el,
					Page::SITEID => null,

					Page::IS_NAVIGATION_EXCLUDED => new WdElement
					(
						WdElement::E_CHECKBOX, array
						(
							WdElement::T_LABEL => '.is_navigation_excluded',
							WdElement::T_GROUP => 'online'
						)
					),

					Page::LABEL => new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => '.label',
							WdElement::T_GROUP => 'advanced',
							WdElement::T_DESCRIPTION => '.label'
						)
					),

					Page::PATTERN => new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => '.pattern',
							WdElement::T_GROUP => 'advanced',
							WdElement::T_DESCRIPTION => '.pattern'
						)
					),

					Page::LOCATIONID => $location_el
				)
			),

			$contents_tags
		);
	}

	protected function get_contents_section($nid, $template=null)
	{
		list($template, $template_description, $is_inherited) = $this->resolve_template($nid, $template);
		list($elements, $hiddens) = $this->get_contents_section_elements($nid, $template);

		if ($elements)
		{
			$template_description .= ' ' . t("The following elements are editable:");
		}
		else
		{
			$template_description = t("The <q>:template</q> template doesn't define editable elements", array(':template' => $template));
		}

		$elements = array_merge
		(
			array
			(
				Page::TEMPLATE => new WdAdjustTemplateElement
				(
					array
					(
						WdElement::T_LABEL => '.template',
						WdElement::T_LABEL_POSITION => 'before',
						WdElement::T_GROUP => 'contents',
						WdElement::T_DESCRIPTION => $template_description
					)
				)
			),

			$elements
		);

		return array
		(
			array
			(
				WdForm::T_HIDDENS => $hiddens,

				#
				# If the template is inherited, we remove the value in order to have a clean
				# inheritence, easier to manage.
				#

				WdForm::T_VALUES => array
				(
					Page::TEMPLATE => $is_inherited ? null : $template
				),

				WdElement::T_GROUPS => array
				(
					'contents' => array
					(
						'title' => '.contents',
						'class' => 'form-section flat',
						'weight' => 10
					),

					'contents.inherit' => array
					(
						'class' => 'form-section flat',
						'weight' => 11,
						'description' => '.contents.inherit'
					)
				),

				WdElement::T_CHILDREN => $elements
			),

			array
			(
				'name' => $template,
				'description' => $template_description,
				'inherited' => $is_inherited
			)
		);
	}

	protected function get_contents_section_elements($nid, $template)
	{
		global $core;

		$info = self::get_template_info($template);

		if (!$info)
		{
			return array(array(), array());
		}

		list($editables, $styles) = $info;

		$elements = array();
		$hiddens = array();

		$contents_model = $this->model('contents');

		foreach ($editables as $editable)
		{
			$id = $editable['id'];
			$title = $editable['title'];
			$title = t($id, array(), array('scope' => array('content', 'title'), 'default' => $title));

			$does_inherit = !empty($editable['inherit']);

			$name = 'contents[' . $id . ']';
			$value = null;

			$editor = $editable['editor'];
			$editor_config = json_decode($editable['config'], true);
			$editor_description = $editable['description'];

			#
			#
			#

			$contents = $nid ? $contents_model->where('pageid = ? AND contentid = ?', $nid, $id)->one : null;

			if ($contents)
			{
				$value = $contents->content;
				$editor = $contents->editor;
			}

			if ($does_inherit)
			{
				if (!$contents && $nid)
				{
					$inherited = null;
					$node = $this->model[$nid];

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

					// TODO-20101214: check home page

					if ($inherited)
					{
						$elements[] = new WdElement
						(
							'div', array
							(
								WdForm::T_LABEL => $title,
								WdElement::T_GROUP => 'contents.inherit',
								WdElement::T_INNER_HTML => '',
								WdElement::T_DESCRIPTION => t
								(
									'This content is currently inherited from the <q><a href="!url">!title</a></q> parent page – <a href="#edit">Edit the content</a>', array
									(
										'!url' => '/admin/' . $this->id . '/' . $inherited->nid . '/edit',
										'!title' => $inherited->title
									)
								),

								WdFormSectionElement::T_PANEL_CLASS => 'inherit-toggle'
							)
						);
					}
					else
					{
						$editor_description .= t('No parent page define this content.');
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

				if (!class_exists($class, true))
				{
					$elements[$name . '[contents]'] = new WdElement
					(
						'div', array
						(
							WdForm::T_LABEL => $title,
							WdElement::T_INNER_HTML => t('Éditeur inconnu : %editor', array('%editor' => $editable['editor'])),
							WdElement::T_GROUP => $does_inherit ? 'contents.inherit' : 'contents',

							'class' => 'danger'
						)
					);

					continue;
				}

				if (empty($editable['multiple']))
				{
					$elements[$name . '[contents]'] = new $class
					(
						array
						(
							WdForm::T_LABEL => $title,

							WdEditorElement::T_STYLESHEETS => $styles,
							WdEditorElement::T_CONFIG => $editor_config,

							WdElement::T_GROUP => $does_inherit ? 'contents.inherit' : 'contents',
							WdElement::T_DESCRIPTION => $editor_description,

							'id' => 'editor-' . $id,
							'name' => $name,
							'value' => $value
						)
					);
				}
				else
				{
					$n = 3;
					$fragments = array();

					for ($i = 0 ; $i < $n ; $i++)
					{
						$fragments[] = '<div class="excerpt"><em>generating excerpt...</em></div>';

						$fragments[$name . "[contents][$i]"] = new $class
						(
							array
							(
								WdEditorElement::T_STYLESHEETS => $styles,
								WdEditorElement::T_CONFIG => $editor_config,

								'name' => "$name[$i]",
								'value' => $value
							)
						);
					}

					$fragments[] = '<p><button type="button" class="continue small">Ajouter un nouveau contenu</button></p>';

					$elements[] = new WdElement
					(
						'div', array
						(
							WdForm::T_LABEL => $title,

							WdElement::T_GROUP => $does_inherit ? 'contents.inherit' : 'contents',
							WdElement::T_DESCRIPTION => $editor_description,
							WdElement::T_CHILDREN => $fragments,

							'id' => 'editor-' . $id,
							'class' => 'editor multiple'
						)
					);

					$hiddens[$name . '[is_multiple]'] = true;
				}

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

						WdElement::T_GROUP => $does_inherit ? 'contents.inherit' : 'contents',
						WdElement::T_DESCRIPTION => $editor_description,

						'id' => 'editor-' . $id,
						'value' => $value
					)
				);
			}
		}

		return array($elements, $hiddens);
	}

	/**
	 * Returns the template to use for a specified page.
	 *
	 * @param int $nid
	 * @return array An array composed of the template name, the description and a boolean
	 * representing wheter or not the template is inherited for the specified page.
	 */

	protected function resolve_template($nid, $request_template=null)
	{
		global $core;

		$inherited = false;
		$is_alone = !$this->model->select('nid')->find_by_siteid($core->working_site_id)->rc;

		if ($is_alone)
		{
			$template = 'home.html';
		}

		$description = t("The template defines a page model of which some elements are editable.");

		if (!$nid)
		{
			if ($is_alone)
			{
				$description .= " Parce que la page est seule elle utilise le gabarit <q>home.html</q>.";
			}
			else if (!$request_template)
			{
				$template = 'page.html';
			}
			else
			{
				$template = $request_template;
			}

			return array($template, $description, $template == 'page.html');
		}

		$record = $this->model[$nid];
		$definer = null;
		$template = $request_template ? $request_template : $record->template;

//		wd_log_done('template: \1 (requested: \3), is_home: \2', array($template, $record->is_home, $request_template));

		if ($template == 'page.html' && (!$record->parent || ($record->parent && $record->parent->is_home)))
		{
//			wd_log('page parent is home, hence the page.html template');

			$inherited = true;

			// TODO-20100507: à réviser, parce que la page peut ne pas avoir de parent.

			$description .= ' ' . "Parce qu'aucun gabarit n'est défini pour la page, elle utilise
			le gabarit <q>page.html</q>.";
		}
		else if ($template == 'home.html' && (!$record->parent && $record->weight == 0))
		{
			$inherited = true;

			//$template_description .= ' ' . "Cette page utilise le gabarit &laquo;&nbsp;home.html&nbsp;&raquo;.";
		}
		else if (!$request_template)
		{
			$definer = $record->parent;
		}
		else
		{
			$definer = $record;
			$parent = $record->parent;

//			wd_log_done('parent: \1 (\2 ?= \3)', array($definer->title, $definer->template, $template));

			while ($parent)
			{
//				wd_log_done('parent: \1, template: \2', array($parent->title, $parent->template));

				if ($parent->template == $request_template)
				{
					break;
				}

				$parent = $parent->parent;
			}

//			wd_log_done('end parent: \1', array($parent ? $parent->title : 'none'));

			if ($parent && $parent->template == $request_template)
			{
				$definer = $parent;
			}

//			wd_log_done('definer: \1:\3 (\2), record: \4:\5', array($definer->title,  $definer->template, $definer->nid, $record->title, $record->nid));
		}

		if ($definer && $definer != $record)
		{
//			wd_log("entry template: $template ($record->nid), from: $inherited->template ($inherited->nid: $inherited->title)");

			$description .= ' ' . t
			(
				'This page uses the <q>:template</q> template, inherited from the parent page <q><a href="!url">!title</a></q>.', array
				(
					':template' => $template,
					'!url' => '/admin/site.pages/' . $definer->nid . '/edit',
					'!title' => $definer->title
				)
			);

			$inherited = true;
		}

		return array($template, $description, $inherited);
	}

	static public function get_template_info($name)
	{
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

	public function adjust_createEntry($record)
	{
		return parent::adjust_createEntry($record) . ' <span class="small">&ndash; ' . $record->url . '</span>';
	}
}
