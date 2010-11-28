<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class contents_WdManager extends system_nodes_WdManager
{
	public function __construct($module, array $tags=array())
	{
		parent::__construct
		(
			$module, $tags + array
			(
				self::T_ORDER_BY => array('date', 'desc')
			)
		);

		global $document;

		$document->css->add('public/manage.css');
		$document->js->add('public/manage.js');
	}

	protected function columns()
	{
		return parent::columns() + array
		(
			'date' => array
			(
				self::COLUMN_CLASS => 'date'/*,
				self::COLUMN_HOOK => array($this, 'get_cell_datetime')*/
			),

			'is_home_excluded' => array
			(
				self::COLUMN_LABEL => ''
			)
		);
	}

	protected function get_cell_is_home_excluded($entry, $tag)
	{
		return new WdElement
		(
			'label', array
			(
				WdElement::T_CHILDREN => array
				(
					new WdElement
					(
						WdElement::E_CHECKBOX, array
						(
							'value' => $entry->nid,
							'checked' => ($entry->$tag != 0),
							'class' => 'is_home_excluded'
						)
					)
				),

				'title' => "Inclure ou exclure l'entrée de la page d'accueil",
				'class' => 'checkbox-wrapper home'
			)
		);
	}
}