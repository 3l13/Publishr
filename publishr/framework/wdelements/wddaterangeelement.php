<?php

class WdDateRangeElement extends WdElement
{
	const T_START_TAGS = '#daterange-start-tags';
	const T_FINISH_TAGS = '#daterange-finish-tags';

	public function __construct($tags=array(), $dummy=null)
	{
		$start_tags = isset($tags[self::T_START_TAGS]) ? $tags[self::T_START_TAGS] : array();
		$finish_tags = isset($tags[self::T_FINISH_TAGS]) ? $tags[self::T_FINISH_TAGS] : array();

		parent::__construct
		(
			'div', $tags + array
			(
				WdElement::T_CHILDREN => array
				(
					new WdDateElement
					(
						$start_tags + array
						(
							WdElement::T_LABEL => 'Début',
							WdElement::T_LABEL_POSITION => 'before',

							'name' => 'start'
						)
					),

					' &nbsp; ',

					new WdDateElement
					(
						$finish_tags + array
						(
							WdElement::T_LABEL => 'Fin',
							WdElement::T_LABEL_POSITION => 'before',

							'name' => 'finish'
						)
					)
				),

				'class' => 'wd-daterange'
			)
		);
	}
}