<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

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

		$document->css->add('public/manage.css');
		$document->js->add('public/manage.js');
	}

	protected function columns()
	{
		return array
		(
			User::USERNAME => array
			(
				self::COLUMN_LABEL => 'Username',
				self::COLUMN_SORT => WdResume::ORDER_ASC
			),

			User::EMAIL => array
			(
				self::COLUMN_LABEL => 'E-Mail',
				self::COLUMN_HOOK => array(__CLASS__, 'email_callback'),
			),

			User::RID => array
			(
				self::COLUMN_LABEL => 'Role'
			),

			User::CREATED => array
			(
				self::COLUMN_CLASS => 'date'
			),

			User::LASTCONNECTION => array
			(
				self::COLUMN_CLASS => 'date'
			),

			User::IS_ACTIVATED => array
			(
				self::COLUMN_LABEL => 'Activé',
				self::COLUMN_CLASS => 'is_activated'
			)
		);
	}

	protected function jobs()
	{
		global $core;

		// TODO: use parent::jobs()

		$jobs = array
		(
			user_users_WdModule::OPERATION_ACTIVATE => 'Activer',
			user_users_WdModule::OPERATION_DEACTIVATE => 'Désactiver'
		);

		if ($core->user->has_permission(WdModule::PERMISSION_MANAGE, $this->module))
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
		else if ($entry->roles)
		{
			$label = '';

			foreach ($entry->roles as $role)
			{
				$label .= ', ' . $role->role;
			}

			$label = substr($label, 2);
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
		if ($entry->is_admin())
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