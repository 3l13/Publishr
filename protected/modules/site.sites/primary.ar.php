<?php

class site_sites_WdActiveRecord extends WdActiveRecord
{
	const BASE = '/protected/';

	public function __construct()
	{
		if (empty($this->model))
		{
			$this->model = 'default';
		}

		parent::__construct();
	}

	protected function __get_url()
	{
		$parts = explode('.', $_SERVER['HTTP_HOST']);

		/*
		if (count($parts) == 2)
		{
			array_unshift($parts, 'www');
		}
		*/

		if ($this->subdomain)
		{
			$parts[0] = $this->subdomain;
		}

		if ($this->domain)
		{
			$parts[1] = $this->domain;
		}

		if ($this->tld)
		{
			$parts[2] = $this->tld;
		}

		return 'http://' . implode('.', $parts) . $this->path;
	}

	/**
	 * Returns the available templates for the site
	 */

	protected function __get_templates()
	{
		$templates = array();
		$root = $_SERVER['DOCUMENT_ROOT'];

		$models = array($this->model, 'all');

		foreach ($models as $model)
		{
			$path = self::BASE . $model . '/templates';

			if (!is_dir($root . $path))
			{
				continue;
			}

			$dh = opendir($root . $path);

			if (!$dh)
			{
				WdDebug::trigger('Unable to open directory %path', array('%path' => $path));

				continue;
			}

			while (($file = readdir($dh)) !== false)
			{
				if ($file{0} == '.')
				{
					continue;
				}

			 	$pos = strrpos($file, '.');

			 	if (!$pos)
			 	{
			 		continue;
			 	}

				$templates[$file] = $file;
			}

			closedir($dh);
		}

		sort($templates);

		return $templates;
	}

	protected function __get_partial_templates()
	{
		$templates = array();
		$root = $_SERVER['DOCUMENT_ROOT'];

		$models = array($this->model, 'all');

		foreach ($models as $model)
		{
			$path = self::BASE . $model . '/templates/partials';

			if (!is_dir($root . $path))
			{
				continue;
			}

			$dh = opendir($root . $path);

			if (!$dh)
			{
				WdDebug::trigger('Unable to open directory %path', array('%path' => $path));

				continue;
			}

			while (($file = readdir($dh)) !== false)
			{
				if ($file{0} == '.')
				{
					continue;
				}

			 	$pos = strrpos($file, '.');

			 	if (!$pos)
			 	{
			 		continue;
			 	}

			 	$id = preg_replace('#\.(php|html)$#', '', $file);
				$templates[$id] = $root . $path . '/' . $file;
			}

			closedir($dh);
		}

		return $templates;
	}

	/**
	 * Resolve the location of a relative path according site inheritence.
	 *
	 * @param string $relative The path to the file to locate.
	 */

	public function resolve_path($relative)
	{
		$root = $_SERVER['DOCUMENT_ROOT'];

		$try = self::BASE . $this->model . '/' . $relative;

		if (file_exists($root . $try))
		{
			return $try;
		}

		$try = self::BASE . 'all/' . $relative;

		if (file_exists($root . $try))
		{
			return $try;
		}
	}
}