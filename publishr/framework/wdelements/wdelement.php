<?php

/**
 * This file is part of the WdElements framework
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.weirdog.com/wdelements/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.weirdog.com/wdelements/license/
 */

/*
 * http://dev.w3.org/html5/spec/Overview.html#embedding-custom-non-visible-data-with-the-data-attributes
 *
 */

class WdElement
{
	#
	# special elements
	#

	const E_CHECKBOX = '#checkbox';
	const E_CHECKBOX_GROUP = '#checkbox-group';
	const E_FILE = '#file';
	const E_HIDDEN = '#hidden';
	const E_PASSWORD = '#password';
	const E_RADIO = '#radio';
	const E_RADIO_GROUP = '#radio-group';
	const E_SUBMIT = '#submit';
	const E_TEXT = '#text';

	#
	# special tags
	#

	const T_CHILDREN = '#children';
	const T_DATASET = '#dataset';
	const T_DEFAULT = '#default';
	const T_DESCRIPTION = '#description';
	const T_FILE_WITH_LIMIT = '#element-file-with-limit';
	const T_FILE_WITH_REMINDER = '#element-file-with-reminder';
	const T_GROUP = '#group';
	const T_GROUPS = '#groups';
	const T_TYPE = '#type';

	/**
	 * The T_INNER_HTML tag is used to define the inner HTML of an element.
	 * If the value of the tag is NULL, the markup will be self-closing.
	 */

	const T_INNER_HTML = '#innerHTML';
	const T_LABEL = '#element-label';
	const T_LABEL_POSITION = '#element-label-position';
	const T_LABEL_SEPARATOR = '#element-label-separator';
	const T_LABEL_MISSING = '#element-label-missing'; // TODO: use this in validation
	const T_LEGEND = '#element-legend';
	const T_REQUIRED = '#required';
	const T_OPTIONS = '#element-options';
	const T_OPTIONS_DISABLED = '#element-options-disabled';

	/**
	 * Define a validator for the object. The validator is defined using an
	 * array made of a callback and a possible userdata array.
	 *
	 */

	const T_VALIDATOR = '#validator';
	const T_VALIDATOR_OPTIONS = '#validator-options';
	const T_VERIFY = '#element-verify';
	const T_WEIGHT = '#weight';

	static $inputs = array('button', 'form', 'input', 'option', 'select', 'textarea');
	static private $has_attribute_value = array('button', 'input', 'option');

	#
	#
	#

	public $type;
	public $tagName;
	public $children = array();
	public $dataset = array();

	protected $tags;
	protected $classes = array();
	protected $innerHTML = null;

	public function __construct($type, $tags=array())
	{
		if ($tags === null)
		{
			$tags = array();
		}

		$this->type = $type;
		$this->tags = $tags;

		#
		# prepare special elements
		#

		switch ((string) $type)
		{
			case self::E_CHECKBOX:
			case self::E_RADIO:
			case self::E_SUBMIT:
			case self::E_TEXT:
			case self::E_HIDDEN:
			case self::E_PASSWORD:
			{
				static $translate = array
				(
					self::E_CHECKBOX => array('input', 'checkbox'),
					self::E_RADIO => array('input', 'radio'),
					self::E_SUBMIT => array('button', 'submit'),
					self::E_TEXT => array('input', 'text'),
					self::E_HIDDEN => array('input', 'hidden'),
					self::E_PASSWORD => array('input', 'password')
				);

				$this->tagName = $translate[$type][0];
				$tags['type'] = $translate[$type][1];

				if ($type == self::E_SUBMIT)
				{
					$tags += array
					(
						self::T_INNER_HTML => t('Send')
					);
				}
			}
			break;

			case self::E_CHECKBOX_GROUP:
			{
				$this->tagName = 'div';
				$this->addClass('checkbox-group');
			}
			break;

			case self::E_FILE:
			{
				$this->tagName = 'input';

				$tags['type'] = 'file';

				$tags += array('size' => 40);
			}
			break;

			case self::E_RADIO_GROUP:
			{
				$this->tagName = 'div';
				$this->addClass('radio-group');
			}
			break;

			case 'textarea':
			{
				$this->tagName = 'textarea';
				$this->innerHTML = '';

				$tags += array('rows' => 10, 'cols' => 76);
			}
			break;

			default:
			{
				$this->tagName = $type;
			}
			break;
		}

		$this->set($tags);
	}

