<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class user_members__save_WdOperation extends user_users__save_WdOperation
{
	protected $accept = array
	(
		'image/gif',
		'image/jpeg',
		'image/png'
	);

	protected function validate()
	{
		$file = new WdUploaded('photo', $this->accept, false);

		if ($file)
		{
			if ($file->er)
			{
				$operation->form->log
				(
					'photo', 'Unable to upload file %file: :message.', array
					(
						'%file' => $file->name,
						':message' => $file->er_message
					)
				);

				return false;
			}

			if ($file->location)
			{
				$this->params['photo'] = $file;
			}
		}

		#
		# email verify
		#

		if (!$this->key && $this->properties['email'] != $this->params['email-verify'])
		{
			$this->form->log('email-verify', "E-mail and E-mail confirm don't match");

			return false;
		}

		return parent::validate();
	}

	protected function process()
	{
		global $core;

		$rc = parent::process();

		if (!$this->key && !$core->user_id)
		{
			$core->session->application['user_id'] = $rc['key'];
			$core->session->application['user_agent'] = md5($_SERVER['HTTP_USER_AGENT']);
		}

		return $rc;
	}
}