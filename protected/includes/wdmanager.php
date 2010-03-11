<?php

class WdManager extends WdResume
{
	public function __construct($module, array $tags=array())
	{
		$model_id = 'primary';

		if (is_string($module))
		{
			global $core;

			list($module_id, $model_id) = explode('/', $module) + array(1 => $model_id);

			$module = $core->getModule($module_id);
		}

		$model = $module->model($model_id);

		#
		# Set the properties here so that they are available to the columns() method, and others.
		#

		$this->module = $module;
		$this->model = $model;

		parent::__construct
		(
			$module, $model, $tags + array
			(
				self::T_BLOCK => 'manage',
				self::T_COLUMNS => $this->columns()
			)
		);

		#
		# TODO: move this to WdResume somewhere
		#

		$jobs = $this->jobs();

		foreach ($jobs as $operation => $label)
		{
			$this->addJob($operation, $label);
		}
	}

	protected function columns()
	{
		return array();
	}

	protected function jobs()
	{
		return array();
	}

	static protected $user_cache = array();
	static protected $user_model;

	protected function get_cell_user($entry, $tag)
	{
		$uid = $entry->$tag;

		if (empty(self::$user_cache[$uid]))
		{
			if (empty(self::$user_model))
			{
				global $core;

				self::$user_model = $core->getModule('user.users')->model();
			}

			self::$user_cache[$uid] = self::$user_model->load($uid);
		}

		$user = self::$user_cache[$uid];

		if (!$user)
		{
			return t('<span class="error">Unknown user: %uid</span>', array('%uid' => $uid));
		}

		$rc = ($user->firstname && $user->lastname) ? $user->firstname . ' ' . $user->lastname : $user->username;

		/*

		$rc = $user->name;

		if ($user->name != $user->username)
		{
			$rc .= ' <small>(' . $user->username . ')</small>';
		}

		*/

		return $rc;
	}
}