<?php

class contents_WdMarkups extends system_nodes_WdMarkups
{
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