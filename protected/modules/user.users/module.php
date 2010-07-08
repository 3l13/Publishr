<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class user_users_WdModule extends WdPModule
{
	const OPERATION_CONNECT = 'connect';
	const OPERATION_DISCONNECT = 'disconnect';
	const OPERATION_ACTIVATE = 'activate';
	const OPERATION_DEACTIVATE = 'deactivate';
	const OPERATION_PASSWORD = 'password';

	static $config_default = array
	(
		'notifies' => array
		(
			'password' => array
			(
				'subject' => 'Vos paramètres de connexion au WdPublisher',
				'from' => 'no-reply@wdpublisher.com',
				'template' => 'Bonjour,

Voici vos paramètres de connexion au système de gestion de contenu WdPublisher :

Identifiant : "#{@username}" ou "#{@email}"
Mot de passe : "#{@password}"

Une fois connecté vous pourrez modifier votre mot de passe. Pour cela cliquez sur votre nom dans la barre de titre et éditez votre profil.

Cordialement'
			)
		)
	);

	public function __construct($tags)
	{
		#
		# Just like system.nodes, in order to identify which module created the user, we need to extend the primary model
		# by defining the T_CONSTRUCTOR tag. The tag is defined by the user.users primary model.
		#

		$tags[self::T_MODELS]['primary'] += array
		(
			user_users_WdModel::T_CONSTRUCTOR => $tags[self::T_ID]
		);

		parent::__construct($tags);
	}

	public function install()
	{
		global $registry;

		$registry->set
		(
			wd_camelCase($this->id, '.') . '.notifies.password', array
			(
				self::$config_default['notifies']['password']
			)
		);

		$rc = parent::install();

		// FIXME-20080908: why do I do this anyway ?

		if ($rc)
		{
			global $app;

			$user = new User();
			$user->uid = 1;

			$app->user = $user;
		}

		return $rc;
	}

	protected function getOperationsAccessControls()
	{
		return array
		(
			self::OPERATION_DISCONNECT => array
			(
				self::CONTROL_VALIDATOR => false
			),

			self::OPERATION_ACTIVATE => array
			(
				self::CONTROL_PERMISSION => PERMISSION_ADMINISTER,
				self::CONTROL_OWNERSHIP => true,
				self::CONTROL_VALIDATOR => false
			),

			self::OPERATION_DEACTIVATE => array
			(
				self::CONTROL_PERMISSION => PERMISSION_ADMINISTER,
				self::CONTROL_OWNERSHIP => true,
				self::CONTROL_VALIDATOR => false
			),

			self::OPERATION_PASSWORD => array
			(
				self::CONTROL_PERMISSION => PERMISSION_MANAGE
			)
		)

		+ parent::getOperationsAccessControls();
	}

	protected function control_permission(WdOperation $operation, $permission)
	{
		global $app;

		$user = $app->user;

		if ($operation->name == self::OPERATION_SAVE && $user->uid == $operation->key && $user->has_permission('modify own profile'))
		{
			return true;
		}

		return parent::control_permission($operation, $permission);
	}

	// FIXME-20100105: this allows all operations if the user can 'modify its profile' ! the
	// control should be restricted to the 'save' operation.

	protected function control_ownership(WdOperation $operation)
	{
		global $app;

		$user = $app->user;

		if ($user->uid == $operation->key && $user->has_permission('modify own profile'))
		{
			$operation->user = $user;
			$operation->entry = $user;

			return true;
		}

		return parent::control_ownership($operation);
	}

	protected function operation_queryOperation(WdOperation $operation)
	{
		switch ($operation->params['operation'])
		{
			case self::OPERATION_PASSWORD:
			{
				global $app;

				$user = $app->user;

				if (!$user->has_permission(PERMISSION_MANAGE, $this))
				{
					wd_log_error('You don\'t have the permission to query this operation');

					return false;
				}

				$entries = $operation->params['entries'];
				$count = count($entries);

				$message = ($count == 1)
					? 'Êtes-vous sûr de vouloir envoyer un nouveau mot de passe à l\'entrée sélectionnée'
					: 'Êtes-vous sûr de vouloir envoyer un nouveau mot de passe aux :count entrées sélectionnées ?';

				$operation->terminus = true;

				return array
				(
					'title' => 'Nouveau mot de passe',
					'message' => t($message, array(':count' => $count)),
					'confirm' => array('Ne pas envoyer', 'Envoyer'),
					'params' => array
					(
						'entries' => $entries
					)
				);
			}
			break;
		}

		return parent::operation_queryOperation($operation);
	}

	/*
	**

	OPERATIONS

	**
	*/

	protected function validate_operation_save(WdOperation $operation)
	{
		$params =& $operation->params;

		if (!empty($params[User::PASSWORD]))
		{
			if (empty($params[User::PASSWORD . '-verify']))
			{
				$operation->form->log(User::PASSWORD . '-verify', 'Password verify is empty');

				return false;
			}

			if ($params[User::PASSWORD] != $params[User::PASSWORD . '-verify'])
			{
				$operation->form->log(User::PASSWORD . '-verify', 'Password and password verify don\'t match');

				return false;
			}
		}

		return parent::validate_operation_save($operation);
	}

	protected function operation_save(WdOperation $operation)
	{
		global $app;

		$operation->handle_booleans(array(User::IS_ACTIVATED));

		if (!$app->user->has_permission(PERMISSION_ADMINISTER, $this))
		{
			unset($operation->params[User::IS_ACTIVATED]);
		}

		#
		#
		#

		$rc = parent::operation_save($operation);

		$params = &$operation->params;

		if (!empty($params[User::PASSWORD]) && !empty($rc['key']))
		{
			$uid = $rc['key'];
			$password = $params[User::PASSWORD];

			$this->sendPassword($uid, $password);
		}
		else if ($rc && !$operation->key)
		{
			$this->sendPassword($rc['key']);
		}

		return $rc;
	}

	protected function operation_disconnect(WdOperation $operation)
	{
		#
		# if a disconnection message has been posted, the
		# login information of the session are cleared.
		#

		global $app;

		unset($app->session->application['user_id']);

		#
		#
		#

		$url = $_SERVER['REQUEST_URI'];

		if ($operation->method == 'GET')
		{
			if ($_SERVER['QUERY_STRING'])
			{
				$url = substr($url, 0, - strlen($_SERVER['QUERY_STRING']) - 1);
			}
		}

		$operation->location = $url;

		return true;
	}

	protected function block_connect()
	{
		global $document;

		$document->js->add('public/connect.js');

		$rc = '<div id="login">';

		$rc .= '<div class="wrapper">';
		$rc .= '<div class="slide">';
		$rc .= $this->form_connect();
		$rc .= '</div>';
		$rc .= '</div>';

		$rc .= <<<EOT
<div class="wrapper password" style="height: 0">
	<div class="slide">
	<form class="group password login" name="password" action="">
		<div class="form-label">
		<label class="mandatory">Votre adresse E-Mail&nbsp;<sup>*</sup><span class="separator">&nbsp;:</span></label>
		</div>

		<div class="form-element">
		<input type="text" name="email" />
		<div class="element-description"><a href="#" class="cancel">Annuler</a></div>
		</div>

		<div class="form-element">
		<button class="warn big" type="submit">Envoyer</button>
		</div>
	</form>
	</div>
</div>
EOT;

		$rc .= '</div>';

		return $rc;
	}

	protected function block_disconnect()
	{
		return new WdForm
		(
			array
			(
				WdForm::T_HIDDENS => array
				(
					WdOperation::NAME => self::OPERATION_DISCONNECT,
					WdOperation::DESTINATION => $this->id
				),

				WdElement::T_CHILDREN => array
				(
					new WdElement
					(
						WdElement::E_SUBMIT, array
						(
							WdElement::T_INNER_HTML => t('Disconnect')
						)
					)
				)
			)
		);
	}

	protected function form_connect()
	{
		global $document;

		if (isset($document))
		{
			$document->css->add('public/connect.css');
		}

		return new Wd2CForm
		(
			array
			(
				WdForm::T_HIDDENS => array
				(
					WdOperation::DESTINATION => $this,
					WdOperation::NAME => self::OPERATION_CONNECT
				),

				WdElement::T_CHILDREN => array
				(
					User::USERNAME => new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'Username',
							WdElement::T_MANDATORY => true,

							'class' => 'autofocus'
						)
					),

					User::PASSWORD => new WdElement
					(
						WdElement::E_PASSWORD, array
						(
							WdForm::T_LABEL => 'Password',
							WdElement::T_MANDATORY => true,
							WdElement::T_DESCRIPTION => '<a href="#">J\'ai oublié mon mot de passe</a>'
						)
					),

					new WdElement
					(
						WdElement::E_SUBMIT, array
						(
							WdElement::T_INNER_HTML => t('Connect'),

							'class' => 'continue big'
						)
					)
				),

				'class' => 'group login',
				'name' => self::OPERATION_CONNECT
			),

			'div'
		);
	}

	// FIXME: should implement control_form() and patch OPERATION_CONNECT instead
	// of using an operation validator

	protected function validate_operation_connect(WdOperation $operation)
	{
		global $core;

		$params = &$operation->params;
		$operation->form = $this->form_connect();

		if (!$operation->form->validate($params))
		{
			return false;
		}

		#
		# try to load user
		#

		$username = $params[User::USERNAME];
		$password = $params[User::PASSWORD];

		$found = $this->model()->query
		(
			'select `uid`, `constructor` from {prefix}user_users where (`username`= ? OR `email` = ?) and `password` = md5(?)', array
			(
				$username, $username, $password
			)
		)
		->fetchAndClose();

		if (!$found)
		{
			$operation->form->log(User::PASSWORD, 'Unknown username/password combination');

			return false;
		}

		list($uid, $constructor) = $found;

		$entry = $core->getModule($constructor)->model()->load($uid);

		if (!$entry)
		{
			throw new WdException('Unable to load user with uid %uid', array('%uid' => $uid));
		}

		if (!$entry->is_admin() && !$entry->is_activated)
		{
			$operation->form->log(null, 'User %username is not activated', array('%username' => $username));

			return false;
		}

		$operation->entry = $entry;

		return true;
	}

	protected function operation_connect(WdOperation $operation)
	{
		global $app;

		$user = $operation->entry;

		$app->session->application['user_id'] = $user->uid;
		$app->user = $user;

		#
		# we update the 'lastconnection' date
		#

		$this->model()->execute
		(
			'UPDATE {prefix}user_users SET lastconnection = now() WHERE uid = ?', array
			(
				$user->uid
			)
		);

		/*
		$user->lastconnection = date('Y-m-d H:i:s');
		$user->save();
		*/

		return !empty($user->uid);
	}

	/*
	**

	BLOCKS

	**
	*/

	protected function block_edit(array $properties, $permission)
	{
		global $app, $document;

		$document->js->add('public/edit.js');

		#
		# permissions
		#

		$user = $app->user;

		$administer = false;
		$permission = false;

		$uid = $properties[User::UID];

		$role_options = array();

		if ($user->has_permission(PERMISSION_MANAGE, $this))
		{
			$administer = true;
			$permission = true;

			// FIXME: not sure about this one

			if (!($user->is_admin() && $user->uid == $uid))
			{
				#
				# create role's options
				#

				global $core;

				$module = $core->getModule('user.roles');

				$roles = $module->model()->loadAll();

				foreach ($roles as $role)
				{
					$role_options[$role->rid] = $role->role;
				}
			}
		}
		else if (($user->uid == $uid) && $user->has_permission('modify own profile'))
		{
			$permission = true;
		}

		#
		# display options
		#

		$display_options = array
		(
			'<username>'
		);

		if ($properties[User::USERNAME])
		{
			$display_options[0] = $properties[User::USERNAME];
		}

		$firstname = $properties[User::FIRSTNAME];

		if ($firstname)
		{
			$display_options[1] = $firstname;
		}

		$lastname = $properties[User::LASTNAME];

		if ($lastname)
		{
			$display_options[2] = $lastname;
		}

		if ($firstname && $lastname)
		{
			$display_options[3] = $firstname . ' ' . $lastname;
			$display_options[4] = $lastname . ' ' . $firstname;
		}

		#
		#
		#

		return array
		(
			WdForm::T_DISABLED => !$permission,

			WdElement::T_GROUPS => array
			(
				'contact' => array
				(
					'title' => 'Contact'
				),

				'connection' => array
				(
					'title' => 'Connexion'
				),

				'advanced' => array
				(
					'title' => 'Options avancées'
				)
			),

			WdElement::T_CHILDREN => array
			(
				#
				# name group
				#

				User::FIRSTNAME => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => 'Prénom',
						WdElement::T_GROUP => 'contact',

						//'class' => 'autofocus'
					)
				),

				User::LASTNAME => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => 'Nom',
						WdElement::T_GROUP => 'contact'
					)
				),

				User::USERNAME => $administer ? new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => 'Username',
						WdElement::T_GROUP => 'contact',
						WdElement::T_MANDATORY => true
					)
				) : null,

				User::DISPLAY => new WdElement
				(
					'select', array
					(
						WdForm::T_LABEL => 'Afficher comme',
						WdElement::T_GROUP => 'contact',
						WdElement::T_OPTIONS => $display_options
					)
				),

				#
				# connection group
				#

				User::EMAIL => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => 'E-Mail',
						WdElement::T_GROUP => 'connection',
						WdElement::T_MANDATORY => true,

						'autocomplete' => 'off'
					)
				),

				new WdElement
				(
					'div', array
					(
						WdForm::T_LABEL => 'Password',

						WdElement::T_GROUP => 'connection',
						WdElement::T_CHILDREN => array
						(
							'<div>',

							User::PASSWORD => new WdElement
							(
								WdElement::E_PASSWORD, array
								(
									WdForm::T_LABEL => 'Password',
									WdElement::T_DESCRIPTION => $uid ? "Si vous souhaitez changer
									de mot de passe, saisissez ici le nouveau. Sinon, laissez
									ce champ vide." : "À la création d'un nouveau compte, laissez
									le champ libre pour qu'un mot de passe soit automatiquement
									généré. Sinon, saisissez le mot de passe à utiliser.",

									'value' => '',
									'autocomplete' => 'off'
								)
							),

							'</div><div>',

							User::PASSWORD . '-verify' => new WdElement
							(
								WdElement::E_PASSWORD, array
								(
									WdForm::T_LABEL => 'Password verify',
									WdElement::T_DESCRIPTION => "Si vous avez saisi un mot de passe,
									merci de le confirmer.",

									'value' => ''
								)
							),

							'</div>'
						),

						'style' => 'column-count; -moz-column-count: 2; -webkit-column-count: 2'
					)
				),

				User::IS_ACTIVATED => ($uid == 1 || !$administer) ? null : new WdElement
				(
					WdElement::E_CHECKBOX, array
					(
						WdForm::T_LABEL => 'Activation',
						WdElement::T_LABEL => "Le compte de l'utilisateur est actif",
						WdElement::T_GROUP => 'connection',
						WdElement::T_DESCRIPTION => "Seuls les utilisateurs dont le compte est
						actif peuvent se connecter."
					)
				),

				User::RID => $role_options ? new WdElement
				(
					WdElement::E_RADIO_GROUP, array
					(
						WdForm::T_LABEL => 'Role',
						WdElement::T_GROUP => 'advanced',
						WdElement::T_OPTIONS => $role_options,
						WdElement::T_MANDATORY => true,
						WdElement::T_DESCRIPTION => "Parce que vous avez des droits d'administration
						sur ce module, vous pouvez choisir le rôle de cet utilisateur.",

						'class' => 'list'
					)
				)
				: null,

				User::LANGUAGE => new WdElement
				(
					'select', array
					(
						WdForm::T_LABEL => 'Langue',
						WdElement::T_GROUP => 'advanced',
						WdElement::T_DESCRIPTION => "Il s'agit de la langue à utiliser pour
						l'interface.",
						WdElement::T_OPTIONS => array
						(
							null => '',
							'en' => 'Anglais',
							'fr' => 'Français'
						)
					)
				)
			)
		);
	}

	protected function block_profile()
	{
		global $app;

		$user = $app->user;

		$module = $this;
		$constructor = $user->constructor;

		if ($constructor != $this->id)
		{
			global $core;

			$module = $core->getModule($user->constructor);
		}

		return $module->getBlock('edit', $user->uid);
	}

	protected function block_manage()
	{
		return new user_users_WdManager
		(
			$this, array
			(
				WdManager::T_COLUMNS_ORDER => array(User::USERNAME, User::EMAIL, User::RID, User::IS_ACTIVATED, User::CREATED, User::LASTCONNECTION)
			)
		);
	}

	protected function block_config($base)
	{
		return array
		(
			WdElement::T_GROUPS => array
			(
				'notifies.password' => array
				(
					'title' => 'Envoie des informations de connexion',
					'no-panels' => true
				)
			),

			WdElement::T_CHILDREN => array
			(
				$base . '[notifies][password]' => new WdEMailNotifyElement
				(
					array
					(
						WdElement::T_GROUP => 'notifies.password',
						WdElement::T_DEFAULT => self::$config_default['notifies']['password']
					)
				)
			)
		);
	}

	protected function validate_operation_password(WdOperation $operation)
	{
		if (!$operation->key)
		{
			wd_log_error('Missing key baby !');

			return false;
		}

		return true;
	}

	protected function operation_password(WdOperation $operation)
	{
		return $this->sendPassword($operation->key);
	}

	protected function validate_operation_retrievePassword(WdOperation $operation)
	{
		if (empty($operation->params[User::EMAIL]))
		{
			wd_log_error('The field %field is mandatory!', array('%field' => 'Votre adresse E-Mail'));

			return false;
		}

		return true;
	}

	protected function operation_retrievePassword(WdOperation $operation)
	{
		$email = $operation->params[User::EMAIL];

		$uid = $this->model()->select('{primary}', 'where email = ? limit 1', array($email))->fetchColumnAndClose();

		if (!$uid)
		{
			wd_log_error('Unknown E-Mail address: %email', array('%email' => $email));

			return false;
		}

		return $this->sendPassword($uid);
	}

	/*
	 *
	 * ONLINE
	 *
	 */

	protected function operation_query_activate(WdOperation $operation)
	{
		$entries = $operation->params['entries'];
		$count = count($entries);

		return array
		(
			'title' => $count == 1 ? 'Activate user' : 'Activate users',
			'message' => $count == 1
				? t('Are you sure you want to active the selected user ?')
				: t('Are you sure you want to activate the :count selected users ?', array(':count' => $count)),
			'confirm' => array('Don\'t activate', 'Activate'),
			'params' => array
			(
				'entries' => $entries
			)
		);
	}

	protected function operation_activate(WdOperation $operation)
	{
		$entry = $operation->entry;
		$entry->is_activated = true;
		$entry->save();

		wd_log_done('!name is now active', array('!name' => $entry->name));

		return true;
	}

	/*
	 *
	 * DEACTIVATE
	 *
	 */

	protected function operation_query_deactivate(WdOperation $operation)
	{
		$entries = $operation->params['entries'];
		$count = count($entries);

		return array
		(
			'title' => $count == 1 ? 'Deactivate user' : 'Deactivate users',
			'message' => $count == 1
				? t('Are you sure you want to deactive the selected user ?')
				: t('Are you sure you want to deactivate the :count selected users ?', array(':count' => $count)),
			'confirm' => array('Don\'t deactivate', 'Deactivate'),
			'params' => array
			(
				'entries' => $entries
			)
		);
	}

	protected function operation_deactivate(WdOperation $operation)
	{
		$entry = $operation->entry;
		$entry->is_activated = false;
		$entry->save();

		wd_log_done('!name is now inactive', array('!name' => $entry->name));

		return true;
	}

	/**
	 * Generate a password.
	 *
	 * @param $length
	 * The length of the password.
	 * Default: 8
	 * @param $possible
	 * The characters that can be used to create the password.
	 * If you defined your own, pay attention to ambiguous characters such as 0, O, 1, l, I...
	 * Default: '$=@#23456789bcdfghjkmnpqrstvwxyz'
	 * @return unknown_type
	 */

	static public function generatePassword($length=8, $possible='$=@#23456789bcdfghjkmnpqrstvwxyz')
	{
		$password = '';

		$possible_length = strlen($possible) - 1;

		#
		# add random characters to $password for $length
		#

		while ($length--)
		{
			#
			# pick a random character from the possible ones
			#

			$except = substr($password, -$possible_length / 2);

			for ($n = 0 ; $n < 5 ; $n++)
			{
				$char = $possible{mt_rand(0, $possible_length)};

				#
				# we don't want this character if it's already in the password
				# unless it's far enough (half of our possible length)
				#

				if (strpos($except, $char) === false)
				{
					break;
				}
			}

			$password .= $char;
		}

		return $password;
	}

	protected function sendPassword($uid, $password=null)
	{
		#
		# load and check user id
		#

		$user = $this->model()->load($uid);

		if (!$user)
		{
			wd_log_error('Unknown user id: %uid', array('%uid' => $uid));

			return false;
		}

		#
		# load the configuration to send the email from the registry
		#

		global $registry;

		$r = $registry->get(wd_camelCase($this->id, '.') . '.notifies.password.');

		if (!$r)
		{
			$r = $registry->get('userUsers.notifies.password.', self::$config_default['notifies']['password']);
		}

		if (!$r || empty($r['template']))
		{
			wd_log_error('The password cannot be sent because the notify config is missing or incomplete');

			return false;
		}

		#
		# If the new password is not defined, we generate one
		#

		if (!$password)
		{
			$password = self::generatePassword();
		}

		#
		#
		#

		$mailer = new WdMailer
		(
			array
			(
				WdMailer::T_DESTINATION => $user->email,
				WdMailer::T_TYPE => 'plain',
				WdMailer::T_MESSAGE => Patron
				(
					$r['template'], array('password' => $password) + get_object_vars($user)
				)
			)

			+ $r
		);

		$rc = $mailer->send();

		if (!$rc)
		{
			wd_log_error('Unable to send password at %email', array('%email' => $user->email));

			return false;
		}

		global $app;

		if (0)
		{
			wd_log_done('The password is: %password', array('%password' => $password));
		}

		wd_log_done('Login information have been sent to %email', array('%email' => $user->email));

		$user->password = $password;
		$user->save();

		return true;
	}
}