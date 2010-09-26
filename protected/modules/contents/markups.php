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
	protected $constructor = 'contents';
}

class contents_list_WdMarkup extends system_nodes_list_WdMarkup
{
	protected $constructor = 'contents';
}

class contents_home_WdMarkup extends system_nodes_list_WdMarkup
{
	protected $constructor = 'contents';
}

class contents_WdMarkups
{
	static protected function model($name='contents')
	{
		global $core;

		return $core->models[$name];
	}

	static protected function parseSelect($select)
	{
		list($where, $params) = parent::parseSelect($select);

		foreach ($select as $identifier => $value)
		{
			switch ($identifier)
			{
				case 'month':
				{
					$where[] = 'MONTH(date) = ?';
					$params[] = $value;
				}
				break;

				case 'year':
				{
					$where[] = 'YEAR(date) = ?';
					$params[] = $value;
				}
				break;
			}
		}

		return array($where, $params);
	}
}