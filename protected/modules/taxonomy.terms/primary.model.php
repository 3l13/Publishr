<?php

class taxonomy_terms_WdModel extends WdModel
{
	public function save(array $properties, $key=null, array $options=array())
	{
		if (isset($properties[taxonomy_terms_WdActiveRecord::TERM]) && empty($properties[taxonomy_terms_WdActiveRecord::TERMSLUG]))
		{
			$properties[taxonomy_terms_WdActiveRecord::TERMSLUG] = wd_normalize($properties[taxonomy_terms_WdActiveRecord::TERM]);
		}
		else if (isset($properties[taxonomy_terms_WdActiveRecord::TERMSLUG]))
		{
			$properties[taxonomy_terms_WdActiveRecord::TERMSLUG] = preg_replace('#[^a-zA-Z0-9\-]+#', '', $properties[taxonomy_terms_WdActiveRecord::TERMSLUG]);
		}

		return parent::save($properties, $key, $options);
	}
}