<?php

class system_registry_WdModule extends WdPModule
{
	public function run()
	{
		global $registry;

		$registry = $this;

		if (0)
		{
//			$this->model()->execute('DELETE FROM {self} WHERE name LIKE "thumbnailer.nextCleanup"');

			$entries = $this->model()->loadAll('ORDER BY name')->fetchPairs();

			wd_log('registry\1', array($entries));
		}

		return true;
	}

	protected function block_manage()
	{
		return new system_registry_WdManager
		(
			$this, array
			(
				/*
				WdManager::T_COLUMNS_ORDER => array
				(
					'title', 'surface', 'uid', 'modified', 'is_online'
				)
				*/
			)
		);
	}

	public function get($name, $default=null)
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

			$rc = array();

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

			return $rc;
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

			return $rc;
		}
	}

	public function set($name, $value/*, $encoding=self::ENCODING_NONE*/)
	{
		$name = (string) $name;

		if (is_array($value))
		{
			$values = self::flatten($value, $name);

//			wd_log('should delete %name[%sub] to save !values from !value', array('%name' => $name . '.', '!values' => $values, '!value' => $value, '%sub' => implode(', ', array_keys($value))));

			foreach ($values as $name => $value)
			{
				$this->set($name, $value/*, $encoding*/);
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

	static private function flatten($values, $prefix)
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

	static public function toCamelCase($str)
	{
		$parts = explode('.', $str);

		$first = array_shift($parts);

		$parts = array_map('ucfirst', $parts);

		array_unshift($parts, $first);

		return implode('', $parts);
	}
}