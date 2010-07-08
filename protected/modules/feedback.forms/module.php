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
		$operation->form_model = $entry;

		return $entry->form->validate($params);
	}

	protected function operation_save(WdOperation $operation)
	{
		$operation->handle_booleans(array('is_notify'));

		return parent::operation_save($operation);
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

		$entry = $operation->form_model;

		$rc = method_exists($entry->form, 'finalize') ? $entry->form->finalize($operation) : true;

		wd_log('finalize result: \1', array($rc));

		if ($rc && $entry->is_notify)
		{
			$params = &$operation->params;

			$mailer = new WdMailer
			(
				array
				(
					WdMailer::T_DESTINATION => $entry->notify_destination,
					WdMailer::T_FROM => $entry->notify_from,
					WdMailer::T_BCC => $entry->notify_bcc,
					WdMailer::T_SUBJECT => Patron($entry->notify_subject, $params),
					WdMailer::T_MESSAGE => Patron($entry->notify_template, $params)
				)
			);

			wd_log('mailer: \1', array($mailer));

			$mailer->send();
		}

		global $app;

		$app->session->modules['feedback.forms']['rc'][$entry->nid] = $rc;

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
		global $app, $document;

		$document->css->add('public/edit.css');
		$document->js->add('public/edit.js');

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
		$defaults_callback = null;

		$model = null;
		$modelid = $properties['modelid'];

		if ($modelid)
		{
			if (isset($models[$modelid]))
			{
				$model = $models[$modelid];
				$class = $model['class'];

				if (class_exists($class))
				{
					if (method_exists($class, 'getConfig'))
					{
						$config_callback = array($class, 'getConfig');
					}

					if (method_exists($class, 'get_defaults'))
					{
						$defaults_callback = array($class, 'get_defaults');
					}
				}
				else
				{
					wd_log_error('Model class %class does not exists', array('%class' => $class));
				}
			}

			if ($properties[Form::SERIALIZED_CONFIG])
			{
				$config = array('config' => unserialize($properties[Form::SERIALIZED_CONFIG]));
			}
		}

		$tags = wd_array_merge_recursive
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

					'notify' => array
					(
						'title' => 'Options de notification',
						'class' => 'form-section panel',
						'template' => <<<EOT

						<div style="margin: 1em">
<div class="form-element is_notify">{\$is_notify}</div>
<table>
<tr><td class="label">{\$notify_from.label:}</td><td>{\$notify_from}</td><td colspan="2">&nbsp;</td></tr>
<tr><td class="label">{\$notify_destination.label:}</td><td>{\$notify_destination}</td>
<td class="label">{\$notify_bcc.label:}</td><td>{\$notify_bcc}</td></tr>
<tr><td class="label">{\$notify_subject.label:}</td><td colspan="3">{\$notify_subject}</td></tr>
<tr><td colspan="4">{\$notify_template}</td></tr>
</table>
</div>
EOT
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
							formulaire posté avec succés.",
							WdElement::T_DEFAULT => '<p>Votre message a été envoyé.</p>',

							'rows' => 5
						)
					),

					#
					# notify
					#

					'is_notify' => new WdElement
					(
						WdElement::E_CHECKBOX, array
						(
							WdElement::T_LABEL => 'Activer la notification',
							WdElement::T_GROUP => 'notify',
							WdElement::T_DESCRIPTION => "La notification déclanche l'envoi d'un
							email lorsqu'un formulaire est posté avec succès."
						)
					),

					'notify_destination' => new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'Adresse de destination',
							WdElement::T_GROUP => 'notify',
							WdElement::T_DEFAULT => $app->user->email
						)
					),

					'notify_from' => new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'Adresse d\'expédition',
							WdElement::T_GROUP => 'notify'
						)
					),

					'notify_bcc' => new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'Copie cachée',
							WdElement::T_GROUP => 'notify'
						)
					),

					'notify_subject' => new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'Sujet du message',
							WdElement::T_GROUP => 'notify'
						)
					),

					'notify_template' => new WdElement
					(
						'textarea', array
						(
							WdForm::T_LABEL => 'Patron du message',
							WdElement::T_GROUP => 'notify',
							WdElement::T_DESCRIPTION => "Le corps du message ainsi que son sujet
							sont formatés par <a href=\"http://github.com/Weirdog/WdPatron\" target=\"_blank\">WdPatron</a>,
							utilisez ses fonctionnalités avancées pour les personnaliser."
						)
					),
				)
			)
		);

		if ($defaults_callback)
		{
			$defaults = call_user_func($defaults_callback);

			//wd_log('defaults: \1', array($defaults));

			foreach ($defaults as $name => $value)
			{
				$element = $tags[WdElement::T_CHILDREN][$name];

				if (!empty($properties[$name]))
				{
					continue;
				}

				$element->set('value', $value);
			}
		}

		if (!$config_callback)
		{
			return $tags;
		}

		return wd_array_merge_recursive
		(
			$tags, call_user_func($config_callback, $tags)
		);
	}
}