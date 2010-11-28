<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class feedback_comments_WdHooks
{
	static public function before_operation_save(WdEvent $event)
	{
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
		global $core;

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
<tr><td colspan="4">{\${$metas_prefix}[template]}<button type="button" class="reset small warn" value="/api/feedback.forms/defaults?which=reply">Valeurs par défaut</button></td></tr>
</table>
</div>
EOT
					)
				)
			)
		);
	}







	static public function get_comments(system_nodes_WdActiveRecord $ar)
	{
		global $core;

		return $core->models['feedback.comments']->loadAll
		(
			'WHERE nid = ? AND status = "approved" ORDER by created', array
			(
				$ar->nid
			)
		)
		->fetchAll();
	}

	static public function get_comments_count(system_nodes_WdActiveRecord $ar)
	{
		global $core;

		return $core->models['feedback.comments']->count
		(
			null, null, 'WHERE nid = ? AND status = "approved"', array
			(
				$ar->nid
			)
		);
	}

	static public function dashboard_last()
	{
		global $core, $document;

		if (!$core->hasModule('feedback.comments'))
		{
			return;
		}

		$document->css->add('public/dashboard.css');

		$entries = $core->models['feedback.comments']->order('created DESC')->limit(5);

		$rc = '';

		foreach ($entries as $entry)
		{
			$url = $entry->url;
			$author = wd_entities($entry->author);

			if ($entry->author_url)
			{
				$author = '<a href="' . wd_entities($entry->author_url) . '">' . $author . '</a>';
			}
			else
			{
				$author = '<strong>' . $author . '</strong>';
			}

			$contents = (string) $entry;
			$excerpt = wd_excerpt((string) $entry, 30);

			$target_edit_url = '#';
			$target_title = wd_entities(wd_shorten($entry->node->title));

			$image = wd_entities($entry->author_icon);

			$entry_class = $entry->status == 'spam' ? 'spam' : '';
			$url_edit = "/admin/feedback.comments/$entry->commentid/edit";

			$rc .= <<<EOT
<div class="entry $entry_class">

	<div class="header light">
	<a href="$url" class="out no-text">voir sur le site</a>
	De $author
	sur <a href="$target_edit_url">$target_title</a>

	<span class="more-auto small">
		<a href="$url_edit">Éditer</a>,
		<a href="#delete" class="danger">Supprimer</a>,
		<a href="#spam" class="warn">Spam</a>
	</span>
	</div>

	<img src="$image&amp;s=48" alt="" />

	<div class="contents">
		<div class="comment">$excerpt</div>

	</div>
</div>
EOT;
		}

		$rc .= '<div class="list"><a href="/admin/feedback.comments">Tous les commentaires</a></div>';

		return $rc;
	}
}