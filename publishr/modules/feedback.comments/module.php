<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class feedback_comments_WdModule extends WdPModule
{
	/*
	static $notifies_response = array
	(
		'subject' => 'Notification de réponse au billet : #{@node.title}',
		'template' => 'Bonjour,

Vous recevez cet email parce que vous surveillez le billet "#{@node.title}" sur <nom_du_site>.
Ce billet a reçu une réponse depuis votre dernière visite. Vous pouvez utiliser le lien suivant
pour voir les réponses qui ont été faites :

#{@absolute_url}

Aucune autre notification ne vous sera envoyée.

À bientôt sur <url_du_site>',
		'from' => 'VotreSite <no-reply@votre_site.com>'
	);
	*/

	protected function validate_operation_save(WdOperation $operation)
	{
		global $core;

		if (!parent::validate_operation_save($operation))
		{
			return false;
		}

		$params = &$operation->params;

		#
		# the article id is required when creating a message
		#

		if (!$operation->key && empty($params[Comment::NID]))
		{
			$operation->form->log(Comment::NID, 'The node id is required while creating a new comment');

			return false;
		}

		#
		# validate IP
		#

		$ip = $_SERVER['REMOTE_ADDR'];

		if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE))
		{
			$operation->form->log(null, 'Adresse IP invalide&nbsp;: %ip', array('%ip' => $ip));

			return false;
		}

		#
		#
		#

		if (!$core->user_id)
		{
			$score = $this->spamScore($params[Comment::CONTENTS], $params[Comment::AUTHOR_URL], $params[Comment::AUTHOR]);

			if ($score < 1)
			{
				$operation->form->log(Comment::CONTENTS, '@form.log.spam', array('%score' => $score));

				return false;
			}

			#
			# delay between last post
			#

			$interval = $core->site->metas->get("$this->flat_id.delay", 5);

			$last = $this->model
			->select('created')
			->where
			(
				'(author = ? OR author_email = ? OR author_ip = ?) AND created + INTERVAL ? MINUTE > NOW()',
				$params['author'], $params['author_email'], $ip, $interval
			)
			->order('created DESC')
			->rc;

			if ($last)
			{
				$operation->form->log(null, "Les commentaires ne peuvent être fait à moins de $interval minutes d'intervale");

				return false;
			}
		}

		return true;
	}

	protected function control_properties_for_operation_save(WdOperation $operation)
	{
		global $core;

		$properties = parent::control_properties_for_operation_save($operation);

		if (!$operation->key)
		{
			$properties[Comment::AUTHOR_IP] = $_SERVER['REMOTE_ADDR'];

			$user = $core->user;

			if (!$user->is_guest())
			{
				$properties[Comment::UID] = $user->uid;
				$properties[Comment::AUTHOR] = $user->username;
				$properties[Comment::AUTHOR_EMAIL] = $user->email;
			}
		}

		if (!$core->user->has_permission(self::PERMISSION_MANAGE, $this))
		{
			$properties['status'] = null;
		}

		if (empty($params['status']))
		{
			$node = $core->models['system.nodes'][$params[Comment::NID]];
			$properties['status'] = $node->site->metas->get("$this->flat_id.default_status", 'pending');
		}

		return $properties;
	}

	protected function operation_save(WdOperation $operation)
	{
		$rc = parent::operation_save($operation);

		if (!$operation->key)
		{
			$this->handle_notify($operation, $rc['key']);
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
		return self::renderContents($operation->params['contents']);
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
			WdElement::T_CHILDREN => array
			(
				Comment::AUTHOR => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => 'Author',
						WdElement::T_REQUIRED => true
					)
				),

				Comment::AUTHOR_EMAIL => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => 'E-mail',
						WdElement::T_REQUIRED => true
					)
				),

				Comment::AUTHOR_URL => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => 'URL'
					)
				),

				new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => 'Adresse IP',

						'value' => $properties[Comment::AUTHOR_IP]
					)
				),

				Comment::CONTENTS => new WdElement
				(
					'textarea', array
					(
						WdForm::T_LABEL => 'Message',
						WdElement::T_REQUIRED => true,

						'rows' => 10
					)
				),

				Comment::NOTIFY => new WdElement
				(
					WdElement::E_RADIO_GROUP, array
					(
						WdForm::T_LABEL => 'Notification',
						WdElement::T_DEFAULT => 'no',
						WdElement::T_REQUIRED => true,
						WdElement::T_OPTIONS => array
						(
							'yes' => 'Bien sûr !',
							'author' => "Seulement si c'est l'auteur du billet qui répond",
							'no' => 'Pas la peine, je viens tous les jours',
							'done' => 'Notification envoyée'
						),

						WdElement::T_DESCRIPTION => (($properties[Comment::NOTIFY] == 'done') ? "Un
						message de notification a été envoyé." : null),

						'class' => 'list'
					)
				),

				Comment::STATUS => new WdElement
				(
					'select', array
					(
						WdForm::T_LABEL => 'Status',
						WdElement::T_REQUIRED => true,
						WdElement::T_OPTIONS => array
						(
							null => '',
							'pending' => 'Pending',
							'approved' => 'Aprouvé',
							'spam' => 'Spam'
						)
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
					'created', 'author', 'score', 'nid'
				),

				WdManager::T_ORDER_BY => array('created', 'desc'),

				feedback_comments_WdManager::T_LIST_SPAM => false
			)
		);
	}

	protected function block_manage_spam()
	{
		return new feedback_comments_WdManager
		(
			$this, array
			(
				WdManager::T_COLUMNS_ORDER => array
				(
					'created', 'author', 'score', 'nid'
				),

				WdManager::T_ORDER_BY => array('created', 'desc'),

				feedback_comments_WdManager::T_LIST_SPAM => true
			)
		);
	}

	protected function block_config()
	{
		global $core;

		// TODO-20101101: move this to operation `config`

		$keywords = $core->registry[$this->flat_id . '.spam.keywords'];
		$keywords = preg_split('#[\s,]+#', $keywords, 0, PREG_SPLIT_NO_EMPTY);

		sort($keywords);

		$keywords = implode(', ', $keywords);

		return array
		(
			WdForm::T_VALUES => array
			(
				"global[$this->flat_id.spam.keywords]" => $keywords
			),

			WdElement::T_GROUPS => array
			(
				'primary' => array
				(
					'title' => 'Général',
					'class' => 'form-section flat'
				),

				'response' => array
				(
					'title' => "Message de notification à l'auteur lors d'une réponse",
					'class' => 'form-section flat'
				),

				'spam' => array
				(
					'title' => 'Paramètres du filtre anti-spam',
					'class' => 'form-section flat',
					'description' => "Les paramètres du filtre anti-spam s'appliquent à tous les
					sites."
				)
			),

			WdElement::T_CHILDREN => array
			(
				"local[$this->flat_id.form_id]" => new WdFormSelectorElement
				(
					'select', array
					(
						WdForm::T_LABEL => 'Formulaire',
						WdElement::T_GROUP => 'primary',
						WdElement::T_REQUIRED => true,
						WdElement::T_DESCRIPTION => "Il s'agit du formulaire à utiliser pour la
						saisie des commentaires."
					)
				),

				"local[$this->flat_id.delay]" => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => 'Intervale entre deux commentaires',
						WdElement::T_LABEL => 'minutes',
						WdElement::T_DEFAULT => 3,

						'size' => 3,
						'style' => 'text-align: right'
					)
				),

				"local[$this->flat_id.default_status]" => new WdElement
				(
					'select', array
					(
						WdForm::T_LABEL => 'Status par défaut',
						WdElement::T_OPTIONS => array
						(
							'pending' => 'Pending',
							'approved' => 'Approuvé'
						),
						WdElement::T_DESCRIPTION => "Il s'agit du status par défaut pour les nouveaux
						commentaires."
					)
				),

				"global[$this->flat_id.spam.urls]" => new WdElement
				(
					'textarea', array
					(
						WdForm::T_LABEL => 'URLs',
						WdElement::T_GROUP => 'spam',
						'rows' => 5
					)
				),

				"global[$this->flat_id.spam.keywords]" => new WdElement
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

	static public function spamScore($contents, $url, $author)
	{
		global $core;

		if (self::$spam_score_keywords === null)
		{
			$keywords = $core->registry['feedback_comments.spam.keywords'];

			if ($keywords)
			{
				$keywords = preg_split('#[\s,]+#', $keywords, 0, PREG_SPLIT_NO_EMPTY);
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

		if (in_array($author, self::$spam_score_keywords))
		{
			$score -= 1;
		}

		#
		# additionnal url restrictions
		#

		if (self::$forbidden_urls === null)
		{
			$forbidden_urls = $core->registry['feedback_comments.spam.urls'];

			if ($forbidden_urls)
			{
				$forbidden_urls = preg_split('#[\s,]+#', $forbidden_urls, 0, PREG_SPLIT_NO_EMPTY);
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

	protected function handle_notify(WdOperation $operation, $commentid)
	{
		if (empty($operation->form_entry))
		{
			return;
		}

		$options = unserialize($operation->form_entry->metas[$this->flat_id . '/reply']);

		if (!$options)
		{
			return;
		}

		$comment = $this->model[$commentid];

		#
		# search previous message for notify
		#

		$entries = $this->model->where
		(
			'nid = (SELECT nid FROM {self} WHERE `{primary}` = ?)
			AND `{primary}` < ? AND `{primary}` != ? AND (`notify` = "yes" || `notify` = "author")
			AND author_email != ?',

			$commentid, $commentid, $commentid, $comment->author_email
		)
		->all;

		if (!$entries)
		{
			return;
		}


		#
		# prepare subject and message
		#

		$subject = Patron($options['subject'], $comment);
		$message = Patron($options['template'], $comment);

		$from = $options['from'];
		$bcc = $options['bcc'];

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

			$entry->notify = 'done';
			$entry->save();
		}
	}

	static protected function renderContents($str)
	{
		require_once PUBLISHR_ROOT . '/framework/wdpatron/includes/textmark.php';

		$str = Markdown($str);

		return WdKses::sanitizeComment($str);
	}
}