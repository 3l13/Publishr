<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

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

		$entry = $this->model()->load($form_id);

		if (!$entry)
		{
			wd_log_error('Unknown formId: %id', array('%id' => $form_id));

			return false;
		}

		$operation->form = $entry->form;
		$operation->entry = $entry;

		return $entry->form->validate($params);
	}

	protected function validate_operation_send(WdOperation $operation)
	{
		return true;
	}

	protected function operation_send(WdOperation $operation)
	{
		#
		# the descriptor is loaded during the validation process
		#

		$rc = null;
		$entry = $operation->entry;
		
		if (isset($entry->model['finalize']))
		{
			$finalize = $entry->model['finalize'];
	
			if ($finalize == 'email')
			{
				$message = Patron($entry->config['template'], $operation->params);
	
				$mailer = new WdMailer
				(
					$entry->config + array
					(
						WdMailer::T_MESSAGE => $message
					)
				);
	
				$rc = $mailer->send();
			}
			else if ($finalize)
			{
				$rc = call_user_func($finalize, $operation);
			}
		}
		else
		{
			$rc = $entry->form->finalize($operation);
		}
		
		$_SESSION['feedback.forms.rc'][$entry->nid] = $rc;
		
		return $rc;
	}

	protected function block_manage()
	{
		return new feedback_forms_WdManager
		(
			$this, array
			(
				WdManager::T_COLUMNS_ORDER => array('title', 'modelid', 'uid', 'is_online', 'modified')
			)
		);
	}

	protected function block_edit(array $properties, $permission)
	{
		$models = WdCore::getConstructedConfig('formmodels', 'merge');
		$models_options = array();

		if ($models)
		{
			foreach ($models as $modelid => $model)
			{
				$models_options[$modelid] = $model['title'];
			}

			asort($models_options);
		}

		$config = array();
		$config_callback = null;

		$model = null;
		$modelid = $properties['modelid'];

		if ($modelid)
		{
			if (isset($models[$modelid]))
			{
				$model = $models[$modelid];

				if (method_exists($model['class'], 'getConfig'))
				{
					$config_callback = array($model['class'], 'getConfig');
				}
			}

			if ($properties[Form::SERIALIZED_CONFIG])
			{
				$config = array('config' => unserialize($properties[Form::SERIALIZED_CONFIG]));
			}
		}

		return wd_array_merge_recursive
		(
			parent::block_edit($properties, $permission), array
			(
				WdForm::T_VALUES => $config,

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
							WdElement::T_OPTIONS => array(null => '') + $models_options
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

			$config_callback ? call_user_func($config_callback) : array()
		);
	}
}