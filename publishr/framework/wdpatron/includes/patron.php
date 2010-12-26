<?php

/**
 * This file is part of the WdPatron software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.weirdog.com/wdpatron/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.weirdog.com/wdpatron/license/
 */

/**
 * Initialize the parser and return the result of its publish method.
 *
 * @param $template
 * @return string The template published
 */

function Patron($template, $bind=null, array $options=array())
{
	return WdPatron::getSingleton('WdPublisher')->publish($template, $bind, $options);
}