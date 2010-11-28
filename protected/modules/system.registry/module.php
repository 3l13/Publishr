<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class system_registry_WdModule extends WdModule implements ArrayAccess
{
	public function run()
	{
		global $registry;

		$registry = $this;

		return parent::run();
	}

	protected $cached_values = array();

	public function get($name, $default=null)
	{
		if ($default || !array_key_exists($name, $this->cached_values))
		{
			$length = strlen($name);

			if ($name{$length - 1} == '.')
			{
				$values = $this->model()->select
				(
					'*', 'where `value` is not null and {primary} like ?', array
					(
						$name . '%'
					)
				);

				$rc = $default ? $default : array();

				foreach ($values as $value)
				{
					$name = $value['name'];
					$value = $value['value'];

					$name = substr($name, $length);

					//wd_log('short: "\1"', array($name));

					$name = '[\'' . str_replace('.', "']['", $name) . '\']';

					// FIXME: an eval really ?

					eval('$rc' . $name . ' = $value;');
				}

				// TODO: handle default values

				//echo t(__CLASS__ . '::' . __FUNCTION__ . ':> rc: \1', array($rc));

				$this->cached_values[$name] = $rc;
			}
			else
			{
				$value = $this->model()->select
				(
					'*', 'where `value` is not null and {primary} = ?', array
					(
						$name
					)
				)
				->fetchAndClose();

				$rc = $value['value'];

				if ($rc === null)
				{
					$rc = $default;
				}

				$this->cached_values[$name] = $rc;
			}
		}

		return $this->cached_values[$name];
	}

	/**
	 *
	 * Set a value, or a tree of values, in the registry.
	 *
	 * One can delete key (and all its sub keys), by setting it to null.
	 *
	 * @param unknown_type $name
	 * @param unknown_type $value
	 */

	public function set($name, $value)
	{
		$this->cached_values = array();

		$name = (string) $name;

		if (is_array($value))
		{
			$values = self::flatten($value, $name);

//			wd_log('should delete %name[%sub] to save !values from !value', array('%name' => $name . '.', '!values' => $values, '!value' => $value, '%sub' => implode(', ', array_keys($value))));

			foreach ($values as $name => $value)
			{
				$this->set($name, $value);
			}

			return;
		}

		if ($value === null)
		{
			//wd_log('delete %name because is has been set to null', array('%name' => $name));

			$this->model()->execute
			(
				'DELETE FROM {self} WHERE {primary} = ? OR {primary} LIKE ?', array
				(
					$name,
					$name . '.%'
				)
			);
		}
		else
		{
			//wd_log('set <code>:name := !value</code>', array(':name' => $name, '!value' => $value));

			$this->model()->insert
			(
				array
				(
					'name' => $name,
					'value' => $value
				),

				array
				(
					'on duplicate' => true
				)
			);
		}
	}

	static protected function flatten($values, $prefix)
	{
		if ($prefix)
		{
			$prefix .= '.';
		}

		$flatten = array();

		foreach ($values as $key => $value)
		{
			if (is_array($value))
			{
				$flatten = array_merge
				(
					$flatten, self::flatten($value, $prefix . $key)
				);

				continue;
			}

			$flatten[$prefix . $key] = $value;
		}

		return $flatten;
	}

	public function offsetSet($offset, $value)
	{
        $this->set($offset, $value);
    }

    public function offsetExists($offset)
    {
        return $this->get($offset) !== null;
    }

    public function offsetUnset($offset)
    {
        $this->set($offset, null);
    }

    public function offsetGet($offset)
    {
    	return $this->get($offset);
    }
}