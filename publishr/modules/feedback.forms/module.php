<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class feedback_forms_WdModule extends system_nodes_WdModule
{
	const OPERATION_SEND = 'send';
	const OPERATION_SEND_ID = '#form-id';
	const OPERATION_DEFAULTS = 'defaults';

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