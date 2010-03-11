<?php

class feedback_forms_WdMarkups extends patron_markups_WdHooks
{
	static protected function model($name='feedback.forms')
	{
		return parent::model($name);
	}

	static public function form(WdHook $hook, WdPatron $patron, $template)
	{
		$id = $hook->params['select'];

		$conditions = self::model()->parseConditions(array('slug' => $id, 'language' => WdLocale::$language));

		list($where, $params) = $conditions;

		$descriptor = self::model()->loadRange(0, 1, $where, $params)->fetchAndClose();

		if (!$descriptor)
		{
			$patron->error('Unable to retrieve form using supplied conditions: \1', array($hook->params));

			return;
		}

		if (!$descriptor->is_online)
		{
			$patron->error('The form %title is offline', array('%title' => $descriptor->title));

			return;
		}

		#
		# if the form was sent successfully, we return the `complete` message instead of the form.
		#

		$rc = WdOperation::getResult(feedback_forms_WdModule::OPERATION_SEND);

		if ($rc)
		{
			return $descriptor->complete;
		}

		#
		#
		#

		$tags = $descriptor->model->tags;
		$name = isset($tags['id']) ? $tags['id'] : $id;

		$tags = wd_array_merge_recursive
		(
			array
			(
				WdForm::T_VALUES => $_REQUEST,

				WdForm::T_HIDDENS => array
				(
					WdOperation::DESTINATION => 'feedback.forms',
					WdOperation::NAME => feedback_forms_WdModule::OPERATION_SEND,
					feedback_forms_WdModule::OPERATION_SEND_ID => $descriptor->nid
				),

				WdElement::T_CHILDREN => array
				(
					'#submit' => new WdElement
					(
						WdElement::E_SUBMIT, array
						(
							WdElement::T_WEIGHT => 1000,
							WdElement::T_INNER_HTML => 'Envoyer'
						)
					)
				),

				'name' => $id
			),

			$tags
		);

		$class = $template ? 'WdTemplatedForm' : 'Wd2CForm';

		if (isset($descriptor->model->class))
		{
			$class = $descriptor->model->class;
		}

		$form = $template ? new $class($tags, $patron->publish($template)) : new $class($tags);

		return $descriptor->before . $form . $descriptor->after;
	}
}