<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class feedback_forms__send_WdOperation extends WdOperation
{
	/**
	 * Controls for the operation: form.
	 *
	 * @see WdOperation::__get_controls()
	 */
	protected function __get_controls()
	{
		return array
		(
			self::CONTROL_FORM => true
		)

		+ parent::__get_controls();
	}

	/**
	 * The OPERATION_SEND_ID is required in the operation's params to retrieve the corresponding
	 * form.
	 *
	 * The operation object is altered by setting two properties:
	 *
	 * 1. `form`: the record's form object. This property is set for the `control_operation_form()`
	 * method to use.
	 * 2. `record`: the record itself.
	 *
	 * @param WdOperation $this
	 * @throws WdMissingRecordException when the record specified by the OPERATION_SEND_ID
	 * operation's parameter cannot be found.
	 * @throws WdException when the OPERATION_SEND_ID parameter is empty.
	 * @return boolean Whether or not the form validation was successful.
	 */
	protected function control(array $controls)
	{
		$params = $this->params;

		if (empty($params[feedback_forms_WdModule::OPERATION_SEND_ID]))
		{
			throw new WdException('Missing OPERATION_SEND_ID parameter', array(), 404);
		}

		$form_id = (int) $params[feedback_forms_WdModule::OPERATION_SEND_ID];
		$record = $this->module->model[$form_id];

		$this->record = $record;
		$this->form = $record->form;

		return parent::control($controls);
	}

	protected function validate()
	{
		return true;
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
	 * The result of the "send" operation is stored in the session under
	 * "[modules][feedback.forms][rc][<record_nid>]". This stored value is used when the form is
	 * rendered to choose what to render. For example, if the value is empty, the form is rendered
	 * with the _before_ and _after_ messages, otherwise only the _complete_ message is rendered.
	 *
	 * Note: If the form's model class doesn't provied a `finalize` method, the result of the
	 * operation is always `true`.
	 *
	 * @param WdOperation $this
	 * @return mixed The result of the operation is empty if the operation failed.
	 */
	protected function process()
	{
		global $core;

		$record = $this->record;
		$form = $this->form;

		$this->notify_template = $record->notify_template;
		$this->notify_bind = $this->params;

		$rc = method_exists($form, 'finalize') ? $form->finalize($this) : true;
		$core->session->modules['feedback.forms']['rc'][$record->nid] = $rc;

		if ($rc && $record->is_notify)
		{
			$bind = $this->notify_bind;
			$message = isset($this->notify_message) ? $this->notify_message : Patron($this->notify_template, $bind);

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

		return $rc;
	}
}