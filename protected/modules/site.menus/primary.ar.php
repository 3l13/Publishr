<?php

class site_menus_WdActiveRecord extends system_nodes_WdActiveRecord
{
	protected function model($name='site.menus')
	{
		return parent::model($name);
	}

	protected function __get_pages()
	{
		return self::model('site.pages')->loadAll
		(
			'INNER JOIN {prefix}site_menus_pages AS jn ON pageid = nid
			WHERE is_online = 1 AND menuid = ? ORDER BY jn.weight', array
			(
				$this->nid
			)
		)
		->fetchAll();
	}
}