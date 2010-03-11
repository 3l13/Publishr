<?php

class feedback_comments_WdModule extends WdPModule
{
	static $registry_notifies_response = array
	(
		'subject' => 'Notification de réponse au billet : #{@node.title}',
		'template' => 'Bonjour,

Vous recevez cet email parce que vous surveillez le billet "#{@node.title}" sur <nom_du_site>.
Ce billet a reçu une réponse depuis votre dernière visite. Vous pouvez utiliser le lien suivant
pour voir les réponses qui ont été faites :

http://<url_du_site>#{@url}

Aucune autre notification ne vous sera envoyée.

À bientôt sur <url_du_site>',
		'from' => 'VotreSite <no-reply@votre_site.com>'
	);

	public function install()
	{
		global $registry;

		$registry->set
		(
			'feedback.comments', array
			(
				'notifies' => array
				(
					'response' => self::$registry_notifies_response
				)
			)
		);

		return parent::install();
	}

	/*
	protected function control_form(WdOperation $operation)
	{
		$form = new Wd2CForm
		(
			array
			(
				WdElement::T_CHILDREN => array
				(
					Comment::AUTHOR => new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'Nom',
							WdElement::T_MANDATORY => true
						)
					),

					Comment::AUTHOR_EMAIL => new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'E-Mail',
							WdElement::T_MANDATORY => true,
							WdElement::T_VALIDATOR => array(array('WdForm', 'validate_email'))
						)
					),

					Comment::CONTENTS => new WdElement
					(
						'textarea', array
						(
							WdForm::T_LABEL => 'Commentaire',
							WdElement::T_MANDATORY => true
						)
					)
				),

				'id' => 'respond-form'
			)
		);

		if (!$form->validate($operation->params))
		{
			return false;
		}

		$operation->form = $form;

		return true;
	}
	*/

	protected function validate_operation_save(WdOperation $operation)
	{
		if (!parent::validate_operation_save($operation))
		{
			return false;
		}

		$params = &$operation->params;

		#
		# the article id is mandatory when creating a message
		#

		if (!$operation->key && empty($params[Comment::NID]))
		{
			$operation->form->log(Comment::NID, 'The node id is mandatory while creating a new comment');

			return false;
		}

		#
		#
		#

		global $user;

		if ($user->isGuest())
		{
			$score = $this->spamScore($params[Comment::CONTENTS], $params[Comment::AUTHOR_URL], $params[Comment::AUTHOR]);

			if ($score < 1)
			{
				$operation->form->log(Comment::CONTENTS, '@form.log.spam', array('%score' => $score));

				/*

				$mailer = new WdMailer
				(
					array
					(
						WdMailer::T_DESTINATION => 'contact@weirdog.com',
						WdMailer::T_FROM => 'WdBlog <notify@weirdog.com>',
						WdMailer::T_MESSAGE => $params[self::CONTENTS] . wd_dump($_SERVER),
						WdMailer::T_SUBJECT => 'rejected message with score ' . $score,
						WdMailer::T_TYPE => 'plain'
					)
				);

				$mailer->send();

				*/

				return false;
			}
		}

		return true;
	}

	protected function operation_save(WdOperation $operation)
	{
		if (!$operation->key)
		{
			$params = &$operation->params;

			global $user;

			if (!$user->isGuest())
			{
				$params[Comment::UID] = $user->uid;
				$params[Comment::AUTHOR] = $user->username;
				$params[Comment::AUTHOR_EMAIL] = $user->email;
			}
		}

		$rc = parent::operation_save($operation);

		if (isset($rc['key']))
		{
			$this->handleNotify($rc['key']);
		}

		if (!$operation->key && $rc)
		{
			$mailer = new WdMailer
			(
				array
				(
					WdMailer::T_DESTINATION => 'contact@weirdog.com',
					WdMailer::T_FROM => 'WdBlog <notify@weirdog.com>',
					WdMailer::T_MESSAGE => wd_dump($operation->params),
					WdMailer::T_SUBJECT => 'Un nouveau message sur le blog baby',
					WdMailer::T_TYPE => 'html'
				)
			);

			$mailer->send();
		}

		return $rc;
	}

	/*
	 *
	 * The 'preview' operation is used to give the user a visual feedback
	 * about the message he's typing.
	 *
	 */

	protected function validate_operation_preview(WdOperation $operation)
	{
		return !empty($operation->params['contents']);
	}

	protected function operation_preview(WdOperation $operation)
	{
		require_once WDPATRON_ROOT . 'includes/textmark.php';

		// TODO: filter <script> and href="javascript:

		$rc = $operation->params['contents'];
		$rc = Markdown($rc);

		return $rc;
	}

	/*
	**

	BLOCKS

	**
	*/

