<?php

class taxonomy_vocabulary_WdModule extends WdPModule
{
	protected function block_manage()
	{
		return new taxonomy_vocabulary_WdManager($this);
	}

	protected function block_edit(array $values, $permission)
	{
		global $document;

		$document->addStyleSheet('public/edit.css');

		#
		# scope
		#

		global $core;

		$scopes = array();

		foreach ($core->descriptors as $module_id => $descriptor)
		{
			if (empty($descriptor[self::T_MODELS]['primary']))
			{
				continue;
			}

			if (!$core->hasModule($module_id))
			{
				continue;
			}

			$model = $descriptor[self::T_MODELS]['primary'];

			$is_instance = self::modelInstanceof($model, 'system.nodes');

			if (!$is_instance)
			{
				continue;
			}

			$scopes[$module_id] = t($descriptor[self::T_TITLE]);
		}

		asort($scopes);

		#
		#
		#

		$e = array();

		foreach ($scopes as $module_id => $title)
		{
			$e[] = new WdElement
			(
				'div', array
				(
					WdElement::T_CHILDREN => array
					(
						'scopes[' . $module_id . '][scope]' => new WdElement
						(
							WdElement::E_CHECKBOX, array
							(
								WdElement::T_LABEL => $title . ' <small>(' . $module_id . ')</small>',

								'value' => $module_id,
								'class' => 'header'
							)
						),

						new WdElement
						(
							'div', array
							(
								WdElement::T_CHILDREN => array
								(
									'scopes[' . $module_id . '][is_mandatory]' => new WdElement
									(
										WdElement::E_CHECKBOX, array
										(
											WdElement::T_LABEL => 'Mandatory'
										)
									)
								),

								'class' => 'checkbox-group list'
							)
						)
					),

					'class' => 'local'
				)
			);
		}

		#
		# load current scope
		#

		if (!isset($values['scopes']) && isset($values[taxonomy_vocabulary_WdActiveRecord::VID]))
		{
			$entries = $this->model('scope')->select
			(
				array('scope', 'is_mandatory'), 'where vid = ?', array
				(
					$values[taxonomy_vocabulary_WdActiveRecord::VID]
				)
			)
			->fetchAll(PDO::FETCH_ASSOC);

			$scopes_values = array();

			foreach ($entries as $entry)
			{
				$scopes_values[$entry['scope']] = $entry;
			}

			$values['scopes'] = $scopes_values;
		}

		#
		# children
		#

		return array
		(
			WdForm::T_VALUES => $values,

			WdElement::T_GROUPS => array
			(
				'options' => array
				(
					'title' => 'Options',
					'weight' => 100
				)
			),

			WdElement::T_CHILDREN => array
			(
				taxonomy_vocabulary_WdActiveRecord::VOCABULARY => new WdTitleSlugComboElement
				(
					array
					(
						WdForm::T_LABEL => 'Title',
						WdElement::T_MANDATORY => true
					)
				),

				new WdElement
				(
					'div', array
					(
						WdForm::T_LABEL => 'Global settings',
						WdElement::T_GROUP => 'options',

						WdElement::T_CHILDREN => array
						(
							taxonomy_vocabulary_WdActiveRecord::IS_TAGS => new WdElement
							(
								WdElement::E_CHECKBOX, array
								(

									WdElement::T_LABEL => 'Tags',
									WdElement::T_DESCRIPTION => 'Terms are created by users when
									submitting posts by typing a comma separated list.'
								)
							),

							taxonomy_vocabulary_WdActiveRecord::IS_MULTIPLE => new WdElement
							(
								WdElement::E_CHECKBOX, array
								(
									WdElement::T_LABEL => 'Multiple select',
									WdElement::T_DESCRIPTION => 'Allows posts to have more than
									one term from this vocabulary (always true for tags).'
								)
							)/*,

							self::IS_MANDATORY => new WdElement
							(
								WdElement::E_CHECKBOX, array
								(
									WdElement::T_LABEL => 'Mandatory',
									WdElement::T_DESCRIPTION => 'At least one term in this
									vocabulary must be selected.'
								)
							)*/
						)
					)
				),

				new WdElement
				(
					'div', array
					(
						WdForm::T_LABEL => 'Scope and local settings',
						WdElement::T_GROUP => 'options',
						WdElement::T_CHILDREN => $e,
						WdElement::T_DESCRIPTION => '<sup>*</sup>&nbsp;Mandatory: For the scope, at least one term in this
						vocabulary must be selected.',

						'class' => 'scopes'
					)
				)
			)
		);
	}

