<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class WdAdjustThumbnailElement extends WdElement
{
	public function __construct($tags)
	{
		global $document;

		$this->addClass('widget-adjust-thumbnail');

		parent::__construct
		(
			'div', $tags + array
			(
				WdElement::T_CHILDREN => array
				(
					$this->adjust_image = new WdAdjustImageElement(array()),
					$this->adjust_thumbnail_options = new WdAdjustThumbnailOptionsElement(array())
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

	static public function operation_popup(WdOperation $operation)
	{
		$params = &$operation->params;

		$el = (string) new WdAdjustThumbnailElement
		(
			array
			(
				'value' => isset($params['selected']) ? $params['selected'] : null
			)
		);

		$label_cancel = t('label.cancel');
		$label_use = t('label.use');
		$label_remove = t('label.remove');

		return <<<EOT
<div class="popup">
	$el

	<div class="confirm">
		<button type="button" class="cancel">$label_cancel</button>
		<button type="button" class="none warn">$label_remove</button>
		<button type="button" class="continue">$label_use</button>
	</div>
	<div class="arrow"><div>&nbsp;</div></div>
</div>
EOT;
	}
}