<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class WdAdjustImageElement extends WdAdjustNodeElement
{
	public function __construct($tags=array(), $dummy=null)
	{
		global $core;

		parent::__construct
		(
			$tags + array
			(
				self::T_CONSTRUCTOR => 'resources.images',

				'class' => 'widget-adjust-image adjust'
			)
		);

		$this->dataset['adjust'] = 'adjustimage';

		$document = $core->document;

		$document->css->add('adjustimage.css');
		$document->js->add('adjustimage.js');

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
								'pop-preview-delay' => 500,
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

	static public function operation_get(WdOperation $operation)
	{
		$params = &$operation->params;

		return (string) new WdAdjustImageElement
		(
			array
			(
				'value' => isset($params['selected']) ? $params['selected'] : null
			)
		);
	}

	static public function operation_results(WdOperation $operation)
	{
		$params = &$operation->params;

		$el = new WdAdjustImageElement
		(
			array
			(
				'value' => isset($params['selected']) ? $params['selected'] : null
			)
		);

		return $el->get_results('resources.images', $_GET);
	}
}