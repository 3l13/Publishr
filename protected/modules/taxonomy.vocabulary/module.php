<?php

class taxonomy_vocabulary_WdModule extends WdPModule
{
	protected function block_manage()
	{
		return new taxonomy_vocabulary_WdManager($this);
	}

	protected function block_edit(array $properties, $permission)
	{
		global $core, $document;

		$document->css->add('public/edit.css');

		#
		# scope
		#

		/*DIRTY:SCOPE
		$scopes = array();
		*/
		$scope_options = array();

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

			$is_instance = WdModel::is_extending($model, 'system.nodes');

			if (!$is_instance)
			{
				continue;
			}

			/*DIRTY:SCOPE
			$scopes[$module_id] = t($descriptor[self::T_TITLE]);
			*/
			$scope_options[$module_id] = t($descriptor[self::T_TITLE]);
		}

		/*DIRTY:SCOPE
		asort($scopes);
		*/

		uasort($scope_options, 'wd_unaccent_compare_ci');

		#
		#
		#

		/*DIRTY:SCOPE

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

		if (!isset($properties['scopes']) && isset($properties[taxonomy_vocabulary_WdActiveRecord::VID]))
		{
			$entries = $this->model('scope')->select
			(
				array('scope.scope', 'is_mandatory'), 'where vid = ?', array
				(
					$properties[taxonomy_vocabulary_WdActiveRecord::VID]
				)
			)
			->fetchAll(PDO::FETCH_ASSOC);

			$scopes_values = array();

			foreach ($entries as $entry)
			{
				$scopes_values[$entry['scope']] = $entry;
			}

			$properties['scopes'] = $scopes_values;
		}
		*/

		$scope_value = $properties[taxonomy_vocabulary_WdActiveRecord::SCOPE];

		wd_log('scope value: \1', array($scope_value));

		if (is_string($scope_value))
		{
			$scope_value = explode(',', $scope_value);
			$scope_value = array_map('trim', $scope_value);
			$scope_value = array_combine($scope_value, array_fill(0, count($scope_value), true));

			$properties[taxonomy_vocabulary_WdActiveRecord::SCOPE] = $scope_value;
		}

		#
		# children
		#

