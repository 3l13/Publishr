<?php

/**
 * This file is part of the WdElements framework
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.weirdog.com/wdelements/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.weirdog.com/wdelements/license/
 */

require_once 'wdform.php';

class Wd2CForm extends WdForm
{
	const T_NO_WRAPPERS = '#tableform-no-wrappers';

	public function __construct($tags, $container_type='table', array $container_tags=array())
	{
		parent::__construct($tags);

		#
		# we merge the provided container tags with the default table tags for the table element
		#

		$this->container = new Wd2CElement($container_type, $container_tags);
	}

	protected function getInnerHTML()
	{
		$this->contextPush();
		$this->container->contextPush();

		$this->container->children = $this->get_ordered_children();
		$this->container->contents = null;

		$this->children = array($this->container);
		$this->contents = null;

		$rc = parent::getInnerHTML();

		$this->container->contextPop();
		$this->contextPop();

		return $rc;
	}
}

class Wd2CElement extends WdElement
{
	const T_NO_WRAPPERS = '#2c-no-wrappers';

	public function __construct($type, $tags=array())
	{
		#
		# we merge the provided container tags with the default table tags for the table element
		#

		if ($type == 'table')
		{
			$tags += array
			(
				'cellpadding' => 5,
				'cellspacing' => 0,
				'summary' => ''
			);
		}

		parent::__construct($type, $tags);
	}

	protected function getInnerHTML()
	{
		#
		# create the inner HTML of our container
		#

		$rc = null;

		$is_table = ($this->tagName == 'table');
		$has_wrappers = !$this->get(self::T_NO_WRAPPERS);

		foreach ($this->get_ordered_children() as $child)
		{
			#
			# we skip empty children
			#

			if (!$child)
			{
				continue;
			}

			#
			# create child's form label
			#

			$label = null;

			if (is_object($child))
			{
				$child_id = $child->get('id');

				if (!$child_id)
				{
					$child_id = WdForm::getAutoElementId();

					$child->set('id', $child_id);
				}

				$text = $child->get(WdForm::T_LABEL);

				if ($text)
				{
					$is_required = $child->get(self::T_REQUIRED);

					$label .= '<label';

					if ($is_required)
					{
						$label .= ' class="required mandatory"';
					}

					if ($child_id)
					{
						$label .= ' for="' . $child_id . '"';
					}

					$label .= '>';

					$label .= self::translate_label($text);

					if ($is_required)
					{
						$label .= '&nbsp;<sup>*</sup>';
					}

					$label .= '<span class="separator">&nbsp;:</span>';
					$label .= '</label>';

					$complement = $child->get(WdForm::T_LABEL_COMPLEMENT);

					if ($complement)
					{
						$label .= ' <small class="completion">';
						$label .= t($complement);
						$label .= '</small>';
					}
				}
			}

			if ($is_table)
			{
				$rc .= '<tr>';
				$rc .= $label ? ('<td class="label">' . $label) : '<td>&nbsp;';
				$rc .= '</td>';
			}
			else if ($label)
			{
				if ($has_wrappers)
				{
					$rc .= '<div class="form-label">';
					$rc .= $label;
					$rc .= '</div>';
				}
				else
				{
					$rc .= $label;
				}
			}

			#
			# element
			#

			if ($is_table)
			{
				$rc .= '<td>';
				$rc .= $child;
				$rc .= '</td>';
				$rc .= '</tr>';
			}
			else if ($child)
			{
				if ($has_wrappers && is_object($child))
				{
					$rc .= '<div class="form-element">';
					$rc .= $child;
					$rc .= '</div>';
				}
				else
				{
					$rc .= $child;
				}
			}
		}

//		$rc = PHP_EOL . '<!-- BEGIN::' . __CLASS__ . '::' . __FUNCTION__ . '-->' . $rc .= '<!-- END::' . __CLASS__ . '::' . __FUNCTION__ . ' -->';

		return $rc;
	}
}