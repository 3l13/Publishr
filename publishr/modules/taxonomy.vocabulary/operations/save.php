<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class taxonomy_vocabulary__save_WdOperation extends publishr_save_WdOperation
{
	protected function __get_properties()
	{
		global $core;

		$params = $this->params;
		$properties = parent::__get_properties();

		if (isset($params['scope']))
		{
			$properties['scope'] = $params['scope'];
		}

		if (!$this->key || !$core->user->has_permission(system_nodes_WdModule::PERMISSION_MODIFY_ASSOCIATED_SITE))
		{
			$properties['siteid'] = $core->working_site_id; // FIXME-20110312: should be site_id
		}

		return $properties;
	}
}