		return array
		(
			WdForm::T_VALUES => $properties,

			WdElement::T_GROUPS => array
			(
				'settings' => array
				(
					'title' => 'Settings',
					'weight' => 100,
					'class' => 'form-section flat'
				)
			),

			WdElement::T_CHILDREN => array
			(
				taxonomy_vocabulary_WdActiveRecord::VOCABULARY => new WdTitleSlugComboElement
				(
					array
					(
						WdForm::T_LABEL => 'Title',
						WdElement::T_REQUIRED => true
					)
				),

				taxonomy_vocabulary_WdActiveRecord::SCOPE => new WdElement
				(
					WdElement::E_CHECKBOX_GROUP, array
					(
						WdForm::T_LABEL => 'Scope',
						WdElement::T_OPTIONS => $scope_options,
						WdElement::T_REQUIRED => true,

						'class' => 'list combo'
					)
				),

				taxonomy_vocabulary_WdActiveRecord::IS_TAGS => new WdElement
				(
					WdElement::E_CHECKBOX, array
					(

						WdElement::T_LABEL => 'Tags',
						WdElement::T_GROUP => 'settings',
						WdElement::T_DESCRIPTION => 'Terms are created by users when
						submitting posts by typing a comma separated list.'
					)
				),

				taxonomy_vocabulary_WdActiveRecord::IS_MULTIPLE => new WdElement
				(
					WdElement::E_CHECKBOX, array
					(
						WdElement::T_LABEL => 'Multiple select',
						WdElement::T_GROUP => 'settings',
						WdElement::T_DESCRIPTION => 'Allows posts to have more than
						one term from this vocabulary (always true for tags).'
					)
				),

				taxonomy_vocabulary_WdActiveRecord::IS_REQUIRED => new WdElement
				(
					WdElement::E_CHECKBOX, array
					(
						WdElement::T_LABEL => 'Required',
						WdElement::T_GROUP => 'settings',
						WdElement::T_DESCRIPTION => 'At least one term in this
						vocabulary must be selected.'
					)
				)

				/*DIRTY:SCOPE
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
				*/
			)
		);
	}


	protected function block_order($vid)
	{
		global $core, $document;

		$document->js->add('public/order.js');
		$document->css->add('public/order.css');

		$terms_model = $core->getModule('taxonomy.terms')->model();

		$terms = $terms_model->loadAll('WHERE vid = ? ORDER BY term.weight, vtid', array($vid))->fetchAll();

		$rc = '';
		$rc .= '<form id="taxonomy-order" method="post">';
		$rc .= '<input type="hidden" name="#operation" value="' . self::OPERATION_ORDER . '" />';
		$rc .= '<input type="hidden" name="#destination" value="' . $this . '" />';
		$rc .= '<ol>';

		foreach ($terms as $term)
		{
			$rc .= '<li>';
			$rc .= '<input type="hidden" name="terms[' . $term->vtid . ']" value="' . $term->weight . '" />';
			$rc .= wd_entities($term->term);
			$rc .= '</li>';
		}

		$rc .= '</ol>';

		$rc .= '<div class="actions">';
		$rc .= '<button class="save">Enregistrer</button>';
		$rc .= '</div>';

		$rc .= '</form>';

		return $rc/* . wd_dump($terms)*/;
	}





	/*
	 * IMPLEMENTS
	 *
	 */

	public function alter_block_edit($event)
	{
		global $core, $document;

		$terms_module = $core->getModule('taxonomy.terms');

		/*DIRTY:SCOPE
		$vocabularies = $this->model('scope')->loadAll
		(
			'where `scope` = ? order by `weight`', array((string) $event->target)
		)
		->fetchAll(PDO::FETCH_ASSOC);
		*/

		$vocabularies = $this->model
		->where('? IN (scope)', (string) $event->target)
		->order('weight')
		->all();

		$children = array();

		$identifier_base = $this->flat_id . '[' . taxonomy_vocabulary_WdActiveRecord::VID . ']';

		#
		# extends document
		#

		// TODO-20101104: use WdForm::T_VALUES instead of setting the 'values' of the elements.
		// -> because 'properties' are ignored, and that's bad.

		$document->css->add('public/support.css');
		$document->js->add('public/support.js');

		$terms_model = $terms_module->model();
		$nodes_model = $terms_module->model('nodes');

		$nid = $event->key;

		foreach ($vocabularies as $vocabulary)
		{
			$vid = $vocabulary->vid;;

			$identifier = $identifier_base . '[' . $vid . ']';

			if ($vocabulary->is_multiple)
			{
				$options = $terms_model->select
				(
					array('term', 'count(nid)'), 'inner join {self}_nodes using(vtid) where `vid` = ? group by `term` order by `term`', array
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

				$label = $vocabulary->vocabulary;

				$children[] = new WdElement
				(
					'div', array
					(
						WdForm::T_LABEL => $label,

						WdElement::T_GROUP => 'organize',
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

						'class' => 'taxonomy-tags combo'
					)
				);
			}
			else
			{
				$options = $terms_model->select
				(
					array('term.vtid', 'term'), 'where `vid` = ? order by `term`', array
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
					'node.vtid', 'where vid = ? and nid = ? order by term', array
					(
						$vid, $nid
					)
				)
				->fetchColumnAndClose();

				$edit_url = '/admin/' . $this . '/' . $vocabulary->vid . '/edit';
				$terms_url = '/admin/taxonomy.terms';

				$children[$identifier] = new WdElement
				(
					'select', array
					(
						WdForm::T_LABEL => $vocabulary->vocabulary,
						WdElement::T_GROUP => 'organize',
						WdElement::T_OPTIONS => array(null => '') + $options,
						WdElement::T_REQUIRED => $vocabulary->is_required,
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
					'organize' => array
					(
						'title' => 'Organiser',
						'class' => 'form-section flat',
						'weight' => 500,
						'description' => 'Méthode de classification des informations dans une
						architecture structurée de manière évolutive.'
					)
				),

				WdElement::T_CHILDREN => $children
			)
		);
	}

	public function event_operation_save(WdEvent $event)
	{
		global $core;

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
		# on supprime toutes les liaisons pour cette node
		#

		$terms_model = $core->models['taxonomy.terms'];
		$nodes_model = $core->models['taxonomy.terms/nodes'];

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

			$vocabulary = $this->model->load($vid);

			if ($vocabulary->is_tags)
			{
				#
				# because tags are provided as a string with coma separated terms,
				# we need to get/created terms id before we can update the links between
				# terms and nodes
				#

				$terms = explode(',', $values);
				$terms = array_map('trim', $terms);

				$values = array();

				foreach ($terms as $term)
				{
					$vtid = $terms_model->select
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
						$vtid = $terms_model->save
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

	protected function operation_save(WdOperation $operation)
	{
		$operation->handle_booleans
		(
			array
			(
				taxonomy_vocabulary_WdActiveRecord::IS_MULTIPLE,
				taxonomy_vocabulary_WdActiveRecord::IS_REQUIRED,
				taxonomy_vocabulary_WdActiveRecord::IS_TAGS
			)
		);

		$params = &$operation->params;

		if (isset($params[taxonomy_vocabulary_WdActiveRecord::SCOPE]) && is_array($params[taxonomy_vocabulary_WdActiveRecord::SCOPE]))
		{
			$params[taxonomy_vocabulary_WdActiveRecord::SCOPE] = implode(',', array_keys($params[taxonomy_vocabulary_WdActiveRecord::SCOPE]));
		}

		parent::operation_save($operation);
	}

	/*
	 * "order" operation
	 */

	const OPERATION_ORDER = 'order';

	protected function validate_operation_order(WdOperation $operation)
	{
		return !empty($operation->params['terms']);
	}

	protected function operation_order(WdOperation $operation)
	{
//		wd_log('operation order: \1', array($operation));

		$weights = array();
		$w = 0;

		$update = $this->model()->prepare('UPDATE {prefix}taxonomy_terms SET weight = ? WHERE vtid = ?');

		foreach ($operation->params['terms'] as $vtid => $dummy)
		{
			$update->execute(array($w, $vtid));

			$weights[$vtid] = $w++;
		}
	}
}