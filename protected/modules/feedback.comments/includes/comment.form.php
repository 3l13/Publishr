<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class feedback_comments_WdForm extends Wd2CForm
{
	public function __construct($tags, $dummy=null)
	{
		global $core;

		$is_member = $core->user_id != 0;
		$values = array();

		if ($is_member)
		{
			$user = $core->user;

			$values[Comment::AUTHOR] = $user->name;
			$values[Comment::AUTHOR_EMAIL] = $user->email;
		}

		parent::__construct
		(
			wd_array_merge_recursive
			(
				$tags, array
				(
					WdForm::T_VALUES => $values,

					WdElement::T_CHILDREN => array
					(
						Comment::AUTHOR => new WdElement
						(
							WdElement::E_TEXT, array
							(
								WdForm::T_LABEL => 'Name',
								WdElement::T_REQUIRED => true,
								'readonly' => $is_member
							)
						),

						Comment::AUTHOR_EMAIL => new WdElement
						(
							WdElement::E_TEXT, array
							(
								WdForm::T_LABEL => 'E-mail',
								WdElement::T_REQUIRED => true,
								WdElement::T_VALIDATOR => array(array('WdForm', 'validate_email')),
								'readonly' => $is_member
							)
						),

						Comment::AUTHOR_URL => new WdElement
						(
							WdElement::E_TEXT, array
							(
								WdForm::T_LABEL => 'URL'
							)
						),

						Comment::CONTENTS => new WdElement
						(
							'textarea', array
							(
								WdElement::T_REQUIRED => true,
								WdElement::T_LABEL_MISSING => 'Contents',
								'rows' => 8
							)
						),

						"Souhaitez-vous être informé par E-Mail d'une réponse à votre message&nbsp;?",

						Comment::NOTIFY => new WdElement
						(
							WdElement::E_RADIO_GROUP, array
							(
								WdElement::T_OPTIONS => array
								(
									'yes' => 'Bien sûr !',
									'author' => 'Seulement si c\'est l\'auteur du billet qui répond',
									'no' => 'Pas la peine, je viens tous les jours'
								),

								WdElement::T_DEFAULT => 'no',

								'class' => 'list'
							)
						)
					),

					'action' => '#respond'
				)
			)
		);
	}

	public function __toString()
	{
		global $document;

		$document->js->add('../public/comment.form.js');

		return parent::__toString();
	}

	public function finalize(WdOperation $operation)
	{
		global $core;

		$clone = clone $operation;

		$clone->name = WdModule::OPERATION_SAVE;
		$clone->destination = 'feedback.comments';

		$clone->dispatch();

		$rc = $clone->response->rc;

		if ($rc)
		{
			$comment = $core->models['feedback.comments'][$rc['key']];

			#
			# if the comment is approved we change the location to the comment location, otherwise
			# the location is changed to the location of the form element.
			#

			$operation->location = ($comment->status == 'approved') ? $comment->url : $_SERVER['REQUEST_URI'] . '#' . $operation->form_entry->slug;
			$operation->entry = $comment;
		}

		return $rc;
	}

	static public function get_defaults()
	{
		return array
		(
			'notify_subject' => 'Un nouveau commentaire a été posté sur #{$server.http.host}',
			'notify_from' => 'Comments <comments@#{$server.http.host}>',
			'notify_template' => <<<EOT
Bonjour,

Vous recevez ce message parce qu'un nouveau commentaire a été posté sur le site #{\$server.http.host} :

URL : #{@absolute_url}
Auteur : #{@author} <#{@author_email}>

#{this.strip_tags()}

EOT
		);
	}
}