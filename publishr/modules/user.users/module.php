<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class user_users_WdModule extends WdPModule
{
	const OPERATION_CONNECT = 'connect';
	const OPERATION_DISCONNECT = 'disconnect';
	const OPERATION_ACTIVATE = 'activate';
	const OPERATION_DEACTIVATE = 'deactivate';
	const OPERATION_IS_UNIQUE = 'is_unique';
	/*
	const OPERATION_SEND_PASSWORD = 'send_password';
	const OPERATION_RECOVER_PASSWORD = 'recover_password';
	*/

	static $config_default = array
	(
		'notifies' => array
		(
			/*
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
			*/
		)
	);

	protected function resolve_primary_model_tags($tags)
	{
		return parent::resolve_model_tags($tags, 'primary') + array
		(
			user_users_WdModel::T_CONSTRUCTOR => $this->id
		);
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

	public function is_installed()
	{
		global $core;

		$config = $core->configs['user'];

		if (!$config)
		{
			wd_log_error('Missings <em>user</em> config!');

			return false;
		}

		return parent::is_installed();
	}

	protected function block_connect()
	{
		global $core;

		$core->document->js->add('public/connect.js');

		$form = (string) $this->form_connect();

		$label_email = t('label.your_email');
		$label_cancel = t('label.cancel');
		$label_send = t('label.send');

		return <<<EOT
<div id="login">
	<div class="wrapper">
		<div class="slide">$form</div>
	</div>

	<div class="wrapper password" style="height: 0">
		<div class="slide">
		<form class="group password login" name="password" action="">
			<div class="form-label form-label-email">
			<label class="required mandatory">$label_email&nbsp;<sup>*</sup><span class="separator">&nbsp;:</span></label>
			</div>

			<div class="form-element form-element-email">
			<input type="text" name="email" />
			<div class="element-description"><a href="#" class="cancel">$label_cancel</a></div>
			</div>

			<div class="form-element form-element-submit">
			<button class="warn big" type="submit">$label_send</button>
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

	public function form_connect()
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
		$permission_modify_own_profile = false;

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
			$permission_modify_own_profile = true;
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
		# roles
		#

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
					WdForm::T_LABEL => '.roles',
					WdElement::T_GROUP => 'advanced',
					WdElement::T_OPTIONS => $role_options,
					WdElement::T_OPTIONS_DISABLED => array(2 => true),
					WdElement::T_REQUIRED => true,
					WdElement::T_DESCRIPTION => '.roles',

					'class' => 'framed list sortable',
					'value' => $properties_rid
				)
			);
		}

		#
		# site limiter
		#

		$available_sites_el = null;

		if ($user->has_permission(self::PERMISSION_ADMINISTER, $this))
		{
			$uid = $properties['uid'];
			$value = array();

			if ($uid)
			{
				$record = $this->model[$uid];
				$value = explode(',', $record->metas['available_sites']);
				$value = array_combine($value, array_fill(0, count($value), true));
			}

			$available_sites_el = new WdElement
			(
				WdElement::E_CHECKBOX_GROUP, array
				(
					WdForm::T_LABEL => '.siteid',
					WdElement::T_OPTIONS => $core->models['site.sites']->select('siteid, IF(admin_title != "", admin_title, concat(title, ":", language))')->order('admin_title, title')->pairs,
					WdElement::T_GROUP => 'advanced',
					WdElement::T_DESCRIPTION => '.siteid',

					'class' => 'list framed',
					'value' => $value
				)
			);
		}

		$rc = array
		(
			WdForm::T_DISABLED => !$permission,

			WdElement::T_GROUPS => array
			(
				'contact' => array
				(
					'title' => '.contact',
					'class' => 'form-section flat'
				),

				'connection' => array
				(
					'title' => '.connection',
					'class' => 'form-section flat'
				),

				'advanced' => array
				(
					'title' => '.advanced',
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
						WdForm::T_LABEL => '.firstname',
						WdElement::T_GROUP => 'contact',

						//'class' => 'autofocus'
					)
				),

				User::LASTNAME => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => '.lastname',
						WdElement::T_GROUP => 'contact'
					)
				),

				User::USERNAME => $administer ? new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => '.username',
						WdElement::T_GROUP => 'contact',
						WdElement::T_REQUIRED => true
					)
				) : null,

				User::DISPLAY => new WdElement
				(
					'select', array
					(
						WdForm::T_LABEL => '.display_as',
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
						WdForm::T_LABEL => '.email',
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
									WdElement::T_LABEL => '.password',
									WdElement::T_LABEL_POSITION => 'above',
									WdElement::T_DESCRIPTION => '.password_' . ($uid ? 'update' : 'new'),

									'value' => '',
									'autocomplete' => 'off'
								)
							),

							'</div><div>',

							User::PASSWORD . '-verify' => new WdElement
							(
								WdElement::E_PASSWORD, array
								(
									WdElement::T_LABEL => '.password_confirm',
									WdElement::T_LABEL_POSITION => 'above',
									WdElement::T_DESCRIPTION => '.password_confirm',

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
						WdElement::T_LABEL => '.is_activated',
						WdElement::T_GROUP => 'connection',
						WdElement::T_DESCRIPTION => '.is_activated'
					)
				),

				User::RID => $role_el,

				User::LANGUAGE => new WdElement
				(
					'select', array
					(
						WdForm::T_LABEL => 'Language',
						WdElement::T_REQUIRED => true,
						WdElement::T_GROUP => 'advanced',
						WdElement::T_DESCRIPTION => t('user_users.element.description.language'),
						WdElement::T_OPTIONS => array(null => '') + $core->locale->conventions['localeDisplayNames']['languages']
					)
				),

				'timezone' => new WdTimeZoneWidget
				(
					array
					(
						WdForm::T_LABEL => '.timezone',
						WdElement::T_GROUP => 'advanced',
						WdElement::T_DESCRIPTION => "Si la zone horaire n'est pas définie celle
						du site sera utilisée à la place."
					)
				),

				'available_sites' => $available_sites_el
			)
		);

		if ($permission_modify_own_profile)
		{
			$rc[WdElement::T_CHILDREN]['#submit'] = new WdElement
			(
				WdElement::E_SUBMIT, array
				(
					WdElement::T_GROUP => 'save',
					WdElement::T_INNER_HTML => 'Enregistrer',

					'class' => 'save'
				)
			);
		}

		return $rc;
	}

	protected function block_profile()
	{
		global $core;

		$core->document->page_title = t('My profile');

		$module = $this;
		$user = $core->user;
		$constructor = $user->constructor;

		if ($constructor != $this->id)
		{
			$module = $core->modules[$user->constructor];
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
				/*
				"local[$this->flat_id.notifies.password]" => new WdEMailNotifyElement
				(
					array
					(
						WdElement::T_GROUP => 'notifies.password',
						//WdElement::T_DEFAULT => self::$config_default['notifies']['password']

						WdElement::T_DEFAULT => array
						(
							'subject' => 'Vos paramètres de connexion au Publishr',
							'from' => 'no-reply@' . $_SERVER['HTTP_HOST'],
							'template' => 'Bonjour,

Voici vos paramètres de connexion à la plateforme de gestion de contenu Publishr :

Identifiant : "#{@username}" ou "#{@email}"
Mot de passe : "#{@password}"

Une fois connecté vous pourrez modifier votre mot de passe. Pour cela cliquez sur votre nom dans la barre de titre et éditez votre profil.

Cordialement'
						)
					)
				)
				*/
			)
		);
	}

	/**
	 * Returns the user object.
	 *
	 * If the user identifier can be retrieved from the session, it is used to find the
	 * corresponding user.
	 *
	 * If no user could be found, a guest user object is returned.
	 *
	 * This is the getter for the `$core->user` property.
	 *
	 * @param WdCore $core
	 * @return user_users_WdActiveRecord The user object, or guest user object.
	 */
	public function hook_get_user(WdCore $core)
	{
		$user = null;
		$uid = $core->user_id;

		try
		{
			if ($uid)
			{
				$user = $this->model[$uid];
			}
		}
		catch (Exception $e) {}

		if (!$user)
		{
			if (WdSession::exists())
			{
				unset($core->session->application['user_id']);
			}

			$user = new User();
		}

		return $user;
	}

	/**
	 * Returns the user's identifier.
	 *
	 * This is the getter for the `$core->user_id` property.
	 *
	 * @param WdCore $core
	 * @return int|null Returns the identifier of the user or null if the user is a guest.
	 */
	static public function hook_get_user_id(WdCore $core)
	{
		if (WdSession::exists() && isset($core->session->application['user_id']))
		{
			if (isset($core->session->application['user_agent']) && $core->session->application['user_agent'] != md5($_SERVER['HTTP_USER_AGENT']))
			{
				return;
			}

			return $core->session->application['user_id'];
		}
	}
}