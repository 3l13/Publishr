<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class WdAdjustThumbnailWidget extends WdWidget
{
	public function __construct($tags)
	{
		global $document;

		parent::__construct
		(
			'div', $tags + array
			(
				WdElement::T_CHILDREN => array
				(
					$this->adjust_image = new WdAdjustImageWidget(array()),
					$this->adjust_thumbnail_options = new WdAdjustThumbnailOptionsWidget(array())
				),

				'class' => 'adjust'
			)
		);

		$document->js->add('adjust-thumbnail.js');
		$document->css->add('adjust-thumbnail.css');
	}

	public function getInnerHTML()
	{
		return parent::getInnerHTML() . '<div class="more">✔</div>';
	}
}