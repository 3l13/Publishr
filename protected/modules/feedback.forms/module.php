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
	const OPERATION_DEFAULTS = 'defaults';

	protected function get_operation_send_controls(WdOperation $operation)
	{
		return array
		(
			self::CONTROL_FORM => true,
			self::CONTROL_VALIDATOR => true
		);
	}

	protected function get_operation_defaults_controls(WdOperation $operation)
	{
		return array
		(
			self::CONTROL_AUTHENTICATION => true,
			self::CONTROL_PERMISSION => self::PERMISSION_CREATE
		);
	}

	protected function control_operation_send_form(WdOperation $operation)
	{
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
			throw new WdException('Unknown formId: %id', array('%id' => $form_id), 404);
		}

		$operation->form = $entry->form;
		$operation->form_entry = $entry;

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
		global $core;

		#
		# the descriptor is loaded during the validation process
		#

		$form = $operation->form;
		$entry = $operation->form_entry;

		$operation->entry = null;

		$rc = method_exists($form, 'finalize') ? $form->finalize($operation) : true;

//		wd_log('finalize result: \1', array($rc));

		if ($rc && $entry->is_notify)
		{
			$params = $operation->entry ? $operation->entry : $operation->params;

			$mailer = new WdMailer
			(
				array
				(
					WdMailer::T_DESTINATION => Patron($entry->notify_destination, $params),
					WdMailer::T_FROM => Patron($entry->notify_from, $params),
					WdMailer::T_BCC => Patron($entry->notify_bcc, $params),
					WdMailer::T_SUBJECT => Patron($entry->notify_subject, $params),
					WdMailer::T_MESSAGE => Patron($entry->notify_template, $params)
				)
			);

//			wd_log('mailer: \1', array($mailer));

			$mailer->send();
		}

		$core->session->modules['feedback.forms']['rc'][$entry->nid] = $rc;

		return $rc;
	}

	protected function validate_operation_defaults(WdOperation $operation)
	{
		if (!$operation->key)
		{
			wd_log_error('Missing modelid');

			return false;
		}

		return true;
	}

	protected function operation_defaults(WdOperation $operation)
	{
		$modelid = $operation->key;
		$models = WdConfig::get_Constructed('formmodels', 'merge');

		if (empty($models[$modelid]))
		{
			wd_log_error("Unknown model");

			return;
		}

		$model = $models[$modelid];
		$model_class = $model['class'];

		if (!method_exists($model_class, 'get_defaults'))
		{
			wd_log_done("Model doesn't have defaults");

			return false;
		}

		return call_user_func(array($model_class, 'get_defaults'));
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
		global $core, $document;

		$document->css->add('public/edit.css');
		$document->js->add('public/edit.js');

		$models = WdConfig::get_constructed('formmodels', 'merge');
		$models_options = array();

		if ($models)
		{
			foreach ($models as $modelid => $model)
			{
				$models_options[$modelid] = $model['title'];
			}

			asort($models_options);
		}

		return wd_array_merge_recursive
		(
			parent::block_edit($properties, $permission), array
			(
				WdElement::T_GROUPS => array
				(
					'messages' => array
					(
						'title' => 'Messages accompagnant le formulaire',
						'class' => 'form-section flat'
					),

					'notify' => array
					(
						'title' => 'Options de notification',
						'class' => 'form-section flat'
					),

					'operation' => array
					(
						'title' => 'Opération &amp; configuration'
					)
				),

				WdElement::T_CHILDREN => array
				(
					'modelid' => new WdElement
					(
						'select', array
						(
							WdForm::T_LABEL => 'Modèle du formulaire',
							WdElement::T_REQUIRED => true,
							WdElement::T_OPTIONS => array(null => '') + $models_options,
							WdElement::T_LABEL_POSITION => 'before'
						)
					),

					'pageid' => new WdPageSelectorElement
					(
						'select', array
						(
							WdForm::T_LABEL => 'Page sur laquelle s\'affiche le formulaire',
							WdElement::T_LABEL_POSITION => 'before'
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
							WdElement::T_REQUIRED => true,
							WdElement::T_DESCRIPTION => "Il s'agit du message affiché une fois le
							formulaire posté avec succés.",
							WdElement::T_DEFAULT => '<p>Votre message a été envoyé.</p>',

							'rows' => 5
						)
					),

					#
					# notify
					#

					'notify' => new WdTemplatedElement
					(
						'div', array
						(
							WdElement::T_GROUP => 'notify',
							WdElement::T_CHILDREN => array
							(
								'is_notify' => new WdElement
								(
									WdElement::E_CHECKBOX, array
									(
										WdElement::T_LABEL => 'Activer la notification',
										WdElement::T_GROUP => 'notify',
										WdElement::T_DESCRIPTION => "Cette option déclanche l'envoi
										d'un email lorsqu'un formulaire est posté avec succès."
									)
								),

								'notify_destination' => new WdElement
								(
									WdElement::E_TEXT, array
									(
										WdForm::T_LABEL => 'Adresse de destination',
										WdElement::T_GROUP => 'notify',
										WdElement::T_DEFAULT => $core->user->email
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
										WdElement::T_GROUP => 'notify'
									)
								)
							)
						),

						<<<EOT
<div class="panel">
<div class="form-element is_notify">{\$is_notify}</div>
<table>
<tr><td class="label">{\$notify_from.label:}</td><td>{\$notify_from}</td><td colspan="2">&nbsp;</td></tr>
<tr><td class="label">{\$notify_destination.label:}</td><td>{\$notify_destination}</td>
<td class="label">{\$notify_bcc.label:}</td><td>{\$notify_bcc}</td></tr>
<tr><td class="label">{\$notify_subject.label:}</td><td colspan="3">{\$notify_subject}</td></tr>
<tr><td colspan="4">{\$notify_template}<button class="reset small warn" type="button" value="/api/feedback.forms/%modelid/defaults">Valeurs par défaut</button>

<div class="element-description">
Le sujet du message et le corps du message
sont formatés par <a href=\"http://github.com/Weirdog/WdPatron\" target=\"_blank\">WdPatron</a>,
utilisez ses fonctionnalités avancées pour les personnaliser.
</div>
</td></tr>
</table>
</div>
EOT
					)
				)
			)
		);
	}
}