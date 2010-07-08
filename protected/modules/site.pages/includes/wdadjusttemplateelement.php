<?php

class WdAdjustTemplateElement extends WdElement
{
	protected $path = '/protected/templates/';

	public function __construct($tags, $dummy=null)
	{
		parent::__construct('select', $tags);
	}

	public function __toString()
	{
		$path = $this->path;

		if (!is_dir($_SERVER['DOCUMENT_ROOT'] . $path))
		{
			return t('The directory %path does not exists !', array('%path' => $path));
		}

		$list = $this->get_list();

		$options = array();

		foreach ($list as $id)
		{
			$options[$id] = $id;
		}

		$this->set(self::T_OPTIONS, array(null => '<auto>') + $options);

		return parent::__toString();
	}

	protected function get_list()
	{
		$path = $this->path;
		$dh = opendir($_SERVER['DOCUMENT_ROOT'] . $path);

		if (!$dh)
		{
			WdDebug::trigger('Unable to open directory %path', array('%path' => $path));

			return false;
		}

		$files = array();

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

			$files[] = $file;
		}

		//wd_log('files: \1', array($files));

		return $files;
	}
}