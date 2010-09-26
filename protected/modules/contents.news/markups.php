<?php

class contents_news_list_WdMarkup extends system_nodes_list_WdMarkup
{
	protected $constructor = 'contents.news';

	public function __invoke(array $args, WdPatron $patron, $template)
	{
		return parent::__invoke
		(
			$args + array
			(
				'order' => 'date:desc'
			),

			$patron, $template
		);
	}
}

class contents_news_home_WdMarkup extends contents_news_list_WdMarkup
{
	protected function get_limit($which='home', $default=4)
	{
		return parent::get_limit($which, $default);
	}

	protected function loadRange($select, &$range, $order=null)
	{
		return $this->model->loadRange
		(
			0, $range['limit'], 'WHERE constructor = ? AND is_online = 1 AND is_home_excluded = 0 AND (language = ? OR language = "") ORDER BY date DESC', array
			(
				$this->constructor,
				WdLocale::$language
			)
		)
		->fetchAll();
	}
}

class contents_news_view_WdMarkup extends system_nodes_view_WdMarkup
{
	protected $constructor = 'contents.news';
}