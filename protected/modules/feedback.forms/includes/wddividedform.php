<?php

class WdDividedForm extends Wd2CForm
{
	public function __construct($tags, $container_type='div', $container_tags=array())
	{
		parent::__construct($tags, $container_type, $container_tags);
	}
}