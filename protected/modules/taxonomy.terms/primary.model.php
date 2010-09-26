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

	public function load_terms($vocabulary, $scope=null, $having_nodes=true)
	{
		$query = 'SELECT term.* FROM {self} term';

		$conditions = array();
		$conditions_args = array();

		if (is_numeric($vocabulary))
		{
			$conditions[] = 'vid = ?';
			$conditions_args[]= $vocabulary;
		}
		else
		{
			$query .= ' INNER JOIN {prefix}taxonomy_vocabulary USING(vid)
			INNER JOIN {prefix}taxonomy_vocabulary_scope USING(vid)';

			$conditions[] = '(vocabularyslug = ? OR vocabulary = ?)';
			$conditions_args[] = $vocabulary;
			$conditions_args[] = $vocabulary;

			$conditions[] = 'scope = ?';
			$conditions_args[] = $scope;
		}

		if ($having_nodes)
		{
			$conditions[] = '(SELECT nid FROM {self}_nodes WHERE vtid = term.vtid LIMIT 1) IS NOT NULL';
		}

		return $this->query
		(
			$query . ' WHERE ' . implode(' AND ', $conditions) . ' ORDER BY weight, term',
			$conditions_args
		)
		->fetchAll(PDO::FETCH_CLASS, 'taxonomy_terms_WdActiveRecord');
	}
}