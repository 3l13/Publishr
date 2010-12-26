<?php

/**
 * This file is part of the WdElements framework
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.weirdog.com/wdelements/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.weirdog.com/wdelements/license/
 */

class WdDateElement extends WdElement
{
	public function __construct($tags, $dummy=null)
	{
		parent::__construct
		(
			WdElement::E_TEXT, $tags + array
			(
				'size' => 16,
				'class' => 'date'
			)
		);

		global $document;

		if (isset($document))
		{
			$document->js->add('public/datepicker/datepicker.js');
			$document->js->add('public/datepicker/auto.js');

//			$document->css->add('public/datepicker/datepicker.css');

			$document->css->add('public/datepicker/calendar-eightysix-v1.1-default.css');
			$document->css->add('public/datepicker/calendar-eightysix-v1.1-osx-dashboard.css');
		}
	}

	public function __toString()
	{
		$value = $this->get('value');

		if (!(int) $value)
		{
			$this->set('value', null);
		}

		return parent::__toString();
	}
}