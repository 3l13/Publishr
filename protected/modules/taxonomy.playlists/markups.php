<?php

class taxonomy_playlists_WdMarkups  extends patron_markups_WdHooks
{
	static protected function model($name='taxonomy.playlists')
	{
		return parent::model($name);
	}

	static public function playlists(WdHook $hook, WdPatron $patron, $template)
	{
		$entries = self::model()->loadAll
		(
			'WHERE is_online = 1 ORDER BY date, title'
		)
		->fetchAll();

		return $patron->publish($template, $entries);
	}
}