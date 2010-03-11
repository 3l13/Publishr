<?php

class contents_articles_WdManager extends contents_WdManager
{
	protected $taxonomy;

	protected function columns()
	{
		global $core;

		$this->taxonomy = $core->getModule('taxonomy.support');

		$taxonomy_columns = $this->taxonomy->getManageColumns((string) $this->module);

		//wd_log('columns: \1', array($taxonomy_columns));

		return parent::columns() + $taxonomy_columns + array
		(
			Article::DATE => array
			(
				self::COLUMN_LABEL => 'Date',
				self::COLUMN_HOOK => array(__CLASS__, 'date_callback'),
				self::COLUMN_CLASS => 'date',
				self::COLUMN_SORT => self::ORDER_DESC
			)
		);
	}
}