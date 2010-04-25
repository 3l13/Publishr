<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class nodeadjust_WdEditorElement extends WdEditorElement
{
	static public function toContents($params)
	{
		if (empty($params['contents']))
		{
			return;
		}

		return json_encode($params['contents']);
	}

	static public function render($contents)
	{
		global $core;

		$value = json_decode($contents);

		if ($value === null)
		{
			return;
		}

		return $core->getModule('system.nodes')->model()->load($value);
	}

	public function __toString()
	{
		$value = $this->get('value');
		$name = $this->get('name');

		$value = json_decode($value);

		return (string) new WdPopVideoElement
		(
			array
			(
				//WdPopNodeElement::T_SCOPE => 'resources.videos',

				'name' => $name,
				'value' => $value
			)
		);
	}
}