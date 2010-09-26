<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class contents_WdModel extends system_nodes_WdModel
{
	public function parseConditions(array $conditions)
	{
		list($where, $params) = parent::parseConditions($conditions);

		foreach ($conditions as $identifier => $value)
		{
			switch ($identifier)
			{
				case 'date':
				{
					$where[] = 'date = ?';
					$params[] = $value;
				}
				break;

				case 'year':
				{
					$where[] = 'YEAR(date) = ?';
					$params[] = $value;
				}
				break;

				case 'month':
				{
					$where[] = 'MONTH(date) = ?';
					$params[] = $value;
				}
				break;

				case 'is_home_excluded':
				{
					$where[] = 'is_home_excluded = ?';
					$params[] = $value;
				}
				break;
			}
		}

		return array($where, $params);
	}
}