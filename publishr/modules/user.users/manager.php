<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
				'label' => 'Username',
				'ordering' => true
			),

			User::EMAIL => array
			(
				'label' => 'E-Mail'
			),

			User::RID => array
			(
				'label' => 'Role',
				'orderable' => false
			),

			User::CREATED => array
			(
				'class' => 'date'
			),

			User::LASTCONNECTION => array
			(
				'class' => 'date'
			),

			User::IS_ACTIVATED => array
			(
				'label' => 'Activated',
				'class' => 'is_activated',
				'orderable' => false
			)
		);
	}

	protected function jobs()
	{
		global $core;

		// TODO: use parent::jobs()

		$jobs = array
		(
			user_users_WdModule::OPERATION_ACTIVATE => t('activate.operation.title'),
			user_users_WdModule::OPERATION_DEACTIVATE => t('deactivate.operation.title')
		);

		return $jobs;
	}

	protected function render_cell_username($record)
	{
		$label = $record->username;
		$name = $record->name;

		if ($label != $name)
		{
			$label .= ' <small>(' . $name . ')</small>';
		}

		return parent::modify_code($label, $record->uid, $this);
	}

	protected function render_cell_rid($record, $property)
	{
		if ($record->uid == 1)
		{
			return '<em>Admin</em>';
		}
		else if ($record->roles)
		{
			$label = '';

			foreach ($record->roles as $role)
			{
				if ($role->rid == 2)
				{
					continue;
				}

				$label .= ', ' . $role->role;
			}

			$label = substr($label, 2);
		}

		return parent::render_filter_cell($record, $property, $label);
	}

	protected function render_cell_created($record, $property)
	{
		return $this->render_cell_datetime($record, $property);
	}

	protected function render_cell_lastconnection($record, $property)
	{
		if (!((int) $record->$property))
		{
			return '<em class="small">Never connected</em>';
		}

		return $this->render_cell_datetime($record, $property);
	}

	protected function render_cell_is_activated($record)
	{
		if ($record->is_admin())
		{
			return;
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
							'value' => $record->uid,
							'checked' => ($record->is_activated != 0)
						)
					)
				),

				'class' => 'checkbox-wrapper circle'
			)
		);
	}
}