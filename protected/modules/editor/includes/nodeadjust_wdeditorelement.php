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
	public function __construct($tags, $dummy=null)
	{
		parent::__construct('div', $tags);
	}

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

	protected function getInnerHTML()
	{
		$rc = parent::getInnerHTML();

		$value = $this->get('value');
		$name = $this->get('name');

		$value = json_decode($value);
		$config = (array) $this->get(self::T_CONFIG, array());

		// TODO-20100816: rename 'scope' as 'contructor' ?

		if (isset($config['constructor']))
		{
			$scope = $config['constructor'];
		}
		else
		{
			$scope = (isset($config['scope'])) ? $config['scope'] : 'system.nodes';
		}

		$class = 'WdPopNodeElement';

		if ($scope == 'resources.images')
		{
			$class = 'WdPopImageElement';
		}

		$rc .= (string) new $class
		(
			array
			(
				WdPopNodeElement::T_SCOPE => $scope,

				'name' => $name,
				'value' => $value
			)
		);

		return $rc;
	}
}