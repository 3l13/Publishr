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
	protected function operation_save(WdOperation $operation)
	{
		$operation->handle_booleans(array('is_notify'));

		return parent::operation_save($operation);
	}

	const OPERATION_SEND = 'send';
	const OPERATION_SEND_ID = '#formId';

	/**
	 * Returns the controls for the "send" operation.
	 *
	 * @param WdOperation $operation
	 * @return array The controls for the "send" operation.
	 */

	protected function get_operation_send_controls(WdOperation $operation)
	{
		return array
		(
			self::CONTROL_FORM => true,
			self::CONTROL_VALIDATOR => false
		);
	}

	/**
	 * Controls the "send" operation's form.
	 *
	 * The OPERATION_SEND_ID is required in the operation's params to retrieve the corresponding
	 * form.
	 *
	 * The operation object is altered by setting two properties:
	 *
	 * 1. `form`: the record's form object. This property is set for the `control_operation_form()`
	 * method to use.
	 * 2. `record`: the record itself.
	 *
	 * @param WdOperation $operation
	 * @throws WdMissingRecordException if the record specified by the OPERATION_SEND_ID
	 * operation's parameter cannot be found.
	 * @throws WdException if OPERATION_SEND_ID parameter is empty.
	 * @return boolean Whether or not the form validation was successful.
	 */

	protected function control_operation_send_form(WdOperation $operation)
	{
		$params = &$operation->params;

		if (empty($params[self::OPERATION_SEND_ID]))
		{
			throw new WdException('Missing OPERATION_SEND_ID parameter', array(), 404);
		}

		$form_id = (int) $params[self::OPERATION_SEND_ID];
		$record = $this->model[$form_id];

		$operation->record = $record;
		$operation->form = $record->form;

		return parent::control_operation_form($operation);
	}

	/**
	 * Sends the form.
	 *
	 * The operation object is altered by setting the following properties:
	 *
	 * 1. `notify_template`: The template used to create the notify message.
	 * 2. `notify_bind`: The bind used to resolve the notify template.
	 * 3. `notify_message`: The message resulting from the template resolving. This property is
	 * only set when the notify message has been sent.
	 *
	 *
	 * The `finalize` method
	 * =====================
	 *
	 * If defined, the `finalize` method of the form's model is invoked with the operation object
	 * as argument. Before the `finalize` method is invoked, the `entry` and `notify_message` of
	 * the operation object are set to `null`.
	 *
	 * If the result of the `finalize` method and the `is_notify` property of the record are not
	 * empty, an email is sent using the `notify_<identifier>` properties. The properties are
	 * resolved using the `Patron()` function and the operation's params, or, if defined, the
	 * value of the `entry` property of the operation object, as bind.
	 *
	 * If the `notify_message` property of the operation object is defined, it's used for the
	 * email's message, otherwise a message is created by resolving the record's `notify_template`
	 * property's value.
	 *
	 *
	 * Result tracking
	 * ===============
	 *
	 * The result of the "send" operation is stored is the session under
	 * "[modules][feedback.forms][rc][<record_nid>]". This stored value is used when the form is
	 * rendered to choose what to render. For example, if the value is empty, the form is rendered
	 * with the _before_ and _after_ messages, otherwise only the _complete_ message is rendered.
	 *
	 * Note: The result of the "send" operation is always `true` if the form's model class doesn't
	 * provied a `finalize` method.
	 *
	 * @param WdOperation $operation
	 * @return mixed The result of the operation is empty if the operation failed.
	 */

	protected function operation_send(WdOperation $operation)
	{
		global $core;

		$record = $operation->record;
		$form = $operation->form;

		$operation->notify_template = $record->notify_template;
		$operation->notify_bind = &$operation->params;

		$rc = method_exists($form, 'finalize') ? $form->finalize($operation) : true;

		if (isset($operation->entry))
		{
			throw new WdException("Operation's entry property is set, this is deprecated, use the notify_bind property if you whish to alter the resolving bind");
		}

		if ($rc && $record->is_notify)
		{
			$bind = $operation->notify_bind;
			$message = $operation->notify_message = Patron($operation->notify_template, $bind);

			$mailer = new WdMailer
			(
				array
				(
					WdMailer::T_DESTINATION => Patron($record->notify_destination, $bind),
					WdMailer::T_FROM => Patron($record->notify_from, $bind),
					WdMailer::T_BCC => Patron($record->notify_bcc, $bind),
					WdMailer::T_SUBJECT => Patron($record->notify_subject, $bind),
					WdMailer::T_MESSAGE => $message
				)
			);

			wd_log('operation send mailer: \1', array($mailer));

			$mailer->send();
		}

		$core->session->modules['feedback.forms']['rc'][$record->nid] = $rc;

		return $rc;
	}

	/**
	 * The _defaults_ opération can be used to retrieve the default values for the form, usualy
	 * the values for the notify feature.
	 */

	const OPERATION_DEFAULTS = 'defaults';

	protected function get_operation_defaults_controls(WdOperation $operation)
	{
		return array
		(
			self::CONTROL_AUTHENTICATION => true,
			self::CONTROL_PERMISSION => self::PERMISSION_CREATE
		);
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