	protected function block_edit(array $properties, $permission)
	{
		return array
		(
			WdElement::T_GROUPS => array
			(
				'comment' => array()
			),

			WdElement::T_CHILDREN => array
			(
				Comment::AUTHOR => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => 'Author',
						WdElement::T_MANDATORY => true
					)
				),

				Comment::AUTHOR_EMAIL => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => 'E-mail',
						WdElement::T_MANDATORY => true
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
						WdForm::T_LABEL => 'Contents',
						WdElement::T_MANDATORY => true,

						'rows' => 10
					)
				),

				Comment::NOTIFY => new WdElement
				(
					WdElement::E_RADIO_GROUP, array
					(
						WdForm::T_LABEL => 'Notification',
						WdElement::T_DEFAULT => 'no',
						WdElement::T_OPTIONS => array
						(
							'yes' => 'Bien sûr !',
							'author' => 'Seulement si c\'est l\'auteur du billet qui répond',
							'no' => 'Pas la peine, je viens tous les jours'
						),

						'class' => 'list'
					)
				)
			)
		);
	}

	protected function block_manage()
	{
		return new feedback_comments_WdManager
		(
			$this, array
			(
				WdManager::T_COLUMNS_ORDER => array
				(
					'created', 'score', 'author', 'nid'
				)
			)
		);
	}

	protected function block_config($base)
	{
		return array
		(
			WdElement::T_GROUPS => array
			(
				'response' => array
				(
					'title' => 'Message de notification lors d\'une réponse',
					'no-panels' => true
				),

				'spam' => array
				(
					'title' => 'Paramètres du filtre anti-spam'
				)
			),

			WdElement::T_CHILDREN => array
			(
				$base . '[notifies][response]' => new WdEMailNotifyElement
				(
					array
					(
						WdElement::T_GROUP => 'response',
						WdElement::T_DEFAULT => self::$registry_notifies_response
					)
				),

				$base . '[spam][urls]' => new WdElement
				(
					'textarea', array
					(
						WdForm::T_LABEL => 'URLs',
						WdElement::T_GROUP => 'spam',
						'rows' => 5
					)
				),

				$base . '[spam][keywords]' => new WdElement
				(
					'textarea', array
					(
						WdForm::T_LABEL => 'Mots clés',
						WdElement::T_GROUP => 'spam',
						'rows' => 5
					)
				)
			)
		);
	}

	protected static $spam_score_keywords;
	protected static $forbidden_urls;

	public function spamScore($contents, $url, $author)
	{
		global $registry;

		if (self::$spam_score_keywords === null)
		{
			$keywords = $registry->get('feedbackComments.spam.keywords');

			if ($keywords)
			{
				$keywords = explode(',', $keywords);
				$keywords = array_map('trim', $keywords);
			}
			else
			{
				$keywords = array();
			}

			self::$spam_score_keywords = $keywords;
		}

		$score = wd_spamScore($contents, $url, $author, self::$spam_score_keywords);

		#
		# additionnal contents restrictions
		#

		$score -= substr_count($contents, '[url=');

		#
		# additionnal author restrictions
		#

		if ($author{0} == '#')
		{
			$score -= 5;
		}

		#
		# additionnal url restrictions
		#

		if (self::$forbidden_urls === null)
		{
			$forbidden_urls = $registry->get('feedbackComments.spam.urls');

			if ($forbidden_urls)
			{
				$forbidden_urls = explode(',', $forbidden_urls);
				$forbidden_urls = array_map('trim', $forbidden_urls);
			}

			self::$forbidden_urls = $forbidden_urls;
		}

		if (self::$forbidden_urls)
		{
			foreach (self::$forbidden_urls as $forbidden)
			{
				if (strpos($contents . $url, $forbidden) !== false)
				{
					$score -= 5;
				}
			}
		}

		return $score;
	}

	protected function handleNotify($commentid)
	{
		//wd_log('search previous message with notify');

		$entries = $this->model()->loadAll
		(
			'WHERE `nid` = (SELECT `nid` FROM {self} WHERE `{primary}` = ?)
			AND `{primary}` < ? AND `{primary}` != ? AND `notify` != "no"', array
			(
				$commentid, $commentid, $commentid
			)
		)
		->fetchAll();

		if (!$entries)
		{
			return;
		}

		global $registry;

		$r = $registry->get('feedbackComments.notifies.response.');

		if (!$r)
		{
			wd_log_error('Unable to send notify, not defined');

			return;
		}

		#
		# load last comment
		#

		$comment = $this->model()->load($commentid);

		#
		# prepare message
		#

		$message = Patron($r['template'], $comment);
		$message = wordwrap($message);

		# subject

		$subject = Patron($r['subject'], $comment);

		$from = $r['from'];
		$bcc = $r['bcc'];

		foreach ($entries as $entry)
		{
			#
			# notify only if the author of the node post a comment
			#

			if ($entry->notify == 'author' && $comment->uid != $comment->node->uid)
			{
				continue;
			}

			wd_log_done
			(
				'Send notify to %author (message n°%nid, mode: %notify)', array
				(
					'%author' => $entry->author,
					'%nid' => $entry->nid,
					'%notify' => $entry->notify
				)
			);

			#
			# send message
			#

			$mailer = new WdMailer
			(
				array
				(
					WdMailer::T_DESTINATION => $entry->author_email,
					WdMailer::T_FROM => $from,
					WdMailer::T_BCC => $bcc,
					WdMailer::T_MESSAGE => $message,
					WdMailer::T_SUBJECT => $subject,
					WdMailer::T_TYPE => 'plain'
				)
			);

			if (!$mailer->send())
			{
				wd_log_error('Unable to send notify to %author', array('%author' => $entry->author));

				continue;
			}

			#
			# clean notify
			#

			$entry->notify = 0;

			$entry->save();
		}
	}
}