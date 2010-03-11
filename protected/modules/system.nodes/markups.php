<?php

class system_nodes_WdMarkups extends patron_markups_WdHooks
{
	static protected function model($name='system.nodes')
	{
		return parent::model($name);
	}

	static protected function parseSelect($select)
	{
		list($where, $params) = parent::parseSelect($select);

		foreach ($select as $identifier => $value)
		{
			switch ($identifier)
			{
				case 'nid':
				{
					$where[] = 'nid = ?';
					$params[] = $value;
				}
				break;

				case 'slug':
				{
					$where[] = 'slug = ?';
					$params[] = $value;
				}
				break;
			}
		}

		return array($where, $params);
	}

	static public function node(WdHook $hook, WdPatron $patron, $template)
	{
		$select = $hook->params['select'];

		if (!$select)
		{
			return;
		}

		if (!is_numeric($select))
		{
			$select = self::model()->select('nid', 'WHERE slug = ? OR title = ? LIMIT 1', array($select, $select))->fetchColumnAndClose();
		}

		$entry = self::model()->load($select);

		if (!$entry)
		{
			return;
		}

		return $patron->publish($template, $entry);
	}
}