<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class WdAdjustImageWidget extends WdAdjustNodeWidget
{
	public function __construct($tags=array(), $dummy=null)
	{
		global $core;

		parent::__construct
		(
			$tags + array
			(
				self::T_CONSTRUCTOR => 'resources.images'
			)
		);

		$this->dataset['adjust'] = 'adjust-image';

		$document = $core->document;

		$document->css->add('adjust-image.css');
		$document->js->add('adjust-image.js');

		$document->css->add('../public/manage.css');
		$document->js->add('../public/manage.js');
	}

	protected function get_records($constructor, array $options, $limit=16)
	{
		return parent::get_records($constructor, $options, $limit);
	}

	protected function format_record(system_nodes_WdActiveRecord $record, $selected, array $range, array $options)
	{
		$recordid = $record->nid;

		return new WdElement
		(
			'li', array
			(
				WdElement::T_CHILDREN => array
				(
					new WdElement
					(
						'img', array
						(
							WdElement::T_DATASET => array
							(
								'nid' => $recordid,
								'pop-preview-delay' => 1000,
								'pop-preview-target' => '.widget-adjust-image'
							),

							'src' => $record->thumbnail('w:64;h:64'),
							'alt' => $record->alt,
							'width' => 64,
							'height' => 64,
							'class' => 'pop-preview'
						)
					)
				),

				WdElement::T_DATASET => array
				(
					Node::NID => $recordid,
					Node::TITLE => $record->title,
					File::PATH => $record->path
				),

				'class' => $recordid == $selected ? 'selected' : null
			)
		);
	}

	protected function get_results(array $options=array(), $constructor='resources.images')
	{
		return parent::get_results($options, $constructor);
	}
}