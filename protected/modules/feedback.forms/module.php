<?php

class feedback_forms_WdModule extends system_nodes_WdModule
{
	const OPERATION_SEND = 'send';
	const OPERATION_SEND_ID = '#formId';

	protected function getOperationsAccessControls()
	{
		return array
		(
			self::OPERATION_SEND => array
			(
				self::CONTROL_FORM => true,
				self::CONTROL_VALIDATOR => true
			)
		)

		+ parent::getOperationsAccessControls();
	}

	protected function control_form(WdOperation $operation)
	{
		if ($operation->name != self::OPERATION_SEND)
		{
			return parent::control_form($operation);
		}

		$params = &$operation->params;

		if (empty($params[self::OPERATION_SEND_ID]))
		{
			wd_log_error('Missing formId');

			return false;
		}

		$form_id = $params[self::OPERATION_SEND_ID];

		$descriptor = $this->model()->load($form_id);

		if (!$descriptor)
		{
			wd_log_error('Unknown formId: %id', array('%id' => $form_id));

			return false;
		}

		$tags = $descriptor->model->tags + array
		(
			'name' => $descriptor->slug
		);

		$class = 'WdForm';

		if (isset($descriptor->model->class))
		{
			$class = $descriptor->model->class;
		}

		$form = new $class($tags);

		$operation->form = $form;
		$operation->descriptor = $descriptor;

		return $form->validate($params);
	}

	protected function validate_operation_send(WdOperation $operation)
	{
		return true;
	}

	protected function operation_send(WdOperation $operation)
	{
		$params = &$operation->params;

		#
		# the descriptor is loaded during the validation process
		#

		$descriptor = $operation->descriptor;
		$finalize = $descriptor->model->finalize;

		if ($finalize == 'email')
		{
			$message = Patron($descriptor->config['template'], $params);

			$mailer = new WdMailer
			(
				$descriptor->config + array
				(
					WdMailer::T_MESSAGE => $message
				)
			);

			$rc = $mailer->send();

			if (!$rc)
			{
				$operation->form->log(null, 'Unable to send message: Failed to connect to mail server.');
			}

			return $rc;
		}

		return call_user_func($finalize, $operation);
	}

	protected function block_manage()
	{
		return new feedback_forms_WdManager
		(
			$this, array
			(
				WdManager::T_COLUMNS_ORDER => array('title', 'url', 'uid', 'modified', 'is_online')
			)
		);
	}

	static public function getModel($modelId)
	{
		global $core, $user;

		$models = feedback_forms_WdActiveRecord::$formModels;

		if (empty($models[$modelId]))
		{
			WdDebug::trigger('Unknown model Id %modelid', array('%modelid' => $modelId));

			return;
		}

		return (object) require 'models/' . $models[$modelId] . '.php';
	}

	protected function block_edit(array $properties, $permission)
	{
		global $user;

		$values = array();
		$model = null;

		if ($properties['modelid'])
		{
			$model = self::getModel($properties['modelid']);

			if ($properties['serializedconfig'])
			{
				$values = array('config' => unserialize($properties['serializedconfig']));
			}
		}

		return wd_array_merge_recursive
		(
			parent::block_edit($properties, $permission), array
			(
				WdElement::T_GROUPS => array
				(
					'messages' => array
					(
						'title' => 'Messages accompagnant le formulaire'
					),

					'operation' => array
					(
						'title' => 'Opération &amp; configuration'
					),

					'config' => array
					(
						'title' => 'Configuration'
					)
				),

				WdElement::T_CHILDREN => array
				(
					'modelid' => new WdElement
					(
						'select', array
						(
							WdForm::T_LABEL => 'Modèle du formulaire',
							WdElement::T_MANDATORY => true,
							WdElement::T_OPTIONS => array(null => '') + feedback_forms_WdActiveRecord::$formModels
						)
					),

					'pageid' => new WdPageSelectorElement
					(
						'select', array
						(
							WdForm::T_LABEL => 'Page sur laquelle s\'affiche le formulaire'
						)
					),

					'before' => new moo_WdEditorElement
					(
						array
						(
							WdForm::T_LABEL => 'Message précédant le formulaire',
							WdElement::T_GROUP => 'messages',

							'rows' => 5
						)
					),

					'after' => new moo_WdEditorElement
					(
						array
						(
							WdForm::T_LABEL => 'Message suivant le formulaire',
							WdElement::T_GROUP => 'messages',

							'rows' => 5
						)
					),

					'complete' => new moo_WdEditorElement
					(
						array
						(
							WdForm::T_LABEL => 'Message de remerciement',
							WdElement::T_GROUP => 'messages',
							WdElement::T_MANDATORY => true,
							WdElement::T_DESCRIPTION => "Il s'agit du message affiché une fois le
							formulaire traité.",
							WdElement::T_DEFAULT => '<p>Votre message a été envoyé.</p>',

							'rows' => 5
						)
					)
				)
			),

			empty($model->config) ? array() : array
			(
				WdForm::T_VALUES => $values,
				WdElement::T_CHILDREN => $model->config
			)
		);
	}
}