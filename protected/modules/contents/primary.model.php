<?php

class contents_WdModel extends system_nodes_WdModel
{
	public function save(array $properties, $key=null, array $options=array())
	{
		if (isset($properties[Node::TITLE]))
		{
			$properties += array
			(
				Node::SLUG => wd_normalize($properties[Node::TITLE])
			);
		}

		return parent::save($properties, $key, $options);
	}

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
			}
		}

		return array($where, $params);
	}
}