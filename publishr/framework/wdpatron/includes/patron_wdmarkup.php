<?php

/**
 * This file is part of the WdPatron software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.weirdog.com/wdpatron/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.weirdog.com/wdpatron/license/
 */

class patron_WdMarkup /*extends WdObject*/
{
	protected $constructor;
	protected $model;

	public function __invoke(array $args, WdPatron $patron, $template)
	{
		throw new WdException('The __invoke method must be overrode');
	}

	protected function publish(WdPatron $patron, $template, $entries=null, array $options=array())
	{
		return $patron->publish($template, $entries, $options);
	}

	/*
	public function __get($property)
	{
		$getter = '__get_' . $property;

		if (method_exists($this, $getter))
		{
			return $this->$property = $this->$getter();
		}

		throw new WdException
		(
			'Unknow property %property for object of class %class', array
			(
				'%property' => $property,
				'%class' => get_class($this)
			)
		);
	}

	protected function __volatile_get_model()
	{
		global $core;

		return $core->models[$this->constructor];
	}
	*/
}