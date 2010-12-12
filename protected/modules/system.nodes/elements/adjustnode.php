<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class WdAdjustNodeElement extends WdElement
{
	const T_SCOPE = '#adjust-scope';

	public function __construct($tags=array(), $dummy=null)
	{
		parent::__construct
		(
			'div', $tags + array
			(
				self::T_SCOPE => 'system.nodes',

				'class' => 'wd-adjustnode'
			)
		);

		global $document;

		$document->css->add('adjustnode.css');
		$document->js->add('adjustnode.js');
	}

	protected function getInnerHTML()
	{
		global $core;

		$rc = parent::getInnerHTML();

		$scope = $this->get(self::T_SCOPE);

		#
		# results
		#

		$rc .= '<div class="search">';
		$rc .= '<input type="text" class="search" />';

		try
		{
			$rc .= $core->getModule($scope)->getBlock('adjustResults', array('selected' => $this->get('value')));
		}
		catch (Exception $e)
		{
			$rc .= (string) $e;
		}

		$rc .= '</div>';

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

		$rc .= '<div class="arrow"><div>&nbsp;</div></div>';

		#
		# options
		#

		$rc .= '<input type="hidden" class="options" value="' . wd_entities(json_encode(array('scope' => $scope))) . '" />';

		return $rc;
	}
}