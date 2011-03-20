<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class feedback_comments__save_WdOperation extends publishr_save_WdOperation
{
	/**
	 * @var feedback_forms__send_WdOperation
	 */
	protected $send_operation;

	public function __construct($destination, $name, array $params, feedback_forms__send_WdOperation $send=null)
	{
		$this->send_operation = $send;

		parent::__construct($destination, $name, $params);
	}

	/**
	 * Returns the feedback.form send operation form if the send operation is available, otherwise
	 * return the value of the parent `__get_form()` method.
	 *
	 * @see WdOperation::__get_form()
	 */
	protected function __get_form()
	{
		return $this->send_operation ? $this->send_operation->form : parent::__get_form();
	}

	protected function __get_properties()
	{
		global $core;

		$properties = parent::__get_properties();
		$user = $core->user;

		if ($this->key)
		{
			unset($properties[Comment::NID]);
		}
		else
		{
			if (empty($properties[Comment::NID]))
			{
				throw new WdException('Missing target node id');
			}

			$properties[Comment::AUTHOR_IP] = $_SERVER['REMOTE_ADDR'];

			if (!$user->is_guest())
			{
				$properties[Comment::UID] = $user->uid;
				$properties[Comment::AUTHOR] = $user->username;
				$properties[Comment::AUTHOR_EMAIL] = $user->email;
			}
		}

		if (!$user->has_permission(WdModule::PERMISSION_MANAGE, $this->module))
		{
			$properties['status'] = null;
		}

		if (!$this->key && empty($properties['status']))
		{
			$node = $core->models['system.nodes'][$properties[Comment::NID]];
			$properties['status'] = $node->site->metas->get($this->module->flat_id . '.default_status', 'pending');
		}

		return $properties;
	}

	protected function validate()
	{
		global $core;

		$params = &$this->params;

		#
		# the article id is required when creating a message
		#

		if (!$this->key && empty($params[Comment::NID]))
		{
			$this->form->log(Comment::NID, 'The node id is required while creating a new comment');

			return false;
		}

		#
		# validate IP
		#

		$ip = $_SERVER['REMOTE_ADDR'];

		if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE))
		{
			$this->form->log(null, 'Adresse IP invalide&nbsp;: %ip', array('%ip' => $ip));

			return false;
		}

		if (!$core->user_id)
		{
			$score = feedback_comments_WdModule::score_spam($params[Comment::CONTENTS], $params[Comment::AUTHOR_URL], $params[Comment::AUTHOR]);

			if ($score < 1)
			{
				$this->form->log(Comment::CONTENTS, '@form.log.spam', array('%score' => $score));

				return false;
			}

			#
			# delay between last post
			#

			$interval = $core->site->metas->get($this->module->flat_id . '.delay', 5);

			$last = $this->module->model
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
				$this->form->log(null, "Les commentaires ne peuvent être fait à moins de $interval minutes d'intervale");

				return false;
			}
		}

		return true;
	}

	protected function process()
	{
		$rc = parent::process();

		if (!$this->key && $this->send_operation)
		{
			$this->notify($rc['key']);
		}

		return $rc;
	}

	protected function notify($commentid)
	{
		$options = unserialize($this->send_operation->record->metas[$this->module->flat_id . '/reply']);

		if (!$options)
		{
			return;
		}

		$comment = $this->module->model[$commentid];

		#
		# search previous message for notify
		#

		$entries = $this->module->model->where
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
}