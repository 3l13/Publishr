<?php

class user_users_WdManager extends WdManager
{
	public function __construct($module, array $tags=array())
	{
		parent::__construct
		(
			$module, $tags + array
			(
				self::T_KEY => user_users_WdActiveRecord::UID
			)
		);

		global $document;

		$document->addStyleSheet('public/manage.css');
		$document->addJavascript('public/manage.js');
	}

	protected function columns()
	{
		return array
		(
			user_users_WdActiveRecord::USERNAME => array
			(
				self::COLUMN_LABEL => 'Username',
				self::COLUMN_SORT => WdResume::ORDER_ASC
			),

			user_users_WdActiveRecord::EMAIL => array
			(
				self::COLUMN_LABEL => 'E-Mail',
				self::COLUMN_HOOK => array(__CLASS__, 'email_callback'),
			),

			user_users_WdActiveRecord::RID => array
			(
				self::COLUMN_LABEL => 'Role'
			),

			user_users_WdActiveRecord::CREATED => array
			(
				self::COLUMN_CLASS => 'date'
			),

			user_users_WdActiveRecord::LASTCONNECTION => array
			(

			),

			user_users_WdActiveRecord::IS_ACTIVATED => array
			(
				self::COLUMN_LABEL => 'Activé',
				self::COLUMN_CLASS => 'is_activated'
			)
		);
	}

	protected function jobs()
	{
		global $user;

		// TODO: use parent::jobs()

		$jobs = array
		(
			user_users_WdModule::OPERATION_ACTIVATE => 'Activer',
			user_users_WdModule::OPERATION_DEACTIVATE => 'Désactiver'
		);

		if ($user->hasPermission(PERMISSION_MANAGE, $this->module))
		{
			$jobs[user_users_WdModule::OPERATION_PASSWORD] = 'Nouveau mot de passe';
		}

		return $jobs;
	}

	protected function get_cell_username($entry)
	{
		$label = $entry->username;
		$name = $entry->name;

		if ($label != $name)
		{
			$label .= ' <small>(' . $name . ')</small>';
		}

		return parent::modify_code($label, $entry->uid, $this);
	}

	protected function get_cell_rid($entry, $tag)
	{
		$label = '&nbsp;';

		if ($entry->uid == 1)
		{
			return '<em>Admin</em>';
		}
		else if ($entry->role)
		{
			$label = $entry->role->role;
		}

		return parent::select_code
		(
			$tag, $entry->$tag, $label, $this
		);
	}

	protected function get_cell_created($entry, $tag)
	{
		return $this->get_cell_datetime($entry, $tag);
	}

	protected function get_cell_lastconnection($entry, $tag)
	{
		if (!((int) $entry->$tag))
		{
			return '<em class="small">Never connected</em>';
		}

		return $this->get_cell_datetime($entry, $tag);
	}

	protected function get_cell_is_activated($entry)
	{
		if ($entry->isAdmin())
		{
			return '&nbsp;';
		}

		return new WdElement
		(
			'label', array
			(
				WdElement::T_CHILDREN => array
				(
					new WdElement
					(
						WdElement::E_CHECKBOX, array
						(
							'value' => $entry->uid,
							'checked' => ($entry->is_activated != 0)
						)
					)
				),

				'class' => 'checkbox-wrapper circle'
			)
		);
	}
}