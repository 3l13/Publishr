<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

abstract class WdWidget extends WdElement
{
	/**
	 * Interpolates a css class from the widget class and add it to the class list.
	 *
	 * @param string $type
	 * @param array $tags
	 */
	public function __construct($type, $tags)
	{
		preg_match('#Wd(.+)(Element|Widget)#', get_class($this), $matches);

		$class = 'widget-' . wd_hyphenate($matches[1]);

		parent::__construct($type, $tags);

		$this->addClass($class);
	}

	public function get_results(array $options=array())
	{
		throw new WdException('The widget class %class does not implement results', array('%class' => get_class($this)));
	}
}