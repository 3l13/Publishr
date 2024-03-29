<?php

class resources_videos_WdMarkups extends patron_markups_WdHooks
{
	static protected function model($name='resources.videos')
	{
		return parent::model($name);
	}

	static public function home(array $args, WdPatron $patron, $template)
	{
		global $core;

		$limit = $args['limit'];

		if ($limit === null)
		{
			$limit = $core->registry->get('resourcesVideos.homeLimit', 4);
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

		$arq = self::model()->where('is_online = 1 AND (language = "" OR language = ?) AND constructor = "resources.videos"', $page->language);

		$count = $arq->count;
		$entries = $arq->limit($page * $limit, $limit)->order('created DESC, title')->all;

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