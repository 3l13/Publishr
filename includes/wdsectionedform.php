<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class WdSectionedForm extends WdForm
{
	public function __toString()
	{
		$this->contextPush();

		$groups = $this->get(self::T_GROUPS, array());

		self::sort_by($groups, 'weight');

		#
		# dispatch children into groups
		#

		foreach ($this->children as $name => $element)
		{
			if (!$element)
			{
				continue;
			}

			$group = is_object($element) ? $element->get(WdElement::T_GROUP, 'primary') : 'primary';

			$groups[$group][self::T_CHILDREN][$name] = $element;
		}

		#
		# now we the groups
		#

		$children = array();

		foreach ($groups as $group)
		{
			if (empty($group[self::T_CHILDREN]))
			{
				continue;
			}

			#
			# sort children
			#

			self::sort_elements_by($group[self::T_CHILDREN], self::T_WEIGHT);

			#
			# section title
			#

			if (isset($group['title']))
			{
				$title = $group['title'];

				if (is_array($title))
				{
					$title = $title[$key ? ($permission ? 1 : 2) : 0];
				}

				$children[] = '<h3>' . t($title) . '</h3>';

				if (isset($group['description']))
				{
					$children[] = '<div class="group description"><div class="small">' . $group['description'] . '</div></div>';
				}
			}

			#
			# section
			#

			$class = empty($group['no-panels']) ? 'WdFormSectionElement' : 'WdElement';

			$children[] = new $class
			(
				'div', array
				(
					self::T_CHILDREN => $group[self::T_CHILDREN],

					'class' => 'form-section'
				)
			);
		}

		#
		#
		#

		$this->children = $children;

		$rc = parent::__toString();

		$this->contextPop();

		return $rc;
	}

	static protected function sort_by(&$array, $by, $order='asc')
	{
		$groups = array();

		foreach ($array as $key => $value)
		{
			$order = isset($value[$by]) ? $value[$by] : null;

			$groups[$order][$key] = $value;
		}

		if (!$groups)
		{
			return;
		}

		($order == 'desc') ? krsort($groups) : ksort($groups);

		$array = call_user_func_array('array_merge', $groups);
	}

	static protected function sort_elements_by(&$array, $by, $order='asc')
	{
		$groups = array();

		foreach ($array as $key => $value)
		{
			if (!$value)
			{
				continue;
			}

			$order = is_object($value) ? $value->get($by) : $value[$by];

			$groups[$order][$key] = $value;
		}

		($order == 'desc') ? krsort($groups) : ksort($groups);

		$array = call_user_func_array('array_merge', $groups);
	}
}