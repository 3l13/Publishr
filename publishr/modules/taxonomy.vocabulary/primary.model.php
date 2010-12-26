<?php

class taxonomy_vocabulary_WdModel extends WdModel
{
	public function save(array $properties, $key=null, array $options=array())
	{
		if (isset($properties[taxonomy_vocabulary_WdActiveRecord::VOCABULARY]) && empty($properties[taxonomy_vocabulary_WdActiveRecord::VOCABULARYSLUG]))
		{
			$properties[taxonomy_vocabulary_WdActiveRecord::VOCABULARYSLUG] = wd_normalize($properties[taxonomy_vocabulary_WdActiveRecord::VOCABULARY]);
		}
		else if (isset($properties[taxonomy_vocabulary_WdActiveRecord::VOCABULARYSLUG]))
		{
			$properties[taxonomy_vocabulary_WdActiveRecord::VOCABULARYSLUG] = preg_replace('#[^a-zA-Z0-9\-]+#', '', $properties[taxonomy_vocabulary_WdActiveRecord::VOCABULARYSLUG]);
		}

		$key = parent::save($properties, $key, $options);

		if (!$key)
		{
			return $key;
		}

		$scope = array();

		if (isset($properties[taxonomy_vocabulary_WdActiveRecord::SCOPE]))
		{
			$insert = $this->prepare('INSERT IGNORE INTO {self}_scopes (vid, constructor) VALUES(?, ?)');

			foreach ($properties[taxonomy_vocabulary_WdActiveRecord::SCOPE] as $constructor => $ok)
			{
				$ok = filter_var($ok, FILTER_VALIDATE_BOOLEAN);

				if (!$ok)
				{
					continue;
				}

				$scope[] = $constructor;
				$insert->execute(array($key, $constructor));
			}
		}

		if ($scope)
		{
			$scope = array_map(array($this, 'quote'), $scope);

			$this->execute('DELETE FROM {self}_scopes WHERE vid = ? AND constructor NOT IN(' . implode(',', $scope) . ')', array($key));
		}

		return $key;
	}

	public function delete($key)
	{
		$rc = parent::delete($key);

		if ($rc)
		{
			$this->execute('DELETE FROM {self}_scopes WHERE vid = ?', array($key));
			$this->clearTerms($key);
		}

		return $rc;
	}

	protected function clearTerms($vid)
	{
		// TODO: use model delete() method instead, maybe put an event on 'taxonomy.vocabulary.delete'

		global $core;

		$model = $core->models['taxonomy.terms'];
		$model->execute('DELETE FROM {self}_nodes WHERE (SELECT vid FROM {self} WHERE {self}_nodes.vtid = {self}.vtid) = ?', array($vid));
		$model->execute('DELETE FROM {self} WHERE vid = ?', array($vid));
	}
}