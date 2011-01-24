<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class contents_articles_WdManager extends contents_WdManager
{
	protected $taxonomy;

	protected function columns()
	{
		global $core;

		$this->taxonomy = $core->modules['taxonomy.support'];

		$taxonomy_columns = $this->taxonomy->getManageColumns((string) $this->module);

		//wd_log('columns: \1', array($taxonomy_columns));

		return parent::columns() + $taxonomy_columns;
	}
}