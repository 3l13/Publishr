<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class user_users_WdModule extends WdPModule
{
	const OPERATION_CONNECT = 'connect';
	const OPERATION_DISCONNECT = 'disconnect';
	const OPERATION_ACTIVATE = 'activate';
	const OPERATION_DEACTIVATE = 'deactivate';
	const OPERATION_PASSWORD = 'password';
	const OPERATION_IS_UNIQUE = 'is_unique';

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
		global $core;

		$rc = parent::install();

		// FIXME-20080908: why do I do this anyway ?

		if ($rc)
		{
			$user = new User();
			$user->uid = 1;

			$core->user = $user;
		}

		return $rc;
	}

	protected function controls_for_operation_activate(WdOperation $operation)
	{
		return array
		(
			self::CONTROL_PERMISSION => self::PERMISSION_ADMINISTER,
			self::CONTROL_OWNERSHIP => true,
			self::CONTROL_VALIDATOR => false
		);
	}

	protected function controls_for_operation_deactivate(WdOperation $operation)
	{
		return array
		(
			self::CONTROL_PERMISSION => self::PERMISSION_ADMINISTER,
			self::CONTROL_OWNERSHIP => true,
			self::CONTROL_VALIDATOR => false
		);
	}

	protected function controls_for_operation_password(WdOperation $operation)
	{
		return array
		(
			self::CONTROL_PERMISSION => self::PERMISSION_MANAGE
		);
	}

	protected function controls_for_operation_is_unique(WdOperation $operation)
	{
		return array
		(
			self::CONTROL_AUTHENTICATION => true
		);
	}

	protected function control_form_for_operation_save(WdOperation $operation)
	{
		$operation->params[User::RID][2] = 'on';

		return parent::control_form_for_operation($operation);
	}

	protected function control_permission_for_operation_save(WdOperation $operation, $permission)
	{
		global $core;

		$user = $core->user;

		if ($user->uid == $operation->key && $user->has_permission('modify own profile'))
		{
			return true;
		}

		return parent::control_permission_for_operation($operation, $permission);
	}

	protected function control_ownership_for_operation_save(WdOperation $operation)
	{
		global $core;

		$user = $core->user;

		if ($user->uid == $operation->key && $user->has_permission('modify own profile'))
		{
			// TODO-20110105: it this ok to set the user as a record here ?

			$operation->record = $user;

			return true;
		}

		return parent::control_ownership_for_operation($operation);
	}

	protected function operation_queryOperation(WdOperation $operation)
	{
		switch ($operation->params['operation'])
		{
			case self::OPERATION_PASSWORD:
			{
				global $core;

				$user = $core->user;

//				if (!$user->has_permission(self::PERMISSION_MANAGE, $this))
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
		$valide = true;

		$params =& $operation->params;

		if (!empty($params[User::PASSWORD]))
		{
			if (empty($params[User::PASSWORD . '-verify']))
			{
				$operation->form->log(User::PASSWORD . '-verify', 'Password verify is empty');

				$valide = false;
			}

			if ($params[User::PASSWORD] != $params[User::PASSWORD . '-verify'])
			{
				$operation->form->log(User::PASSWORD . '-verify', 'Password and password verify don\'t match');

				$valide = false;
			}
		}

		$uid = $operation->key ? $operation->key : 0;

		#
		# unique username
		#

		if (isset($params[User::USERNAME]))
		{
			$username = $params[User::USERNAME];

			$used = $this->model->select('uid')->where('username = ? AND uid != ?', $username, $uid)->rc;

			if ($used)
			{
				$operation->form->log(User::USERNAME, "L'identifiant %username est déjà utilisé", array('%username' => $username));

				$valide = false;
			}
		}

		#
		# unique username
		#

		$email = $params[User::EMAIL];

		$used = $this->model->select('uid')->where('email = ? AND uid != ?', $email, $uid)->rc;

		if ($used)
		{
			$operation->form->log(User::EMAIL, "L'adresse email %email est déjà utilisée", array('%email' => $email));

			$valide = false;
		}

		return $valide && parent::validate_operation_save($operation);
	}

	protected function control_properties_for_operation_save(WdOperation $operation)
	{
		global $core;

		$properties = parent::control_properties_for_operation_save($operation);

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

		if (!$core->user->has_permission(self::PERMISSION_ADMINISTER, $this))
		{
			unset($properties[User::RID]);
			unset($properties[User::IS_ACTIVATED]);
		}

		return $properties;
	}

	protected function operation_save(WdOperation $operation)
	{
		$rc = parent::operation_save($operation);

		#
		# for new entries (rc but no operation's key), if IS_ACTIVATED is set, we send an
		# automatically generated password to the user.
		#

		/*
		if (!$operation->key && isset($params[User::IS_ACTIVATED]))
		{
			$this->sendPassword($rc['key']);
		}
		*/

		$params = &$operation->params;

		if (!empty($params[User::PASSWORD]))
		{
			$uid = $rc['key'];
			$password = $params[User::PASSWORD];

			$this->sendPassword($uid, $password);
		}

		return $rc;
	}

	protected function controls_for_operation_disconnect(WdOperation $operation)
	{
		return array
		(
			self::CONTROL_VALIDATOR => false
		);
	}

	/**
	 * Disconnect the user from the system by removing its identifier form its session.
	 *
	 * @param WdOperation $operation
	 */

	protected function operation_disconnect(WdOperation $operation)
	{
		global $core;

		unset($core->session->application['user_id']);

		$operation->location = isset($_GET['location']) ? $_GET['location'] : (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '/');

		return true;
	}

	protected function block_connect()
	{
		global $document;

		$document->js->add('public/connect.js');

		$form = (string) $this->form_connect();

		return <<<EOT
<div id="login">
	<div class="wrapper">
		<div class="slide">$form</div>
	</div>

	<div class="wrapper password" style="height: 0">
		<div class="slide">
		<form class="group password login" name="password" action="">
			<div class="form-label form-label-email">
			<label class="required mandatory">Votre adresse E-Mail&nbsp;<sup>*</sup><span class="separator">&nbsp;:</span></label>
			</div>

			<div class="form-element form-element-email">
			<input type="text" name="email" />
			<div class="element-description"><a href="#" class="cancel">Annuler</a></div>
			</div>

			<div class="form-element form-element-submit">
			<button class="warn big" type="submit">Envoyer</button>
			</div>
		</form>
		</div>
	</div>
</div>
EOT;
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
							WdElement::T_INNER_HTML => t('disconnect', array(), array('scope' => array('user_users', 'form', 'label')))
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
							WdForm::T_LABEL => array('username', array('user_users', 'form', 'label')),
							WdElement::T_REQUIRED => true,

							'class' => 'autofocus'
						)
					),

					User::PASSWORD => new WdElement
					(
						WdElement::E_PASSWORD, array
						(
							WdForm::T_LABEL => array('password', array('user_users', 'form', 'label')),
							WdElement::T_REQUIRED => true,
							WdElement::T_DESCRIPTION => '<a href="#lost-password">' . t
							(
								'lost_password', array(), array
								(
									'scope' => array('user_users', 'form', 'label'),
									'default' => 'I forgot my password'
								)
							)

							.

							'</a>'
						)
					),

					'#submit' => new WdElement
					(
						WdElement::E_SUBMIT, array
						(
							WdElement::T_INNER_HTML => t('connect', array(), array('scope' => array('user_users', 'form', 'label'))),

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

	// TODO-20110105: this validator needs revision because it handle both the form and the record.

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

		$found = $this->model->select('uid, constructor')
		->where('(username = ? OR email = ?) AND password = md5(?)', array($username, $username, $password))
		->one(PDO::FETCH_NUM);

		if (!$found)
		{
			$operation->form->log(User::PASSWORD, 'Unknown username/password combination');

			return false;
		}

		list($uid, $constructor) = $found;

		$record = $core->models[$constructor][$uid];

		if (!$record)
		{
			throw new WdException('Unable to load user with uid %uid', array('%uid' => $uid));
		}

		if (!$record->is_admin() && !$record->is_activated)
		{
			$operation->form->log(null, 'User %username is not activated', array('%username' => $username));

			return false;
		}

		$operation->record = $record;

		return true;
	}

	protected function operation_connect(WdOperation $operation)
	{
		global $core;

		$user = $operation->record;

		$core->session->application['user_id'] = $user->uid;
		$core->user = $user;

		#
		# we update the 'lastconnection' date
		#

		$this->model->execute
		(
			'UPDATE {prefix}user_users SET lastconnection = now() WHERE uid = ?', array
			(
				$user->uid
			)
		);

		$operation->location = $_SERVER['REQUEST_URI'];

		/*
		$user->lastconnection = date('Y-m-d H:i:s');
		$user->save();
		*/

		return !empty($user->uid);
	}

	protected function validate_operation_is_unique(WdOperation $operation)
	{
		$params = &$operation->params;

		if (empty($params[User::USERNAME]) && empty($params[User::EMAIL]))
		{
			wd_log_error('Missing %username or %email', array('%username' => User::USERNAME, '%email' => User::EMAIL));

			return false;
		}

		return true;
	}

	protected function operation_is_unique(WdOperation $operation)
	{
		$params = &$operation->params;

		$uid = isset($params[User::UID]) ? $params[User::UID] : 0;

		$is_unique_username = true;
		$is_unique_email = true;

		if (isset($params[User::USERNAME]))
		{
			$is_unique_username = !$this->model->select('uid')->where('username = ? AND uid != ?', $params[User::USERNAME], $uid)->rc;
		}

		if (isset($params[User::EMAIL]))
		{
			$is_unique_email = !$this->model->select('uid')->where('email = ? AND uid != ?', $params[User::EMAIL], $uid)->rc;
		}

		$operation->response->username = $is_unique_username;
		$operation->response->email = $is_unique_email;

		return $is_unique_email && $is_unique_username;
	}

	/*
	**

	BLOCKS

	**
	*/

	protected function block_edit(array $properties, $permission)
	{
		global $core, $document;

		$document->js->add('public/edit.js');

		#
		# permissions
		#

		$user = $core->user;

		$administer = false;
		$permission = false;

		$uid = $properties[User::UID];

		$role_options = array();

		if ($user->has_permission(self::PERMISSION_MANAGE, $this))
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

				$roles = $core->models['user.roles']->all;

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

		/*
		options ? new WdElement
				(
					WdElement::E_RADIO_GROUP, array
					(
						WdForm::T_LABEL => 'Role',
						WdElement::T_GROUP => 'advanced',
						WdElement::T_OPTIONS => $role_options,
						WdElement::T_REQUIRED => true,
						WdElement::T_DESCRIPTION => "Parce que vous avez des droits d'administration
						sur ce module, vous pouvez choisir le rôle de cet utilisateur.",

						'class' => 'list'
					)
				)
				: null
		*/

		$role_el = null;

		if ($properties[User::UID] != 1 && $user->has_permission(self::PERMISSION_ADMINISTER, $this))
		{
			$role_options = $core->models['user.roles']->select('rid, role')->where('rid != 1')->order('rid')->pairs;
			$properties_rid = $properties[User::RID];

			if (is_string($properties_rid))
			{
				$properties_rid = explode(',', $properties_rid);
				$properties_rid = array_combine($properties_rid, array_fill(0, count($properties_rid), true));
			}

			$properties_rid[2] = true;

			$role_el = new WdElement
			(
				WdElement::E_CHECKBOX_GROUP, array
				(
					WdForm::T_LABEL => 'Roles',
					WdElement::T_GROUP => 'advanced',
					WdElement::T_OPTIONS => $role_options,
					WdElement::T_OPTIONS_DISABLED => array(2 => true),
					WdElement::T_REQUIRED => true,
					WdElement::T_DESCRIPTION => "Parce que vous avez des droits d'administration
					sur ce module, vous pouvez choisir les rôles de cet utilisateur.",

					'class' => 'list',
					'value' => $properties_rid
				)
			);
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
					'title' => 'Contact',
					'class' => 'form-section flat'
				),

				'connection' => array
				(
					'title' => 'Connexion',
					'class' => 'form-section flat'
				),

				'advanced' => array
				(
					'title' => 'Options avancées',
					'class' => 'form-section flat'
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
						WdElement::T_REQUIRED => true
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
						WdElement::T_REQUIRED => true,

						'autocomplete' => 'off'
					)
				),

				new WdElement
				(
					'div', array
					(
						WdElement::T_GROUP => 'connection',
						WdElement::T_CHILDREN => array
						(
							'<div>',

							User::PASSWORD => new WdElement
							(
								WdElement::E_PASSWORD, array
								(
									WdElement::T_LABEL => 'Password',
									WdElement::T_LABEL_POSITION => 'above',
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
									WdElement::T_LABEL => 'Confirmation',
									WdElement::T_LABEL_POSITION => 'above',
									WdElement::T_DESCRIPTION => "Si vous avez saisi un mot de passe,
									merci de le confirmer.",

									'value' => '',
									'autocomplete' => 'off'
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
						//WdForm::T_LABEL => 'Activation',
						WdElement::T_LABEL => "Le compte de l'utilisateur est actif",
						WdElement::T_GROUP => 'connection',
						WdElement::T_DESCRIPTION => "Seuls les utilisateurs dont le compte est
						actif peuvent se connecter."
					)
				),

				User::RID => $role_el,

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
				),

				'timezone' => new WdTimeZoneElement
				(
					array
					(
						WdForm::T_LABEL => 'Zone temporelle',
						WdElement::T_GROUP => 'advanced'
					)
				)
			)
		);
	}

	protected function block_profile()
	{
		global $core, $document;

		$user = $core->user;
		$document->page_title = 'Profil utilisateur';

		$module = $this;
		$constructor = $user->constructor;

		if ($constructor != $this->id)
		{
			global $core;

			$module = $core->modules[$user->constructor];
		}

		$form = $module->getBlock('edit', $user->uid);

		$form->addChild
		(
			new WdElement
			(
				WdElement::E_SUBMIT, array
				(
					WdElement::T_GROUP => 'save',
					WdElement::T_INNER_HTML => 'Enregistrer',

					'class' => 'save'
				)
			)
		);

		//$form->hiddens[self::OPERATION_SAVE_MODE] = self::OPERATION_SAVE_MODE_CONTINUE;

		return $form;
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

	protected function block_config()
	{
		return array
		(
			WdElement::T_GROUPS => array
			(
				'notifies.password' => array
				(
					'title' => 'Envoie des informations de connexion',
					'class' => 'form-section flat'/*,
					'no-panels' => true*/
				)
			),

			WdElement::T_CHILDREN => array
			(
				"local[$this->flat_id.notifies.password]" => new WdEMailNotifyElement
				(
					array
					(
						WdElement::T_GROUP => 'notifies.password',
						//WdElement::T_DEFAULT => self::$config_default['notifies']['password']

						WdElement::T_DEFAULT => array
						(
							'subject' => 'Vos paramètres de connexion au WdPublisher',
							'from' => 'no-reply@' . $_SERVER['HTTP_HOST'],
							'template' => 'Bonjour,

Voici vos paramètres de connexion au système de gestion de contenu WdPublisher :

Identifiant : "#{@username}" ou "#{@email}"
Mot de passe : "#{@password}"

Une fois connecté vous pourrez modifier votre mot de passe. Pour cela cliquez sur votre nom dans la barre de titre et éditez votre profil.

Cordialement'
						)
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
			wd_log_error('The field %field is required!', array('%field' => 'Votre adresse E-Mail'));

			return false;
		}

		return true;
	}

	protected function operation_retrievePassword(WdOperation $operation)
	{
		$email = $operation->params[User::EMAIL];

		$uid = $this->model->select('{primary}')->where(array('email' => $email))->rc;

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
		$record = $operation->record;
		$record->is_activated = true;
		$record->save();

		wd_log_done('!name is now active', array('!name' => $record->name));

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
		$record = $operation->record;
		$record->is_activated = false;
		$record->save();

		wd_log_done('!name is now inactive', array('!name' => $record->name));

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
		global $core;

		#
		# load and check user id
		#

		$user = $this->model[$uid];

		if (!$user)
		{
			wd_log_error('Unknown user id: %uid', array('%uid' => $uid));

			return false;
		}

		#
		# load the configuration to send the email from the registry
		#

		// TODO-20110108: the config should be local, with a group and global fallback.

		$r = $core->registry["$this->flat_id.notifies.password."];

		if (!$r)
		{
			$r = $core->registry->get('user_users.notifies.password.', self::$config_default['notifies']['password']);
		}

		if (!$r || empty($r['template']))
		{
			wd_log_error('Les paramètres de connexion ne peuvent pas être envoyés parce que la configuration est incomplète.');

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
			wd_log_error("Impossible d'envoyer les paramètres de connexion à %email", array('%email' => $user->email));

			return false;
		}

		wd_log_done('Les paramètres de connexion ont été envoyés à %email', array('%email' => $user->email));

		$user->password = $password;
		$user->save();

		return true;
	}

	public function hook_get_user(WdCore $core)
	{
		$user = null;
		$uid = $core->user_id;

		if ($uid)
		{
			$user = $this->model[$uid];

			if ($user && $user->language)
			{
				WdI18n::setLanguage($user->language);
			}
		}

		if (!$user)
		{
			unset($core->session->application['user_id']);

			$user = new User();
		}

		return $user;
	}

	/**
	 * Return the user's id.
	 */

	static public function hook_get_user_id(WdCore $core)
	{
		return isset($core->session->application['user_id']) ? $core->session->application['user_id'] : null;
	}
}



class WdTimeZoneElement extends WdElement
{
	public function __construct($tags=array(), $dummy=null)
	{
		$options = array();

		$now = time();
		$time = -39600;
		$i = 24;

		$tz = date_default_timezone_get();
		date_default_timezone_set('GMT');

		while (--$i)
		{
			$time += 3600;

			$options[$time] = strftime('%d %b %Y - %H:%M', $now + $time) . ' ' . ($time / 3600 * 100);
		}

		date_default_timezone_set($tz);

		parent::__construct
		(
			'select', $tags + array
			(
				self::T_OPTIONS => $options
			)
		);
	}
}