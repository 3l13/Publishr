<?php

class contents_news_WdMarkups extends contents_WdMarkups
{
	static protected function model($name='contents.news')
	{
		return parent::model($name);
	}

	static public function head(WdHook $hook, WdPatron $patron, $template)
	{
		$select = $hook->params['select'];

		if ($select)
		{
			$entry = self::model()->load($select);

			if (!$entry->is_online)
			{
				return '<p>Cette actualité est désactivée</p>';
			}

			return $patron->publish($template, $entry);
		}
		else
		{
			$page = $hook->params['page'];
			$limit = $hook->params['limit'];

			$where = array
			(
				'is_online = 1',
				'(language = "" OR language = ?)'
			);

			$params = array
			(
				WdLocale::$language
			);

			$where = 'WHERE ' . implode(' AND ', $where);;

			$count = self::model()->count(null, null, $where, $params);

			$entries = self::model()->loadRange
			(
				$page * $limit, $limit, $where . ' ORDER BY date DESC, title', $params
			)
			->fetchAll();

			if (!$entries)
			{
				return;
			}

			$patron->context['self']['range'] = array
			(
				'count' => $count,
				'page' => $page,
				'limit' => $limit
			);

			return $patron->publish($template, $entries);
		}
	}

	static public function news(WdHook $hook, WdPatron $patron, $template)
	{
		$where  = array();
		$params = array();

		$select = $hook->params['select'];

		if (is_array($select))
		{
			list($where, $params) = self::model()->parseConditions($select);
		}
		else if (is_numeric($select))
		{
			$where[] = '`nid` = ?';
			$params[] = $select;
		}
		else
		{
			$where[] = '`slug` = ? OR `title` = ?';
			$params[] = $select;
			$params[] = $select;
		}

		$where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

		$entry = self::model()->loadRange(0, 1, $where . ' ORDER BY `date` DESC', $params)->fetchAndClose();

		//var_dump($where, $params, $entry);

		if (!$entry)
		{
			return;
		}
		else if (!$entry->is_online)
		{
			return '<p>Entrée hors ligne</p>';
		}

		return $patron->publish($template, $entry);
	}
}