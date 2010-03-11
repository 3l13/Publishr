<?php

class system_nodes_attachments_WdModule extends WdPModule
{
	protected function block_edit(array $properties, $permission)
	{
		global $core;

		#
		# create scope options
		#

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

			$model_tags = $descriptor[self::T_MODELS]['primary'];

			$is_extends = WdModel::doesExtends($model_tags, 'system.nodes');

			if (!$is_extends)
			{
				continue;
			}

			$scope_options[$module_id] = t($descriptor[self::T_TITLE]);
		}

		asort($scope_options);

		#
		#
		#

		$targets = $scope_options;

		return array
		(
			WdElement::T_CHILDREN => array
			(
				'title' => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => 'Titre',
						WdElement::T_MANDATORY => true,

						'maxsize' => 64
					)
				),

				'id' => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => 'Identifiant',
						WdElement::T_MANDATORY => true,

						'maxsize' => 32
					)
				),

				'description' => new WdElement
				(
					'textarea', array
					(
						WdForm::T_LABEL => 'Description',

						'rows' => 4
					)
				),

				'scope' => new WdElement
				(
					'select', array
					(
						WdForm::T_LABEL => 'Module pour lequel activer l\'attachement',
						WdElement::T_MANDATORY => true,
						WdElement::T_OPTIONS => array(null => '') + $scope_options
					)
				),

				'target' => new WdElement
				(
					'select', array
					(
						WdForm::T_LABEL => 'Module depuis lequel des objets peuvent êtres attachés',
						WdElement::T_MANDATORY => true,
						WdElement::T_OPTIONS => array(null => '') + $targets
					)
				),

				'is_mandatory' => new WdElement
				(
					WdElement::E_CHECKBOX, array
					(
						WdElement::T_LABEL => 'L\'attachement est obligatoire'
					)
				)
			)
		);
	}

	protected function block_manage()
	{
		return new system_nodes_attachments_WdManager
		(
			$this, array
			(
				WdManager::T_COLUMNS_ORDER => array
				(
					'title', 'scope', 'target', 'is_mandatory'
				)
			)
		);
	}

	public function event_alter_block_edit(WdEvent $event)
	{
		if (!($event->module instanceof system_nodes_WdModule))
		{
			return;
		}

		global $core;

		$attachments = $this->model()->loadAll
		(
			'WHERE scope = ?', array
			(
				(string) $event->module
			)
		)
		->fetchAll();

		$children = array();
		$values = array();

		$nodes_model = $this->model('nodes');
		$nid = $event->key;

		foreach ($attachments as $attachment)
		{
			$module_id = $attachment->target;

			if (!$core->hasModule($module_id))
			{
				continue;
			}

			$id = $attachment->id;
			$attachmentid = $attachment->attachmentid;

			$name = 'system_nodes_attachments[' . $attachmentid . ']';

			$descriptor = $core->descriptors[$module_id];

			if ($module_id == 'resources.images')
			{
				$children[$name] = new WdPopImageElement
				(
					array
					(
						WdForm::T_LABEL => $attachment->title,
						WdElement::T_GROUP => 'attachments',
						WdElement::T_MANDATORY => $attachment->is_mandatory,
						WdElement::T_DESCRIPTION => 'Ci-dessus, la liste des objets qui peuvent
						être attachés depuis le module <a href="' . WdRoute::encode('/' . $module_id) . '">' . $descriptor[WdModule::T_TITLE] . '</a>.
						L\'objet attaché aura pour identifiant <em>' . $id . '</em>. ' . $attachment->description
					)
				);
			}
			else
			{
				$children[$name] = new WdElement
				(
					'select', array
					(
						WdForm::T_LABEL => $attachment->title,
						WdElement::T_GROUP => 'attachments',
						WdElement::T_MANDATORY => $attachment->is_mandatory,
						WdElement::T_DESCRIPTION => 'Ci-dessus, la liste des objets qui peuvent
						être attachés depuis le module <a href="' . WdRoute::encode('/' . $module_id) . '">' . $descriptor[WdModule::T_TITLE] . '</a>.
						L\'objet attaché aura pour identifiant <em>' . $id . '</em>. ' . $attachment->description,
						WdElement::T_OPTIONS => array(null => '') + $core->getModule($module_id)->model()->select
						(
							array('nid', 'title'), 'WHERE is_online = 1 ORDER BY title'
						)
						->fetchPairs()
					)
				);
			}

			$values['system_nodes_attachments'][$attachmentid] = $nodes_model->select
			(
				'targetid', 'WHERE attachmentid = ? AND nid = ?', array
				(
					$attachmentid,
					$nid
				)
			)
			->fetchColumnAndClose();
		}

		$event->tags = wd_array_merge_recursive
		(
			$event->tags, array
			(
				WdForm::T_VALUES => $values,

				WdElement::T_GROUPS => array
				(
					'attachments' => array
					(
						'title' => 'Attachments',
						'weight' => 600/*,
						'description' => 'Semper sed gravida eu lectus eros amet dapibus augue
						turpis id sit augue sit tristique adipiscing. Nunc Aenean non ipsum
						consequat aliquet luctus ipsum. Aliquam ut mi consectetur interdum
						luctus amet eu sit dolor dolor nisl nec neque.'*/
					)
				),

				WdElement::T_CHILDREN => $children
			)
		);
	}

	protected $attachments = array();

	public function event_ar_property(WdEvent $event)
	{
		if (!($event->ar instanceof system_nodes_WdActiveRecord))
		{
			return;
		}

		#
		#
		#

		$key = $event->ar->constructor . '-' . $event->property;

		if (!isset($this->attachments[$key]))
		{
			$this->attachments[$key] = $this->model()->loadRange
			(
				0, 1, 'WHERE scope = ? AND id = ?', array
				(
					$event->ar->constructor,
					$event->property
				)
			)
			->fetchAndClose();
		}

		$attachment = $this->attachments[$key];

//		wd_log('attachment: \1', array($attachment));

		if (!$attachment)
		{
			return;
		}

		// FIXME-20091208: Should I init the value if the attachement is mandatory ?

		$event->value = null;

		$node = $this->model('nodes')->loadRange
		(
			0, 1, 'WHERE attachmentid = ? AND nid = ?', array
			(
				$attachment->attachmentid,
				$event->ar->nid
			)
		)
		->fetchAndClose();

		if (!$node)
		{
			return;
		}

		global $core;

		$target = $core->getModule($attachment->target)->model()->load($node->targetid);

		$event->value = $target;
		$event->stop();
	}
}