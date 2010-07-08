<?php

require_once WDCORE_ROOT . 'wdapplication.php';

class WdPApplication extends WdApplication
{
	/**
	 * Return the User object.
	 *
	 * If the user is logged, its User object is returned, otherwise a guest
	 * user is returned instead.
	 *
	 */

	protected function __get_user()
	{
		$user = null;
		$uid = $this->user_id;

		if ($uid)
		{
			global $core;

			$user = $core->models['user.users']->load($uid);

			if ($user && $user->language)
			{
				WdLocale::setLanguage($user->language);
			}
		}

		if (!$user)
		{
			unset($this->session->application['user_id']);

			$user = new User();
		}

		return $user;
	}
}