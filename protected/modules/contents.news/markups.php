<?php

class contents_news_view_WdMarkup extends system_nodes_view_WdMarkup
{
	protected $constructor = 'contents.news';
}

class contents_news_list_WdMarkup extends system_nodes_list_WdMarkup
{
	protected $constructor = 'contents.news';

	public function __invoke(array $args, WdPatron $patron, $template)
	{
		global $document;

		// TODO-20100601: move document modifiers to view definitions

		$document->css->add('public/list.css');

		$select = $args['select'];
		$page = isset($select['page']) ? $select['page'] : $args['page'];
		$limit = $args['limit'];

		if ($limit === null)
		{
			global $registry;

			$limit = $registry->get(wd_camelCase($this->constructor, '.') . '.listLimit', 10);
		}

		$range = array
		(
			'count' => null,
			'page' => $page,
			'limit' => $limit
		);

		$entries = $this->loadRange($select, $range);

		if (!$entries)
		{
			return;
		}

		$patron->context['self']['range'] = $range;

		return $patron->publish($template, $entries);
	}

	protected function loadRange($select, &$range)
	{
		$page = $range['page'];
		$limit = $range['limit'];

		list($conditions, $args) = $this->parse_conditions($select);

		$where = 'WHERE ' . implode(' AND ', $conditions);

		$range['count'] = $this->model->count(null, null, $where, $args);

		return $this->model->loadRange
		(
			$page * $limit, $limit, $where . ' ORDER BY date DESC, title', $args
		)
		->fetchAll();
	}
}

class contents_news_home_WdMarkup extends contents_news_list_WdMarkup
{
	public function __invoke(array $args, WdPatron $patron, $template)
	{
		global $registry;

		$range = array
		(
			'limit' => $registry->get(wd_camelCase($this->constructor, '.') . '.homeLimit', 4)
		);

		$entries = $this->loadRange(null, $range);

		if (!$entries)
		{
			return;
		}

		return $patron->publish($template, $entries);
	}

	protected function loadRange($select, &$range)
	{
		return $this->model->loadRange
		(
			0, $range['limit'], 'WHERE constructor = ? AND is_online = 1 AND is_home_excluded = 0 AND language = ? OR language = "" ORDER BY date DESC', array
			(
				$this->constructor,
				WdLocale::$language
			)
		)
		->fetchAll();
	}
}