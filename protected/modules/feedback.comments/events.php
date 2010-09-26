<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class feedback_comments_WdEvents
{
	static public function before_operation_save(WdEvent $event)
	{
		if (!($event->module instanceof feedback_forms_WdModule))
		{
			return;
		}

		$params = &$event->operation->params;

		if (isset($params['metas']['feedback_comments/reply']))
		{
			$metas = &$params['metas']['feedback_comments/reply'];

			$metas += array
			(
				'is_notify' => null
			);

			$metas['is_notify'] = filter_var($metas['is_notify'], FILTER_VALIDATE_BOOLEAN);
		}
	}

	static public function operation_delete(WdEvent $event)
	{
		global $core;

		if (!($event->module instanceof system_nodes_WdModule))
		{
			return;
		}

		if (!$core->hasModule('feedback.comments'))
		{
			return;
		}

		try
		{
			$model = $core->models['feedback.comments'];
		}
		catch (Exception $e)
		{
			return;
		}

		$ids = $model->select
		(
			'{primary}', 'WHERE nid = ?', array($event->operation->key)
		)
		->fetchAll(PDO::FETCH_COLUMN);

		foreach ($ids as $commentid)
		{
			$model->delete($commentid);
		}
	}

	static public function alter_block_edit(WdEvent $event)
	{
		global $core, $app;

		if (!$event->module instanceof feedback_forms_WdModule)
		{
			return;
		}

		if (!$core->hasModule('feedback.comments'))
		{
			return;
		}

		$values = null;
		$key = 'feedback_comments/reply';
		$metas_prefix = 'metas[' . $key . ']';

		if ($event->entry)
		{
			$entry = $event->entry;

			$values = array
			(
				$metas_prefix => unserialize($entry->metas[$key])
			);
		}

		$event->tags = wd_array_merge_recursive
		(
			$event->tags, array
			(
				WdForm::T_VALUES => $values ? $values : array(),

				WdElement::T_CHILDREN => array
				(
					$key => new WdTemplatedElement
					(
						'div', array
						(
							WdElement::T_GROUP => 'notify',
							WdElement::T_CHILDREN => array
							(
								$metas_prefix . '[is_notify]' => new WdElement
								(
									WdElement::E_CHECKBOX, array
									(
										WdElement::T_LABEL => 'Activer la notification aux réponses',
										WdElement::T_DESCRIPTION => "Cette option déclanche l'envoi
										d'un email à l'auteur ayant choisi d'être informé d'une
										réponse à son commentaire."
									)
								),

								$metas_prefix . '[from]' => new WdElement
								(
									WdElement::E_TEXT, array
									(
										WdForm::T_LABEL => 'Adresse d\'expédition'
									)
								),

								$metas_prefix . '[bcc]' => new WdElement
								(
									WdElement::E_TEXT, array
									(
										WdForm::T_LABEL => 'Copie cachée'
									)
								),

								$metas_prefix . '[subject]' => new WdElement
								(
									WdElement::E_TEXT, array
									(
										WdForm::T_LABEL => 'Sujet du message'
									)
								),

								$metas_prefix . '[template]' => new WdElement
								(
									'textarea', array
									(
										WdForm::T_LABEL => 'Patron du message',
										WdElement::T_DESCRIPTION => "Le sujet du message et le corps du message
										sont formatés par <a href=\"http://github.com/Weirdog/WdPatron\" target=\"_blank\">WdPatron</a>,
										utilisez ses fonctionnalités avancées pour les personnaliser."
									)
								)
							)
						),

						<<<EOT
<div class="panel">
<div class="form-element is_notify">{\${$metas_prefix}[is_notify]}</div>
<table>
<tr><td class="label">{\${$metas_prefix}[from].label:}</td><td>{\${$metas_prefix}[from]}</td>
<td class="label">{\${$metas_prefix}[bcc].label:}</td><td>{\${$metas_prefix}[bcc]}</td></tr>
<tr><td class="label">{\${$metas_prefix}[subject].label:}</td><td colspan="3">{\${$metas_prefix}[subject]}</td></tr>
<tr><td colspan="4">{\${$metas_prefix}[template]}<button type="button" class="reset small warn" value="/do/feedback.comments/defaults?which=reply">Valeurs par défaut</button></td></tr>
</table>
</div>
EOT
					)
				)
			)
		);
	}
}