	/**
	 * The set() method is used to set, unset (nullify) and modify a tag
	 * of an element.
	 */

	public function set($name, $value=null)
	{
		if (is_array($name))
		{
			foreach ($name as $tag => $value)
			{
				$this->set($tag, $value);
			}
		}
		else
		{
			$this->tags[$name] = $value;
		}

		switch ($name)
		{
			case self::T_CHILDREN:
			{
				$this->children = array();
				$this->addChildren($value);
			}
			break;

			case self::T_DATASET:
			{
				$this->dataset = $value;
			}
			break;

			case self::T_INNER_HTML:
			{
				$this->innerHTML = $value;
			}
			break;
		}
	}

	/**
	 * The get() method is used to get to value of a tag. If the tag is not
	 * set, `null` is returned. You can provide a default value which is returned
	 * instead of `null` if the tag is not set.
	 */

	public function get($name, $default=null)
	{
		return isset($this->tags[$name]) ? $this->tags[$name] : $default;
	}

	/**
	 * Add a CSS class to the element.
	 * @param: $class
	 */

	public function addClass($class)
	{
		$this->classes[$class] = true;
	}

	/**
	 * Remove a CSS class from the element.
	 * @param $class
	 */

	public function removeClass($class)
	{
		unset($this->classes[$class]);
	}

	/**
	 * Collect the CSS classes of the element.
	 *
	 * The method returns a single string made of the classes joined together.
	 *
	 * @return string
	 */

	protected function compose_class()
	{
		$value = $this->get('class');
		$classes = $this->classes;

		if ($value)
		{
			$add = explode(' ', $value);
			$add = array_map('trim', $add);

			$classes = array_flip($add) + $classes;
		}

		return implode(' ', array_keys($classes));
	}

	protected function handleValue(&$tags)
	{
		$value = $this->get('value');

		if ($value === null)
		{
			$default = $this->get(self::T_DEFAULT);

			if ($default)
			{
				if ($this->type == self::E_CHECKBOX)
				{
					// TODO-20100108: we need to check this situation further more

					//$this->set('checked', $default);
				}
				else
				{
					$this->set('value', $default);
				}
			}
		}
	}

	/**
	 * Add a child to the element.
	 *
	 * @param $child The child element to add
	 *
	 * @param $name Optional, the name of the child element
	 */

	public function addChild($child, $name=null)
	{
		if ($name)
		{
			if (is_object($child))
			{
				$child->set('name', $name);
			}

			$this->children[$name] = $child;
		}
		else
		{
			$this->children[] = $child;
		}
	}

	public function addChildren(array $children)
	{
		foreach ($children as $name => $child)
		{
			$this->addChild($child, is_numeric($name) ? null : $name);
		}
	}

	/**
	 * Returns the children of the element.
	 *
	 * The children are ordered according to their weight.
	 */

	public function getChildren()
	{
		WdDebug::trigger('Use the get_ordered_children() method');

		return $this->get_ordered_children();
	}

	public function get_ordered_children()
	{
		if (!$this->children)
		{
			return array();
		}

		$by_weight = array();
		$with_relative_positions = array();

		foreach ($this->children as $name => $child)
		{
			$weight = is_object($child) ? $child->get(self::T_WEIGHT, 0) : 0;

			if (is_string($weight))
			{
				$with_relative_positions[] = $child;

				continue;
			}

			$by_weight[$weight][$name] = $child;
		}

		if (count($by_weight) == 1 && !$with_relative_positions)
		{
			return $this->children;
		}

		ksort($by_weight);

		$rc = array();

		foreach ($by_weight as $children)
		{
			$rc += $children;
		}

		#
		# now we deal with the relative positions
		#

		if ($with_relative_positions)
		{
			foreach ($with_relative_positions as $child)
			{
				list($target, $position) = explode(':', $child->get(self::T_WEIGHT)) + array(1 => 'after');

				$rc = wd_array_insert($rc, $target, $child, $child->get('name'), $position == 'after');
			}
		}

		return $rc;
	}

