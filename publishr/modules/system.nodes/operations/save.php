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
 * Saves a node of the "system.node" module instances.
 *
 * Adds the "display" save mode.
 *
 * @see publishr_save_WdOperation
 */
class system_nodes__save_WdOperation extends publishr_save_WdOperation
{
	const MODE_DISPLAY = 'display';

	/**
	 * Overrides the method to handle the following properties:
	 *
	 * `constructor`: In order to avoid misuse and errors, the constructor of the record is set by
	 * the method.
	 *
	 * `uid`: Only users with the PERMISSION_ADMINISTER permission can choose the user of records.
	 * If the user saving a record has no such permission, the Node::UID property is removed from
	 * the properties created by the parent method.
	 *
	 * `siteid`: If the user is creating a new record or the user has no permission to choose the
	 * record's site, the property is set to the value of the working site's id.
	 *
	 * @see save_WdOperation::__get_properties()
	 */
	protected function __get_properties()
	{
		global $core;

		$properties = parent::__get_properties();

		$user = $core->user;

		if (!$user->has_permission(WdModule::PERMISSION_ADMINISTER, $this->module))
		{
			unset($properties[Node::UID]);
		}

		if (!$this->key || !$user->has_permission(system_nodes_WdModule::PERMISSION_MODIFY_ASSOCIATED_SITE))
		{
			$properties[Node::SITEID] = $core->site_id;
		}

		if (!empty($properties[Node::SITEID]))
		{
			$properties[Node::LANGUAGE] = $core->models['site.sites'][$properties[Node::SITEID]]->language;
		}

		return $properties;
	}

	/**
	 * Overrides the method to provide a nicer log message, and change the operation location to
	 * the node URL if the save mode is "display".
	 *
	 * @see save_WdOperation::process()
	 */
	protected function process()
	{
		$rc = parent::process();
		$record = $this->module->model[$rc['key']];

		wd_log_done
		(
			$rc['mode'] == 'update' ? '%title has been updated in %module.' : '%title has been created in %module.', array
			(
				'%title' => wd_shorten($record->title), '%module' => $this->module->title
			),

			'save'
		);

		if ($this->mode == self::MODE_DISPLAY)
		{
			$url = $record->url;

			if ($url{0} != '#')
			{
				$this->location = $record->url;
			}
		}

		return $rc;
	}
}