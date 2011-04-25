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
 * Returns model specific default values for the form.
 */
class feedback_forms__defaults_WdOperation extends WdOperation
{
	/**
	 * Controls for the operation: authentication, permission(create)
	 * @see WdOperation::__get_controls()
	 */
	protected function __get_controls()
	{
		return array
		(
			self::CONTROL_AUTHENTICATION => true,
			self::CONTROL_PERMISSION => WdModule::PERMISSION_CREATE
		)

		+ parent::__get_controls();
	}

	/**
	 * Validates the operation unles the operation key is not defined.
	 *
	 * @see WdOperation::validate()
	 */
	protected function validate()
	{
		if (!$this->key)
		{
			wd_log_error('Missing modelid');

			return false;
		}

		return true;
	}

	/**
	 * The "defaults" operation can be used to retrieve the default values for the form, usualy
	 * the values for the notify feature.
	 *
	 * @see WdOperation::process()
	 */
	protected function process()
	{
		global $core;

		$modelid = $this->key;
		$models = $core->configs->synthesize('formmodels', 'merge');

		if (empty($models[$modelid]))
		{
			wd_log_error("Unknown model");

			return;
		}

		$model = $models[$modelid];
		$model_class = $model['class'];

		if (!method_exists($model_class, 'get_defaults'))
		{
			wd_log_done("Model doesn't have defaults");

			return false;
		}

		return call_user_func(array($model_class, 'get_defaults'));
	}
}