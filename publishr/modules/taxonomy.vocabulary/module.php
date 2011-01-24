<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class taxonomy_vocabulary_WdModule extends WdPModule
{
	const PERMISSION_MODIFY_ASSOCIATED_SITE = 'modify associated site';

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

		$scope_options = array();

		foreach ($core->modules->descriptors as $module_id => $descriptor)
		{
			/*
			if (empty($descriptor[self::T_MODELS]['primary']))
			{
				continue;
			}
			*/

			if ($module_id == 'system.nodes' || empty($core->modules[$module_id]))
			{
				continue;
			}

			/*
			$model = $descriptor[self::T_MODELS]['primary'];

			$is_instance = WdModel::is_extending($model, 'system.nodes');
			*/

			$is_instance = WdModule::is_extending($module_id, 'system.nodes');

			if (!$is_instance)
			{
				continue;
			}

			$scope_options[$module_id] = t($descriptor[self::T_TITLE]);
		}

		uasort($scope_options, 'wd_unaccent_compare_ci');

		/*
		$scope_value = $properties[taxonomy_vocabulary_WdActiveRecord::SCOPE];

		if (is_string($scope_value))
		{
			$scope_value = explode(',', $scope_value);
			$scope_value = array_map('trim', $scope_value);
			$scope_value = array_combine($scope_value, array_fill(0, count($scope_value), true));

			$properties[taxonomy_vocabulary_WdActiveRecord::SCOPE] = $scope_value;
		}
		*/

		$scope_value = null;
		$vid = $properties[taxonomy_vocabulary_WdActiveRecord::VID];

		if ($vid)
		{
			$scope_value = $this->model('scopes')->select('constructor, 1')->where('vid = ?', $vid)->pairs;

			$properties[taxonomy_vocabulary_WdActiveRecord::SCOPE] = $scope_value;
		}

		#
		#
		#

		if ($core->user->has_permission(self::PERMISSION_MODIFY_ASSOCIATED_SITE, $this))
		{
			// TODO-20100906: this should be added by the "site.sites" modules using the alter event.

			$siteid_el = new WdElement
			(
				'select', array
				(
					WdElement::T_LABEL => 'Site',
					WdElement::T_LABEL_POSITION => 'before',
					WdElement::T_OPTIONS => array
					(
						null => ''
					)

					+ $core->models['site.sites']->select('siteid, concat(title, ":", language)')->order('title')->pairs,

					WdElement::T_DEFAULT => $core->working_site_id,
					WdElement::T_GROUP => 'admin',
					WdElement::T_DESCRIPTION => "Parce que vous en avez la permission, vous pouvez
					choisir le site d'appartenance de cette entrée. Une entrée appartenant à un
					site apparait uniquement sur ce site."
				)
			);
		}

		return array
		(
			WdElement::T_GROUPS => array
			(
				'settings' => array
				(
					'title' => 'Options',
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
						WdForm::T_LABEL => 'Portée',
						WdElement::T_OPTIONS => $scope_options,
						WdElement::T_REQUIRED => true,

						'class' => 'list combo',
						'value' => $scope_value
					)
				),

				taxonomy_vocabulary_WdActiveRecord::IS_TAGS => new WdElement
				(
					WdElement::E_CHECKBOX, array
					(

						WdElement::T_LABEL => 'Étiquettes',
						WdElement::T_GROUP => 'settings',
						WdElement::T_DESCRIPTION => 'Terms are created by users when
						submitting posts by typing a comma separated list.'
					)
				),

				taxonomy_vocabulary_WdActiveRecord::IS_MULTIPLE => new WdElement
				(
					WdElement::E_CHECKBOX, array
					(
						WdElement::T_LABEL => 'Multiple appartenance',
						WdElement::T_GROUP => 'settings',
						WdElement::T_DESCRIPTION => "Les enregistrements peuvent appartenir à
						plusieurs terms du vocabulaire (c'est toujours le cas pour les
						<em>étiquettes</em>)"
					)
				),

				taxonomy_vocabulary_WdActiveRecord::IS_REQUIRED => new WdElement
				(
					WdElement::E_CHECKBOX, array
					(
						WdElement::T_LABEL => 'Requis',
						WdElement::T_GROUP => 'settings',
						WdElement::T_DESCRIPTION => 'Au moins un terme de ce vocabulaire doit être
						sélectionné.'
					)
				),

				taxonomy_vocabulary_WdActiveRecord::SITEID => $siteid_el
			)
		);
	}


	protected function block_order($vid)
	{
		global $core, $document;

		$document->js->add('public/order.js');
		$document->css->add('public/order.css');

		$terms = $core->models['taxonomy.terms']->where('vid = ?', $vid)->order('term.weight, vtid')->all;

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

		$document->css->add('public/support.css');
		$document->js->add('public/support.js');

		$vocabularies = $this->model
		->joins('INNER JOIN {self}_scopes USING(vid)')
		->where('constructor = ? AND (siteid = 0 OR siteid = ?)', (string) $event->target, $core->working_site_id)
		->order('weight')
		->all;

		// TODO-20101104: use WdForm::T_VALUES instead of setting the 'values' of the elements.
		// -> because 'properties' are ignored, and that's bad.

		$terms_model = $core->models['taxonomy.terms'];
		$nodes_model = $core->models['taxonomy.terms/nodes'];

		$nid = $event->key;
		$identifier_base = $this->flat_id . '[' . taxonomy_vocabulary_WdActiveRecord::VID . ']';
		$children = array();

		foreach ($vocabularies as $vocabulary)
		{
			$vid = $vocabulary->vid;;

			$identifier = $identifier_base . '[' . $vid . ']';

			if ($vocabulary->is_multiple)
			{
				$options = $terms_model->select('term, count(nid)')
				->joins('inner join {self}_nodes using(vtid)')->where(array('vid' => $vid))
				->group('term')->order('term')->pairs;

				$value = $nodes_model->select('term')->where('vid = ? and nid = ?', $vid, $nid)->order('term')->all(PDO::FETCH_COLUMN);
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
				$options = $terms_model->select('term.vtid, term')->where('vid = ?', $vid)->order('term')->pairs;

				if (!$options)
				{
					//continue;
				}

				$value = $nodes_model->select('node.vtid')->where('vid = ? and nid = ?', $vid, $nid)->order('term')->rc;

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

		$nodes_model->where('nid = ?', $nid)->delete();

		#
		# on crée maintenant les nouvelles liaisons
		#

		foreach ($vocabularies as $vid => $values)
		{
			if (!$values)
			{
				continue;
			}

			$vocabulary = $this->model[$vid];

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
					$vtid = $terms_model->select('vtid')->where('vid = ? and term = ?', $vid, $term)->rc;

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

		$update = $this->model->prepare('UPDATE {prefix}taxonomy_terms SET weight = ? WHERE vtid = ?');

		foreach ($operation->params['terms'] as $vtid => $dummy)
		{
			$update->execute(array($w, $vtid));

			$weights[$vtid] = $w++;
		}
	}

	protected $cache_ar_vocabularies = array();
	protected $cache_ar_terms = array();

	public function event_ar_property(WdEvent $event)
	{
		global $core;

		$target = $event->target;
		$constructor = $target->constructor;
		$property = $vocabularyslug = $event->property;
		$siteid = $target->siteid;

		$use_slug = false;

		if (substr($property, -4, 4) == 'slug')
		{
			$use_slug = true;
			$vocabularyslug = substr($property, 0, -4);
		}

		$key = $siteid . '-' . $constructor . '-' . $vocabularyslug;

		if (!isset($this->cache_ar_vocabularies[$key]))
		{
			$this->cache_ar_vocabularies[$key] = $this->model
			->joins('INNER JOIN {self}_scopes USING(vid)')
			->where('constructor = ?', (string) $constructor)
			->where('vocabularyslug = ? AND (siteid = 0 OR siteid = ?)', $vocabularyslug, $target->siteid)
			->one();
		}

		$vocabulary = $this->cache_ar_vocabularies[$key];

		if (!$vocabulary)
		{
			return;
		}

		if ($vocabulary->is_required)
		{
			$event->value = 'uncategorized';
		}

		if (!isset($this->cache_ar_terms[$key]))
		{
			$terms = $core->models['taxonomy.terms']->query
			(
				'SELECT term.*, (SELECT GROUP_CONCAT(nid) FROM {self}_nodes tnode WHERE tnode.vtid = term.vtid) AS nodes_ids
				FROM {self} term WHERE vid = ?', array
				(
					$vocabulary->vid
				)
			)
			->fetchAll(PDO::FETCH_CLASS, 'taxonomy_terms_WdActiveRecord');

			foreach ($terms as $term)
			{
				$term->nodes_ids = array_flip(explode(',', $term->nodes_ids));
			}

			$this->cache_ar_terms[$key] = $terms;
		}

		$nid = $target->nid;

		foreach ($this->cache_ar_terms[$key] as $term)
		{
			if (!isset($term->nodes_ids[$nid]))
			{
				continue;
			}

			$event->value = $use_slug ? $term->termslug : $term;
			$event->stop();

			return;
		}
	}
}