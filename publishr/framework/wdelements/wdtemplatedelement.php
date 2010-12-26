<?php

/**
 * This file is part of the WdElements framework
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.weirdog.com/wdelements/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.weirdog.com/wdelements/license/
 */

require_once 'wdelement.php';

class WdTemplatedElement extends WdElement
{
	protected $template;

	static protected $label_right_separator = '<span class="separator">&nbsp;:</span>';
	static protected $label_left_separator = '<span class="separator">:&nbsp;</span>';

	public function __construct($type, array $tags, $template=null)
	{
		$this->template = $template;

		parent::__construct($type, $tags);
	}

	protected function getInnerHTML()
	{
		$replace = array();

		foreach ($this->children as $name => $child)
		{
			if (!$child)
			{
				continue;
			}

			if (!is_object($child))
			{
				WdDebug::trigger('Child must be an object, given: !child', array('!child' => $child));

				continue;
			}

			#
			# label
			#

			$label = $child->get(WdForm::T_LABEL);

			if ($label)
			{
				$label = t($label);
				$is_required = $child->get(self::T_REQUIRED);

				$child_id = $child->get('id');

				if (!$child_id)
				{
					$child_id = WdForm::getAutoElementId();

					$child->set('id', $child_id);
				}

				// TODO: clean up this mess

				$markup_start = '<label';

				if ($is_required)
				{
					$markup_start .= ' class="required mandatory"';
				}

				$markup_start .= ' for="' . $child_id . '">';

				$start =  $is_required ? $markup_start . $label . '&nbsp;<sup>*</sup>' : $markup_start . $label;
				$finish = '</label>';

				/*
				$complement = $child->get(self::T_LABEL_COMPLEMENT);

				if ($complement)
				{
					$finish = ' <span class="complement">' . $complement . '</span>' . $finish;
				}
				*/

				$replace['{$' . $name . '.label}'] = $start . $finish;
				$replace['{$' . $name . '.label:}'] = $start . self::$label_right_separator . $finish;
				$replace['{$' . $name . '.:label}'] = $markup_start . self::$label_left_separator . $start . $finish;
			}

			#
			# element
			#

			$replace['{$' . $name . '}'] = (string) $child;
		}

		$contents = strtr($this->template, $replace);

		return $contents;

		/*
		$this->contextPush();

		$this->children = array();

		$rc = parent::getInnerHTML();

		$this->contextPop();

		return $rc . $contents;
		*/
	}
}