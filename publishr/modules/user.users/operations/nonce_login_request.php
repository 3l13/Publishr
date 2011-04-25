<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Provides a nonce login.
 */
class user_users__nonce_login_request_WdOperation extends WdOperation
{
	const FRESH_PERIOD = 3600;
	const COOLOFF_DELAY = 900;

	protected function __get_record()
	{
		global $core;

		return $core->models['user.users']->find_by_email($this->params['email'])->one;
	}

	protected function validate()
	{
		if (empty($this->params['email']))
		{
			wd_log_error('The field %field is required!', array('%field' => 'Votre adresse E-Mail'));

			return false;
		}

		$email = $this->params['email'];

		if (!filter_var($email, FILTER_VALIDATE_EMAIL))
		{
			wd_log_error('Invalid e-mail address: %email.', array('%email' => $email));

			return false;
		}

		$user = $this->record;

		if (!$user)
		{
			wd_log_error('Unknown e-mail address.');

			return false;
		}

		$now = time();
		$expires = $user->metas['nonce_login.expires'];

		if ($expires && ($now + self::FRESH_PERIOD - $expires < self::COOLOFF_DELAY))
		{
			throw new WdHTTPException("A message has already been sent to your e-mail address. In order to reduce abuses, you won't be able to request a new one until :time.", array(':time' => wd_format_date($expires - self::FRESH_PERIOD + self::COOLOFF_DELAY, 'HH:mm')), 403);
		}

		return true;
	}

	protected function process()
	{
		global $core;

		$user = $this->record;

		$token = md5(WdSecurity::generate_token(32, 'wide'));
		$expires = time() + self::FRESH_PERIOD;
		$ip = $_SERVER['REMOTE_ADDR'];

		$user->metas['nonce_login.token'] = base64_encode(WdSecurity::pbkdf2($token, $core->configs['user']['nonce_login_salt']));
		$user->metas['nonce_login.expires'] = $expires;
		$user->metas['nonce_login.ip'] = $ip;

		$url = $core->site->url . "/api/nonce-login/$user->email/$token";
		$until = wd_format_date($expires, 'HH:mm');

		$t = new WdTranslatorProxi(array('scope' => array(wd_normalize($user->constructor, '_'), 'nonce_login_request', 'operation')));

		$mailer = new WdMailer
		(
			array
			(
				WdMailer::T_DESTINATION => $user->email,
				WdMailer::T_BCC => 'olivier.laviale@gmail.com',
				WdMailer::T_FROM => $core->site->title . ' <no-reply@publishr.com>',
				WdMailer::T_SUBJECT => $t->__invoke('message.subject'),
				WdMailer::T_MESSAGE => $t->__invoke
				(
					'message.template', array
					(
						':url' => $url,
						':until' => $until,
						':ip' => $ip
					)
				)
			)
		);

		$this->response->mailer = $mailer;

		$mailer->send();

		wd_log_done($t->__invoke('success'));

		return true;
	}
}