	/*
	 * IMPLEMENTS
	 *
	 */

	public function alter_block_edit($event)
	{
		if (!($event->module instanceof system_nodes_WdModule))
		{
			return;
		}

		global $core;

		$terms_module = $core->getModule('taxonomy.terms');

		$vocabularies = $this->model('scope')->loadAll
		(
			'where `scope` = ? order by `weight`', array((string) $event->module)
		)
		->fetchAll(PDO::FETCH_ASSOC);

		$children = array();

		$identifier_base = str_replace('.', '_', (string) $this) . '[' . taxonomy_vocabulary_WdActiveRecord::VID . ']';

		#
		# extends document
		#

		global $document;

		$document->addStyleSheet('public/support.css');
		$document->addJavaScript('public/support.js');

		$terms_model = $terms_module->model();
		$nodes_model = $terms_module->model('nodes');

		$nid = $event->key;

		foreach ($vocabularies as $vocabulary)
		{
			$vid = $vocabulary[taxonomy_vocabulary_WdActiveRecord::VID];

			$identifier = $identifier_base . '[' . $vid . ']';

			if ($vocabulary[taxonomy_vocabulary_WdActiveRecord::IS_MULTIPLE])
			{
				$options = $terms_module->model()->select
				(
					array('term', 'count(nid)'), 'inner join {prefix}taxonomy_terms_nodes using(vtid) where `vid` = ? group by `term` order by `term`', array
					(
						$vid
					)
				)
				->fetchPairs();

				$value = $nodes_model->select
				(
					'term', 'where `vid` = ? and `nid` = ? order by `term`', array
					(
						$vid, $nid
					)
				)
				->fetchAll(PDO::FETCH_COLUMN);

				$value = implode(', ', $value);

				$label = $vocabulary[taxonomy_vocabulary_WdActiveRecord::VOCABULARY];

				$children[] = new WdElement
				(
					'div', array
					(
						WdForm::T_LABEL => $label,

						WdElement::T_GROUP => 'taxonomy',
						WdElement::T_WEIGHT => 100,

						WdElement::T_CHILDREN => array
						(
							new WdElement
							(
								WdElement::E_TEXT, array
								(
									'value' => $value,
									'name' => $identifier
								)
							),

							new WdCloudElement
							(
								'ul', array
								(
									WdElement::T_OPTIONS => $options,
									'class' => 'cloud'
								)
							)
						),

						'class' => 'taxonomy-tags'
					)
				);
			}
			else
			{
				$options = $terms_model->select
				(
					array('t1.vtid', 'term'), 'where `vid` = ? order by `term`', array
					(
						$vid
					)
				)
				->fetchPairs();

				if (!$options)
				{
					//continue;
				}

				$value = $nodes_model->select
				(
					't1.vtid', 'where vid = ? and nid = ? order by term', array
					(
						$vid, $nid
					)
				)
				->fetchColumnAndClose();

				$edit_url = WdRoute::encode('/' . $this . '/' . $vocabulary[taxonomy_vocabulary_WdActiveRecord::VID] . '/edit');
				$terms_url = WdRoute::encode('/taxonomy.terms');

				$children[$identifier] = new WdElement
				(
					'select', array
					(
						WdForm::T_LABEL => $vocabulary[taxonomy_vocabulary_WdActiveRecord::VOCABULARY],
						WdElement::T_GROUP => 'taxonomy',
						WdElement::T_OPTIONS => array(null => '') + $options,
						WdElement::T_MANDATORY => $vocabulary[taxonomy_vocabulary_WdActiveRecord::IS_MANDATORY],
						WdElement::T_DESCRIPTION => '<p><a href="' . $terms_url . '">Gérer les termes</a> ou
						<a href="' . $edit_url . '">éditer ce vocabulaire</a>.</p>',
						'value' => $value
					)
				);
			}
		}

		// FIXME: There is no class to create a _tags_ element. They are created using a collection
		// of objects in a div, so the key is a numeric, not an identifier.

		$event->tags = wd_array_merge_recursive
		(
			$event->tags, array
			(
				WdElement::T_GROUPS => array
				(
					'taxonomy' => array
					(
						'title' => 'Taxonomy',
						'weight' => 500
					)
				),

				WdElement::T_CHILDREN => $children
			)
		);
	}

