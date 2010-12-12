<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class contents_view_WdMarkup extends system_nodes_view_WdMarkup
{

}

class contents_list_WdMarkup extends system_nodes_list_WdMarkup
{
	public function __invoke(array $args, WdPatron $patron, $template)
	{
		return parent::__invoke
		(
			$args + array
			(
				'order' => 'date DESC'
			),

			$patron, $template
		);
	}
}

class contents_home_WdMarkup extends contents_list_WdMarkup
{
	protected function get_limit($which='home', $default=4)
	{
		return parent::get_limit($which, $default);
	}

	protected function loadRange($select, &$range, $order=null)
	{
		global $core;

		$entries = $this->model->loadRange
		(
			0, $range['limit'], 'WHERE constructor = ? AND is_online = 1 AND is_home_excluded = 0 AND (siteid = ? OR siteid = 0) AND (language = ? OR language = "") ORDER BY date DESC', array
			(
				$this->invoked_constructor ? $this->invoked_constructor : $this->constructor, $core->site_id, WdI18n::$language
			)
		)
		->fetchAll();

		WdEvent::fire
		(
			'publisher.nodes_loaded', array
			(
				'nodes' => $entries
			)
		);

		return $entries;
	}
}