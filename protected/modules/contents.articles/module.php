<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class contents_articles_WdModule extends contents_WdModule
{
	protected function block_edit(array $properties, $permission)
	{
		return wd_array_merge_recursive
		(
			parent::block_edit($properties, $permission), array
			(
				WdElement::T_CHILDREN => array
				(
					contents_WdActiveRecord::DATE => new WdDateTimeElement
					(
						array
						(
							WdForm::T_LABEL => 'Date',
							WdElement::T_REQUIRED => true,
							WdElement::T_DEFAULT => date('Y-m-d H:i:s')
						)
					)
				)
			)
		);
	}
}