	public function get_named_elements()
	{
		$rc = array();

		$this->walk(array($this, 'get_named_elements_callback'), array(&$rc), 'name');

		return $rc;
	}

	private function get_named_elements_callback(WdElement $element, $userdata, $stop_value)
	{
		$userdata[0][$stop_value] = $element;
	}

	/*
	**

	CONTEXT

	**
	*/

	protected $pushed_tags = array();
	protected $pushed_children = array();
	protected $pushed_innerHTML = array();

	public function contextPush()
	{
		array_push($this->pushed_tags, $this->tags);
		array_push($this->pushed_children, $this->children);
		array_push($this->pushed_innerHTML, $this->innerHTML);
	}

	public function contextPop()
	{
		$this->tags = array_pop($this->pushed_tags);
		$this->children = array_pop($this->pushed_children);
		$this->innerHTML = array_pop($this->pushed_innerHTML);
	}

	/**
	 * Create the HTML representation of the element, taking care of its attributes and contents.
	 */

	protected function getMarkup()
	{
		#
		# In order to allow further customization, the contents of the element is created before
		# its markup.
		#

		try
		{
			$inner = $this->getInnerHTML();
		}
		catch (Exception $e)
		{
			$inner = $e->getMessage();
		}

		#
		#
		#

		$rc = '<' . $this->tagName;

		#
		# class
		#

		$class = $this->compose_class();

		if ($class)
		{
			$rc .= ' class="' . $class . '"';
		}

		#
		# attributes
		#

		foreach ($this->tags as $name => $value)
		{
			#
			# We discard false, null or custom tags. The 'class' tag is also discarted because it's
			# handled separately.
			#

			if ($value === false || $value === null || $name{0} == '#' || $name == 'class')
			{
				continue;
			}

			if ($name == 'value' && !in_array($this->tagName, self::$has_attribute_value))
			{
				continue;
			}

			#
			# We discard the `disabled`, `name` and `value` attributes for non input type elements
			#

			if (($name == 'disabled' || $name == 'name') && !in_array($this->tagName, self::$inputs))
			{
				continue;
			}

			#
			# attributes with the value TRUE are translated to XHTML standard
			# e.g. readonly="readonly"
			#

			if ($value === true)
			{
				$value = $name;
			}

			$rc .= ' ' . $name . '="' . (is_numeric($value) ? $value : wd_entities($value)) . '"';
		}

		foreach ($this->dataset as $name => $value)
		{
			if (is_array($value))
			{
				$value = json_encode($value);
			}

			if ($value === null)
			{
				continue;
			}

			$rc .= ' data-' . $name . '="' . (is_numeric($value) ? $value : wd_entities($value)) . '"';
		}

		#
		# if the inner HTML of the element is null, the element is self closing
		#

		if ($inner === null)
		{
			$rc .= ' />';
		}
		else
		{
			$rc .= '>' . $inner . '</' . $this->tagName . '>';
		}

		return $rc;
	}

	/**
	 * Get the inner HTML of the element.
	 *
	 * Remember that if the element has a null inner HTML it will be self closing.
	 *
	 * @return string the inner HTML of the element
	 */

	protected function getInnerHTML()
	{
		$rc = null;

		$children = $this->get_ordered_children();

		if ($children)
		{
			foreach ($children as $child)
			{
				$rc .= $child;
			}
		}
		else
		{
			$rc = $this->innerHTML;
		}

		return $rc;
	}

	/**
	 * Return the HTML representation of the object
	 * @return string The HTML representation of the object
	 */

