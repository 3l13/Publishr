<?php

class site_pages_WdModel extends system_nodes_WdModel
{
	public function save(array $properties, $key=null, array $options=array())
	{
		/*
		if (isset($properties[Page::TITLE]) && empty($properties[Page::PATTERN]))
		{
			$properties[Page::PATTERN] = wd_normalize($properties[Page::TITLE]);
		}
		*/

		if ($key && isset($properties[Page::PARENTID]) && $key == $properties[Page::PARENTID])
		{
			throw new WdException('A page connot be its own parent');
		}

		return parent::save($properties, $key, $options);
	}
}