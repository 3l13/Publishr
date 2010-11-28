<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class WdAdjustImageElement extends WdElement
{
	public function __construct($tags=array(), $dummy=null)
	{
		parent::__construct
		(
			'div', $tags + array
			(
				'class' => 'wd-adjustimage'
			)
		);

		global $document;

		$document->css->add('adjustimage.css');
		$document->js->add('adjustimage.js');
	}

	protected function getInnerHTML()
	{
		global $core;

		$rc = parent::getInnerHTML();

		#
		# results
		#

		$rc .= '<div class="search">';
		$rc .= '<input type="text" class="search" />';
		$rc .= $core->getModule('resources.images')->getBlock('adjustResults', array('selected' => $this->get('value')));
		$rc .= '</div>';


		/*
		$rc .= '<div class="thumbnail" style="float: right">';

			$rc .= '<div style="margin: 1em">';
			$rc .= new WdThumbnailerConfigElement(array());
			$rc .= '</div>';

		$rc .= '</div>';


		$rc .= '<div class="clear"></div>';
		*/

		#
		# confirm
		#

		$rc .= '<div class="confirm">';
		$rc .= '<button type="button" class="cancel">Annuler</button>';
		$rc .= '<button type="button" class="continue">Utiliser</button>';
		$rc .= '<button type="button" class="none warn">Aucune</button>';
		$rc .= '</div>';

		#
		# arrow
		#

		$rc .= '<div class="arrow"><div></div><div>';

		return $rc;
	}
}