<?php

require_once WDCORE_ROOT . 'wdapplication.php';

class WdPApplication extends WdApplication
{
	protected function __get_userId()
	{
		return isset($_SESSION[WdApplication::SESSION_LOGGED_USER_ID]) ? $_SESSION[WdApplication::SESSION_LOGGED_USER_ID] : null;
	}

	protected function __get_user()
	{
		global $user;

		if (isset($user))
		{
			return $user;
		}

		#
		# load logged user
		#

		$key = WdApplication::SESSION_LOGGED_USER_ID;

		if (!empty($_SESSION[$key]))
		{
			global $core;

			$user = $core->getModule('user.users')->model()->load($_SESSION[$key]);

			if ($user->language)
			{
				WdLocale::setLanguage($user->language);
			}
		}

		#
		# If we failed to load the user - because it does not exists, or was not authenticated in
		# the first place - we create a guest user.
		#

		if (!$user)
		{
			unset($_SESSION[$key]);

			$user = new User();
		}

		return $user;
	}
}