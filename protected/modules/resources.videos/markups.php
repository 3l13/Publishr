<?php

class resources_videos_WdMarkups extends patron_markups_WdHooks
{
	static protected function model($name='resources.videos')
	{
		return parent::model($name);
	}

	static public function home(array $args, WdPatron $patron, $template)
	{
		global $registry;

		$limit = $args['limit'];

		if ($limit === null)
		{
			$limit = $registry->get('resourcesVideos.homeLimit', 4);
		}

		return self::videos
		(
			$args + array
			(
				'select' => null,
				'page' => 0
			),

			$patron, $template
		);
	}

	static public function videos(array $args, WdPatron $patron, $template)
	{
		$select = $args['select'];
		$limit = $args['limit'];
		$page = $args['page'];

		if (is_array($select))
		{
			if (isset($select['page'])/* && !isset($args['page'])*/)
			{
				$page = $select['page'];
			}
		}

		$where = array
		(
			'is_online = 1',
			'(language = "" OR language = ?)',
			'constructor = "resources.videos"'
		);

		$params = array
		(
			WdLocale::$language
		);

		$where = 'WHERE ' . implode(' AND ', $where);;

		$count = self::model()->count(null, null, $where, $params);

		$entries = self::model()->loadRange
		(
			$page * $limit, $limit, $where . ' ORDER BY created DESC, title', $params
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