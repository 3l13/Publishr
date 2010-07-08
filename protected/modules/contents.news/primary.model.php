<?php

class contents_news_WdModel extends contents_WdModel
{
	public function parseConditions(array $conditions)
	{
		list($where, $params) = parent::parseConditions($conditions);

		foreach ($conditions as $identifier => $value)
		{
			switch ($identifier)
			{
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