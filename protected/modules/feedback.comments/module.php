<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class feedback_comments_WdModule extends WdPModule
{
	static $registry_notifies_response = array
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

	protected function validate_operation_save(WdOperation $operation)
	{
		global $app, $registry;

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

		if (!$app->user_id)
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

			$interval = $registry->get('feedback_comments.delay', 5);

			$last = $this->model()->select
			(
				'created', 'WHERE (author = ? OR author_email = ? OR author_ip = ?) AND created + INTERVAL ? MINUTE > NOW() ORDER BY created DESC', array
				(
					$params['author'],
					$params['author_email'],
					$ip,

					$interval
				)
			)
			->fetchColumnAndClose();

			if ($last)
			{
				$operation->form->log(null, "Les commentaires ne peuvent être fait à moins de $interval minutes d'intervale");

				return false;
			}
		}

		return true;
	}

	protected function operation_save(WdOperation $operation)
	{
		global $app, $registry;

		$params = &$operation->params;
		$user = $app->user;

		if (!$operation->key)
		{
			$params[Comment::AUTHOR_IP] = $_SERVER['REMOTE_ADDR'];

			if (!$user->is_guest())
			{
				$params[Comment::UID] = $user->uid;
				$params[Comment::AUTHOR] = $user->username;
				$params[Comment::AUTHOR_EMAIL] = $user->email;
			}
		}

		#
		# The 'status' property can only be set the managers
		#

		if (!$app->user->has_permission(PERMISSION_MANAGE, $this))
		{
			$params['status'] = null;
		}

		if (empty($params['status']))
		{
			$params['status'] = $registry->get('feedback_comments.default_status', 'pending');
		}

		/*
		wd_log("saving is disable: " . wd_dump($params));

		return;
		*/

		$rc = parent::operation_save($operation);

		if ($rc && !$operation->key)
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
						WdForm::T_LABEL => 'Message',
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
						WdElement::T_MANDATORY => true,
						WdElement::T_OPTIONS => array
						(
							'yes' => 'Bien sûr !',
							'author' => "Seulement si c'est l'auteur du billet qui répond",
							'no' => 'Pas la peine, je viens tous les jours'
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
						WdElement::T_MANDATORY => true,
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

				WdManager::T_ORDER_BY => array('created', 'desc')
			)
		);
	}

	protected function block_config($base)
	{
		global $app, $registry;

		$site_base = $registry->get('site.base');

		return array
		(
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
					'class' => 'form-section flat'
				)
			),

			WdElement::T_CHILDREN => array
			(
				$base . '[formId]' => new WdFormSelectorElement
				(
					'select', array
					(
						WdForm::T_LABEL => 'Formulaire',
						WdElement::T_GROUP => 'primary',
						WdElement::T_MANDATORY => true,
						WdElement::T_DESCRIPTION => "Il s'agit du formulaire à utiliser pour la
						saisie des commentaires."
					)
				),

				'feedback_comments[delay]' => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => 'Intervale entre deux commentaires',
						WdElement::T_DEFAULT => 5,
						WdElement::T_DESCRIPTION => "Il s'agit de l'intervale minimale, exprimée en
						minutes, entre deux commentaires."
					)
				),

				'feedback_comments[default_status]' => new WdElement
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

	static public function spamScore($contents, $url, $author)
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

	protected function handle_notify(WdOperation $operation, $commentid)
	{
		if (empty($operation->form_entry))
		{
			return;
		}

		$options = unserialize($operation->form_entry->metas['feedback_comments/reply']);

		if (!$options)
		{
			return;
		}

		#
		# search previous message for notify
		#

		$entries = $this->model()->loadAll
		(
			'WHERE `nid` = (SELECT `nid` FROM {self} WHERE `{primary}` = ?)
			AND `{primary}` < ? AND `{primary}` != ? AND (`notify` = "yes" || `notify` = "author")', array
			(
				$commentid, $commentid, $commentid
			)
		)
		->fetchAll();

		if (!$entries)
		{
			return;
		}

		#
		# load last comment
		#

		$comment = $this->model()->load($commentid);

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

			#
			# set notify as 'done'
			#

			$entry->notify = 'done';
			$entry->save();
		}
	}

	static protected function renderContents($str)
	{
		require_once WDPATRON_ROOT . 'includes/textmark.php';

		$str = Markdown($str);

		return WdKses::sanitizeComment($str);
	}

	static public function dashboard_last()
	{
		global $core, $document;

		if (!$core->hasModule('feedback.comments'))
		{
			return;
		}

		$document->css->add('public/dashboard.css');

		$entries = $core->models['feedback.comments']->loadRange
		(
			0, 5, 'ORDER BY created DESC'
		)
		->fetchAll();

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
			$score = self::spamScore($contents, $entry->author_url, $entry->author);

			$entry_class = $score < 0 ? 'spam' : '';
			$url_edit = "/admin/index.php/feedback.comments/$entry->commentid/edit";

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

		$rc .= '<div class="list"><a href="/admin/index.php/feedback.comments">Tous les commentaires</a></div>';

		return $rc;
	}
}