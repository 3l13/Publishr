<?php

class taxonomy_playlists_WdActiveRecord extends system_nodes_WdActiveRecord
{
	protected function model($name='taxonomy.playlists')
	{
		return parent::model($name);
	}

	protected function __get_songs()
	{
		return $this->model('resources.songs')->loadAll
		(
			'INNER JOIN {prefix}taxonomy_playlists_songs ON songid = nid
			WHERE is_online = 1 AND plid = ? ORDER BY weight', array
			(
				$this->nid
			)
		)
		->fetchAll();
	}
}