	public function __toString()
	{
		$rc = '';

		$tags =& $this->tags;

		#
		# handle value for some selected 'types' and 'elements'
		#

		static $valued_elements = array
		(
			'input', 'select', 'button', 'textarea'
		);

		if (in_array($this->tagName, $valued_elements))
		{
			$this->handleValue($tags);
		}

		#
		#
		#

		switch ($this->type)
		{
			case self::E_CHECKBOX:
			{
				$this->contextPush();

				if ($this->get(self::T_DEFAULT) && $this->get('checked') === null)
				{
					$this->set('checked', true);
				}

				$rc = $this->getMarkup();

				$this->contextPop();
			}
			break;

			case self::E_CHECKBOX_GROUP:
			{
				$this->contextPush();

				$this->handleValue($tags);

				#
				# get the name and selected value for our children
				#

				$name = $this->get('name');
				$selected = $this->get('value', array());
				$disabled = $this->get('disabled', false);
				$readonly = $this->get('readonly', false);

				#
				# and remove them from our attribute list
				#

				$this->set
				(
					array
					(
						'name' => null,
						'value' => null,
						'disabled' => null,
						'readonly' => null
					)
				);

				#
				# this is the 'template' child
				#

				$child = new WdElement
				(
					'input', array
					(
						'type' => 'checkbox',
						'disabled' => $disabled,
						'readonly' => $readonly
					)
				);

				#
				# create the inner content of our element
				#

				$inner = null;
				$disableds = $this->get(self::T_OPTIONS_DISABLED);

				foreach ($tags[self::T_OPTIONS] as $option_name => $label)
				{
					$child->set
					(
						array
						(
							self::T_LABEL => $label,
							'name' => $name . '[' . $option_name . ']',
							'checked' => !empty($selected[$option_name]),
							'disabled' => !empty($disableds[$option_name])
						)
					);

					$inner .= $child;
				}

				$this->innerHTML .= $inner;

				#
				# make our element
				#

				$rc = $this->getMarkup();

				$this->contextPop();
			}
			break;

			case self::E_FILE:
			{
				$rc .= '<div class="wd-file">';

				#
				# the T_FILE_WITH_REMINDER tag can be used to add a disabled text input before
				# the file element. this text input is used to display the current value of the
				# file element.
				#

				$reminder = $this->get(self::T_FILE_WITH_REMINDER);

				if ($reminder === true)
				{
					$reminder = $this->get('value');
				}

				if ($reminder)
				{
					$rc .= '<div class="reminder">';

					$rc .= new WdElement
					(
						WdElement::E_TEXT, array
						(
							'value' => $reminder,
							'disabled' => true,
							'size' => $this->get('size', 40)
						)
					);

					$rc .= ' ';

					$rc .= new WdElement
					(
						'a', array
						(
							self::T_INNER_HTML => 'Télécharger',

							'href' => $reminder,
							'title' => $reminder,
							'target' => '_blank'
						)
					);

					$rc .= '</div>';
				}
				#
				#
				#

				$rc .= $this->getMarkup();

				#
				# the T_FILE_WITH_LIMIT tag can be used to add a little text after the element
				# reminding the maximum file size allowed for the upload
				#

				$limit = $this->get(self::T_FILE_WITH_LIMIT);

				if ($limit)
				{
					if ($limit === true)
					{
						$limit = ini_get('upload_max_filesize') * 1024;
					}

					$rc .= PHP_EOL;

					$rc .= '<div class="limit">';

					$limit = wd_format_size($limit * 1020);
					$rc .= t('The maximum file size must be less than :size.', array(':size' => $limit));

					/*
					if ($limit > 1024)
					{
						$rc .= t('The maximum file size must be less than :size Mb.', array(':size' => $limit / 1024));
					}
					else
					{
						$rc .= t('The maximum file size must be less than :size Kb.', array(':size' => $limit));
					}
					*/

					$rc .= '</div>';
				}

				$rc .= '</div>';
			}
			break;

			case self::E_RADIO_GROUP:
			{
				$this->contextPush();

				$this->handleValue($tags);

				#
				# get the name and selected value for our children
				#

				$name = $this->get('name');
				$selected = $this->get('value');
				$disabled = $this->get('disabled', false);
				$readonly = $this->get('readonly', false);

				#
				# and remove them from our attribute list
				#

				$this->set
				(
					array
					(
						'name' => null,
						'value' => null,
						'disabled' => null,
						'readonly' => null
					)
				);

				#
				# this is the 'template' child
				#

				$child = new WdElement
				(
					'input', array
					(
						'type' => 'radio',
						'name' => $name,
						'disabled' => $disabled,
						'readonly' => $readonly
					)
				);

				#
				# --create the inner content of our element
				#
				# add our options as children
				#

				$disableds = $this->get(self::T_OPTIONS_DISABLED);

				foreach ($tags[self::T_OPTIONS] as $value => $label)
				{
					$child->set
					(
						array
						(
							self::T_LABEL => $label,
							'value' => $value,
							'checked' => (string) $value === (string) $selected,
							'disabled' => !empty($disableds[$value])
						)
					);

					$this->children[] = clone $child;
				}

				#
				# make our element
				#

				$rc = $this->getMarkup();

				$this->contextPop();
			}
			break;

			case self::E_PASSWORD:
			{
				$this->contextPush();

				#
				# for security reason, the value of the password is emptied
				#

				$this->set('value', '');

				$rc = $this->getMarkup();

				// FIXME: That's so lame !

				if (isset($tags[self::T_VERIFY]))
				{

					$name = $this->get('name');
					$label = t('confirm'); // FIXME: we shouldn't use t() in here !!

					if ($this->get(self::T_REQUIRED))
					{
						$label = '<sup>*</sup> ' . $label;
					}

					$this->set('name', $name . '-confirm');

					$rc .= ' <label>';
					$rc .= $label;
					$rc .= '&nbsp;:';
					$rc .= ' ' . $this->getMarkup();
					$rc .= '</label>';
				}

				$this->contextPop();
			}
			break;

			case 'select':
			{
				$this->contextPush();

				#
				# get the name and selected value for our children
				#

				$selected = $this->get('value');

				#
				# this is the 'template' child
				#

				$child = new WdElement('option');

				#
				# create the inner content of our element
				#

				$inner = '';

				$options = $this->get(self::T_OPTIONS, array());
				$disabled = $this->get(self::T_OPTIONS_DISABLED);

				foreach ($options as $value => $label)
				{
					#
					# value is casted to a string so that we can handle null value and compare '0' with 0
					#

					$child->set
					(
						array
						(
							'value' => $value,
							'selected' => (string) $value === (string) $selected,
							'disabled' => !empty($disabled[$value])
						)
					);

					$child->innerHTML = $label ? wd_entities($label) : '&nbsp;';

					$inner .= $child;
				}

				$this->innerHTML .= $inner;

				#
				# make our element
				#

				$rc = $this->getMarkup();

				$this->contextPop();
			}
			break;

			case 'textarea':
			{
				$this->contextPush();

				$this->innerHTML = wd_entities($this->get('value', ''));

				$this->set('value', null);

				$rc = $this->getMarkup();

				$this->contextPop();
			}
			break;

			default:
			{
				$rc = $this->getMarkup();
			}
			break;
		}

		#
		# add label
		#

		$label = $this->get(self::T_LABEL);

		if ($label)
		{
			$is_required = $this->get(self::T_REQUIRED);
			$position = $this->get(self::T_LABEL_POSITION, 'after');

			if ($position == 'left')
			{
				WdDebug::trigger('Position <em>left</em> is invalid, use <em>before</em> instead');

				$position = 'before';
			}

			if ($position == 'right')
			{
				WdDebug::trigger('Position <em>right</em> is invalid, use <em>after</em> instead');

				$position = 'after';
			}

			if ($position == 'top')
			{
				WdDebug::trigger('Position <em>top</em> is invalid, use <em>above</em> instead');

				$position = 'above';
			}

			$label = t($label);

			if ($is_required)
			{
				$label = $label . '<sup>&nbsp;*</sup>';
			}

			if ($position != 'after')
			{
				$label .= '<span class="separator">&nbsp;:</span>';
			}

			//if ($position != 'above')
			{
				$label = '<span class="label">' . $label . '</span>';
			}

			// TODO-20100714: T_LABEL_SEPARATOR is not used now, look out for consequences

			$separator = $this->get(self::T_LABEL_SEPARATOR, true);

			//$contents = '<span class="element">' . $rc . '</span>';
			$contents = $rc;

			switch ($position)
			{
				case 'above':
				{
					$rc  = '<div class="element-label">';
					$rc .= '<label' . ($is_required ? ' class="required mandatory"' : '') . '>' . $label . '</label>';
					$rc .= '</div>';

					$rc .= '<div class="element-element">';
					$rc .= $contents;
					$rc .= '</div>';
				}
				break;

				case 'before':
				{
					$rc  = $is_required ? '<label class="required mandatory">' : '<label>';
					$rc .= $label;

					/*
					if ($separator)
					{
						$rc .= '&nbsp;:';
					}
					*/

					$rc .= ' ' . $contents;
					$rc .= '</label>';
				}
				break;

				case 'after':
				default:
				{
					$rc  = $is_required ? '<label class="required mandatory">' : '<label>';
					$rc .= $contents . ' ' . $label;
					$rc .= '</label>';
				}
				break;
			}
		}

		#
		# add legend
		#

		$legend = $this->get(self::T_LEGEND);

		if ($legend)
		{
			$contents = $rc;

			$rc  = '<fieldset>';
			$rc .= '<legend>' . $legend . '</legend>';
			$rc .= $contents;
			$rc .= '</fieldset>';
		}

		#
		# add description
		#

		$description = $this->get(self::T_DESCRIPTION);

		if ($description)
		{
			$rc .= '<div class="element-description">';
			$rc .= $description;
			$rc .= '</div>';
		}

		#
		# done !
		#

		return $rc;
	}

