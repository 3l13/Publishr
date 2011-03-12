<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class feedback_forms_WdModule extends system_nodes_WdModule
{
	const OPERATION_SEND = 'send';
	const OPERATION_SEND_ID = '#formId';

	/**
	 * Returns the controls for the "send" operation.
	 *
	 * @param WdOperation $operation
	 * @return array The controls for the "send" operation.
	 */
	protected function controls_for_operation_send(WdOperation $operation)
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
	 * @throws WdMissingRecordException when the record specified by the OPERATION_SEND_ID
	 * operation's parameter cannot be found.
	 * @throws WdException when the OPERATION_SEND_ID parameter is empty.
	 * @return boolean Whether or not the form validation was successful.
	 */
	protected function control_form_for_operation_send(WdOperation $operation)
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

		return parent::control_form_for_operation($operation);
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
	 * as argument. Before the `finalize` method is invoked, the operation object is altered by
	 * adding the `notify_template` and `notify_bind` properties.
	 *
	 * The value of the `notify_template` property is set to the value of the form record
	 * `notify_template` property. The value of the property is used as template to format the
	 * message to send. One can overrite the value of the property to use a template different then
	 * the one defined by the form record.
	 *
	 * The value of the `notify_bind` property is set to the value of the operation `params`
	 * property. The `notify_bind` property is used as scope to format the template of the message
	 * to send. One can overrite the value of the property to use a different scope.
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
		$core->session->modules['feedback.forms']['rc'][$record->nid] = $rc;

		if (isset($operation->entry))
		{
			throw new WdException("Operation's <em>entry</em> property is set, this is deprecated, use the notify_bind property if you whish to alter the resolving bind");
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

			//wd_log('operation send mailer: \1', array($mailer));

			$mailer->send();
		}

		return $rc;
	}

	const OPERATION_DEFAULTS = 'defaults';

	protected function controls_for_operation_defaults(WdOperation $operation)
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

	/**
	 * The "defaults" operation can be used to retrieve the default values for the form, usualy
	 * the values for the notify feature.
	 */
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

		$label_default_values = t('Default values');
		$description_notify = t('description_notify', array(':link' => '<a href="http://github.com/Weirdog/WdPatron" target="_blank">WdPatron</a>'));

		return wd_array_merge_recursive
		(
			parent::block_edit($properties, $permission), array
			(
				WdElement::T_GROUPS => array
				(
					'messages' => array
					(
						'title' => '.messages',
						'class' => 'form-section flat'
					),

					'notify' => array
					(
						'title' => '.notify',
						'class' => 'form-section flat'
					),

					'operation' => array
					(
						'title' => '.operation'
					)
				),

				WdElement::T_CHILDREN => array
				(
					'modelid' => new WdElement
					(
						'select', array
						(
							WdForm::T_LABEL => '.modelid',
							WdElement::T_REQUIRED => true,
							WdElement::T_OPTIONS => array(null => '') + $models_options,
							WdElement::T_LABEL_POSITION => 'before'
						)
					),

					'pageid' => new WdPageSelectorElement
					(
						'select', array
						(
							WdForm::T_LABEL => '.pageid',
							WdElement::T_LABEL_POSITION => 'before'
						)
					),

					'before' => new moo_WdEditorElement
					(
						array
						(
							WdForm::T_LABEL => '.before',
							WdElement::T_GROUP => 'messages',

							'rows' => 5
						)
					),

					'after' => new moo_WdEditorElement
					(
						array
						(
							WdForm::T_LABEL => '.after',
							WdElement::T_GROUP => 'messages',

							'rows' => 5
						)
					),

					'complete' => new moo_WdEditorElement
					(
						array
						(
							WdForm::T_LABEL => '.complete',
							WdElement::T_GROUP => 'messages',
							WdElement::T_REQUIRED => true,
							WdElement::T_DESCRIPTION => '.complete',
							WdElement::T_DEFAULT => '<p>' . t('default.complete') . '</p>',

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
										WdElement::T_LABEL => '.is_notify',
										WdElement::T_GROUP => 'notify',
										WdElement::T_DESCRIPTION => '.is_notify'
									)
								),

								'notify_destination' => new WdElement
								(
									WdElement::E_TEXT, array
									(
										WdForm::T_LABEL => '.notify_destination',
										WdElement::T_GROUP => 'notify',
										WdElement::T_DEFAULT => $core->user->email
									)
								),

								'notify_from' => new WdElement
								(
									WdElement::E_TEXT, array
									(
										WdForm::T_LABEL => '.notify_from',
										WdElement::T_GROUP => 'notify'
									)
								),

								'notify_bcc' => new WdElement
								(
									WdElement::E_TEXT, array
									(
										WdForm::T_LABEL => '.notify_bcc',
										WdElement::T_GROUP => 'notify'
									)
								),

								'notify_subject' => new WdElement
								(
									WdElement::E_TEXT, array
									(
										WdForm::T_LABEL => '.notify_subject',
										WdElement::T_GROUP => 'notify'
									)
								),

								'notify_template' => new WdElement
								(
									'textarea', array
									(
										WdForm::T_LABEL => '.notify_template',
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
<tr><td colspan="4">{\$notify_template}<button class="reset small warn" type="button" value="/api/feedback.forms/%modelid/defaults">$label_default_values</button>

<div class="element-description">$description_notify</div>
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