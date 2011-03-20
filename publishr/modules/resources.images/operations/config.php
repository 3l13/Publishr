<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class resources_images__config_WdOperation extends config_WdOperation
{
	protected function process()
	{
		global $core;

		$params = &$this->params;

		$key = $this->module->flat_id . '.property_scope';
		$scope = null;

		if (isset($params['local'][$key]))
		{
			$scope = implode(',', array_keys($params['local'][$key]));

			unset($params['local'][$key]);
		}

		$core->working_site->metas[$key] = $scope;

		return parent::process();
	}
}