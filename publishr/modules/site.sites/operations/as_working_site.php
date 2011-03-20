<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class site_sites__as_working_site_WdOperation extends WdOperation
{
	protected function __get_controls()
	{
		return array
		(
			self::CONTROL_AUTHENTICATION => true,
			self::CONTROL_RECORD => true
		)

		+ parent::__get_controls();
	}

	protected function validate()
	{
		return true;
	}

	protected function process()
	{
		global $core;

		$record = $this->record;
		$siteid = $record->siteid;

		if ($core->working_site_id == $siteid)
		{
			return;
		}

		$user = $core->user;
		$available_sites = $user->metas['available_sites'];

		if ($available_sites)
		{
			$available_sites = explode(',', $available_sites);

			if (!in_array($siteid, $available_sites))
			{
				throw new WdException("You don't have permission to administer this site", array(), 403);
			}
		}

		$core->session->application['working_site'] = $siteid;

		$params = $this->params;

		$this->location = isset($params['continue']) ? $params['continue'] : $record->url;

		return true;
	}
}