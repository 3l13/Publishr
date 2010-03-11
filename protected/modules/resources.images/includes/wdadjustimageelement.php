<?php

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

		$document->addStyleSheet('../public/wdadjustimage.css');
		$document->addJavaScript('../public/wdadjustimage.js');
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
		$rc .= $core->getModule('resources.images')->getBlock('adjustResults', array('selected' => $this->getTag('value')));
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

		$rc .= '<div class="arrow"><div></div></div>';

		return $rc;
	}
}