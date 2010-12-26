<?php

/**
 * This file is part of the WdElements framework
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.weirdog.com/wdelements/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.weirdog.com/wdelements/license/
 */

class WdFormSectionElement extends WdElement
{
	const T_PANEL_CLASS = '#form-section-panel-class';

	protected function getInnerHTML()
	{
		$rc = null;
		$children = $this->get_ordered_children();

		foreach ($children as $name => $element)
		{
			//wd_log('name: %name, element: %element', array('%name' => $name, '%element' => $element));

			if (!$element)
			{
				continue;
			}

			$class = 'panel ' . (is_object($element) ? $element->get(self::T_PANEL_CLASS) : '');

			$rc .= '<div class="' . rtrim($class) . '">';

			if (is_object($element))
			{
				$label = t($element->get(WdForm::T_LABEL));

				if ($label)
				{
					$rc .= '<div class="form-label">';
					$rc .= $label;

					if ($element->get(WdElement::T_REQUIRED))
					{
						$rc .= ' <sup>*</sup>';
					}

					$rc .= '<span class="separator">&nbsp;:</span>';

					$rc .= '</div>';
				}
			}

			$rc .= '<div class="form-element">';
			$rc .= $element;
			$rc .= '</div>';

			$rc .= '</div>';
		}

		return $rc;
	}
}