	static protected function modelInstanceof($descriptor, $instanceof)
	{
		if (empty($descriptor[WdModel::T_EXTENDS]))
		{
			//wd_log('no extends in \1', array($model));

			return false;
		}

		$extends = $descriptor[WdModel::T_EXTENDS];

		if ($extends == $instanceof)
		{
			//wd_log('found instance of with: \1', array($model));

			return true;
		}

		global $core;

		if (empty($core->descriptors[$extends][WdModule::T_MODELS]['primary']))
		{
			//wd_log('no primary for: \1', array($extends));

			return false;
		}

		//wd_log('try: \1', array($extends));

		return self::modelInstanceof($core->descriptors[$extends][WdModule::T_MODELS]['primary'], $instanceof);
	}

	public function event_operation_save(WdEvent $event)
	{
		if (!($event->module instanceof system_nodes_WdModule))
		{
			return;
		}


		$name = 'taxonomy_vocabulary';
		$params = $event->operation->params;

		if (empty($params[$name]))
		{
			return;
		}

		$nid = $event->rc['key'];
		$scope = $event->operation->destination;

		$vocabularies = $params[$name][taxonomy_vocabulary_WdActiveRecord::VID];

		#
		#
		#

		global $core;

		$terms_module = $core->getModule('taxonomy.terms');

		//wd_log('save scope: \1, nid: \2\3', $nid, $scope, $params[$name]);

		#
		# on supprime toutes les liaisons pour cette node
		#

		$nodes_model = $terms_module->model('nodes');

		$nodes_model->execute
		(
			'delete from {self} where nid = ?', array
			(
				$nid
			)
		);

		#
		# on crée maintenant les nouvelles liaisons
		#

		foreach ($vocabularies as $vid => $values)
		{
			if (!$values)
			{
				continue;
			}

			$vocabulary = $this->model()->load($vid);

			if ($vocabulary->is_tags)
			{
				#
				# because tags are provided as a string with coma separated terms,
				# we need to get/created terms id before we can update the links between
				# terms and nodes
				#

				$terms = explode(', ', $values);
				$terms = array_map('trim', $terms);

				$values = array();

				foreach ($terms as $term)
				{
					$vtid = $terms_module->model()->select
					(
						'vtid', 'where vid = ? and term = ? limit 1', array
						(
							$vid, $term
						)
					)
					->fetchAndClose(PDO::FETCH_COLUMN);

					// FIXME-20090127: only users with 'create tags' permissions should be allowed to create tags

					if (!$vtid)
					{
						$vtid = $terms_module->model()->save
						(
							array
							(
								'vid' => $vid,
								'term' => $term
							)
						);
					}

					$values[] = $vtid;
				}
			}

			foreach ((array) $values as $vtid)
			{
				$nodes_model->insert
				(
					array
					(
						'vtid' => $vtid,
						'nid' => $nid
					),

					array
					(
						'ignore' => true
					)
				);
			}
		}
	}
}

/*

2009-12-23 # 1.2

[CHG] The mandatory option is now local to the scopes and no longer global. This change allows
using the same vocabulary with different mandatory options per scope.

*/