	/**
	 * Validate the value of the object.
	 *
	 * This function uses the validator defined using the T_VALIDATOR tag to validate
	 * its value.
	 *
	 * @param $value
	 * @return boolean Return TRUE is the validation succeed.
	 */

	public function validate($value)
	{
		$validator = $this->get(self::T_VALIDATOR);
		$options = $this->get(self::T_VALIDATOR_OPTIONS);

		if ($validator)
		{
			list($callback, $params) = $validator + array(1 => array());

			return call_user_func($callback, $this, $value, $params);
		}

		#
		# default validator
		#

		if (!$options)
		{
			return true;
		}

		switch ($this->type)
		{
			case self::E_CHECKBOX_GROUP:
			{
				if (isset($options['max-checked']))
				{
					$limit = $options['max-checked'];

					if (count($value) > $limit)
					{
						$this->form->log
						(
							$this->name, 'Le nombre de choix possible pour le champ %name est limité à :limit', array
							(
								'%name' => WdForm::selectElementLabel($this),
								':limit' => $limit
							)
						);

						return false;
					}
				}
			}
			break;
		}

		return true;
	}

	/**
	 * Walk thought the elements of the element's tree applying a function to each one of them.
	 *
	 * The callback is called with the element, the userdata and the stop value for the
	 * element (which is null if @stop is null).
	 *
	 * If @stop is defined, only element having a non-null @stop attribute are called.
	 *
	 * @param $callback
	 * @param $userdata
	 * @param $stop
	 */

	public function walk($callback, $userdata, $stop=null)
	{
		#
		# if the element has children, we walk them first, the walktrought is bubbling.
		#

		foreach ($this->children as $child)
		{
			#
			# Only instances of the WdElement class are walkable.
			#

			if (!($child instanceof WdElement))
			{
				continue;
			}

			$child->walk($callback, $userdata, $stop);
		}

		#
		# the callback is not called for the element, if its 'stop' attribute is null
		#

		$stop_value = null;

		if ($stop)
		{
			$stop_value = $this->get($stop);

			if ($stop_value === null)
			{
				return;
			}
		}

		call_user_func($callback, $this, $userdata, $stop_value);
	}

	static protected function translate_label($label)
	{
		if (!is_array($label))
		{
			return t($label, array(), array('scope' => array('form', 'label')));
		}

		return t($label[0], array(), array('scope' => $label[1]));
	}
}