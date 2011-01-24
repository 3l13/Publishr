<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class contents_agenda_WdModule extends contents_WdModule
{
	protected function block_manage()
	{
		return new contents_agenda_WdManager
		(
			$this, array
			(
				WdManager::T_COLUMNS_ORDER => array
				(
					'title', 'uid', 'is_home_excluded', 'is_online', 'date', 'finish', 'modified'
				)
			)
		);
	}

	protected function block_edit(array $properties, $permission)
	{
		return wd_array_merge_recursive
		(
			parent::block_edit($properties, $permission), array
			(
				WdElement::T_CHILDREN => array
				(
					'date' => null,

					new WdDateRangeElement
					(
						array
						(
							WdDateRangeElement::T_START_TAGS => array
							(
								WdElement::T_LABEL => 'Date de début',
								WdElement::T_REQUIRED => true,

								'name' => 'date'
							),

							WdDateRangeElement::T_FINISH_TAGS => array
							(
								WdElement::T_LABEL => 'Date de fin'
							)
						)
					)
				)
			)
		);
	}

	protected function block_head()
	{
		return Patron(file_get_contents('views/head.html', true, array('file' => dirname(__FILE__) . '/views/head.html')));
	}

	protected function block_view()
	{
		return Patron(file_get_contents('views/view.html', true, array('file' => dirname(__FILE__) . '/views/view.html')));
	}
}