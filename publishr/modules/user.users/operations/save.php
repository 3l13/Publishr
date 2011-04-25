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
 * Create or update a user profile.
 */
class user_users__save_WdOperation extends constructor_save_WdOperation
{
	protected function __get_properties()
	{
		global $core;

		$properties = parent::__get_properties();

		#
		# the admin is always activated.
		#

		if ($this->key === 1)
		{
			$properties[User::IS_ACTIVATED] = true;
		}

		#
		# user's role. the rid "2" (authenticated user) is mandatory
		#

		unset($properties[User::RID][2]);

		$roles = '2';

		if (!empty($properties[User::RID]))
		{
			foreach ($properties[User::RID] as $rid => $value)
			{
				$value = filter_var($value, FILTER_VALIDATE_BOOLEAN);

				if (!$value)
				{
					continue;
				}

				$roles .= ',' . (int) $rid;
			}
		}

		$properties[User::RID] = $roles;

		if (!$core->user->has_permission(WdModule::PERMISSION_ADMINISTER, $this->module))
		{
			unset($properties[User::RID]);
			unset($properties[User::IS_ACTIVATED]);
		}

		#
		# available sites
		#

		$params = $this->params;
		$properties['available_sites'] = array_keys(isset($params['available_sites']) ? $params['available_sites'] : array());

		return $properties;
	}

	/**
	 * Permission is granted if the user is modifing its own profile, and has permission to.
	 *
	 * @see WdOperation::control_permission()
	 */
	protected function control_permission($permission=WdModule::PERMISSION_CREATE)
	{
		global $core;

		$user = $core->user;

		if ($user->uid == $this->key && $user->has_permission('modify own profile'))
		{
			return true;
		}

		return parent::control_permission($permission);
	}

	protected function control_ownership()
	{
		global $core;

		$user = $core->user;

		if ($user->uid == $this->key && $user->has_permission('modify own profile'))
		{
			// TODO-20110105: it this ok to set the user as a record here ?

			$this->record = $user;

			return true;
		}

		return parent::control_ownership();
	}

	/**
	 * The 'User' role (rid 2) is mandatory for every user.
	 *
	 * @see WdOperation::control_form()
	 */
	protected function control_form()
	{
		$this->params[User::RID][2] = 'on';

		return parent::control_form($this);
	}

	protected function validate()
	{
		global $core;

		$valide = true;
		$properties = $this->properties;

		if (!empty($properties[User::PASSWORD]))
		{
			if (empty($this->params[User::PASSWORD . '-verify']))
			{
				$this->form->log(User::PASSWORD . '-verify', 'Password verify is empty.');

				$valide = false;
			}

			if ($properties[User::PASSWORD] != $this->params[User::PASSWORD . '-verify'])
			{
				$this->form->log(User::PASSWORD . '-verify', 'Password and password verify don\'t match.');

				$valide = false;
			}
		}

		$uid = $this->key ? $this->key : 0;
		$model = $core->models['user.users'];

		#
		# unique username
		#

		if (isset($properties[User::USERNAME]))
		{
			$username = $properties[User::USERNAME];
			$used = $model->select('uid')->where('username = ? AND uid != ?', $username, $uid)->rc;

			if ($used)
			{
				$this->form->log(User::USERNAME, "L'identifiant %username est déjà utilisé.", array('%username' => $username));

				$valide = false;
			}
		}

		#
		# check if email is unique
		#

		if (isset($properties[User::EMAIL]))
		{
			$email = $properties[User::EMAIL];
			$used = $model->select('uid')->where('email = ? AND uid != ?', $email, $uid)->rc;

			if ($used)
			{
				$this->form->log(User::EMAIL, "L'adresse email %email est déjà utilisée.", array('%email' => $email));

				$valide = false;
			}
		}

		return $valide && parent::validate();
	}

	protected function process()
	{
		global $core;

		$rc = parent::process();
		$properties = $this->properties;
		$uid = $rc['key'];

		$record = $this->module->model[$uid];
		$record->metas['available_sites'] = implode(',', $properties['available_sites']);

		if ($core->user_id == $rc['key'])
		{
			wd_log_done("Your profile has been updated.", array(), 'save');
		}
		else
		{
			wd_log_done($rc['mode'] == 'update' ? "%name's profile has been updated." : "%name's profile has been created.", array('%name' => $record->name), 'save');
		}

		return $rc;
	}
}