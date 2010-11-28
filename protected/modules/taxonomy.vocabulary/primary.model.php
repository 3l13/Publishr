<?php

class taxonomy_vocabulary_WdModel extends WdModel
{
	/*DIRTY:SCOPE
	static private function scope()
	{
		static $scope;

		if (!$scope)
		{
			global $core;

			$scope = $core->getModule('taxonomy.vocabulary')->model('scope');
		}

		return $scope;
	}
	*/

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

		/*DIRTY:SCOPE
		if (isset($properties['scopes']))
		{
			$this->clearScopes($key);

			#
			# update scope
			#

			$scope_model = self::scope();

			foreach ($properties['scopes'] as $scope_properties)
			{
				if (empty($scope_properties['scope']))
				{
					continue;
				}

				$scope_properties += array
				(
					'is_mandatory' => false
				);

				$scope_properties['is_mandatory'] = filter_var($scope_properties['is_mandatory'], FILTER_VALIDATE_BOOLEAN);

				$scope_model->insert
				(
					array
					(
						taxonomy_vocabulary_WdActiveRecord::VID => $key
					)

					+ $scope_properties
				);
			}
		}
		*/

		return $key;
	}

	public function delete($id)
	{
		$rc = parent::delete($id);

		if ($rc)
		{
			/*DIRTY:SCOPE
			$this->clearScopes($id);
			*/
			$this->clearTerms($id);
		}

		return $rc;
	}

	/*DIRTY:SCOPE
	protected function clearScopes($vid)
	{
		self::scope()->execute('DELETE FROM {self} WHERE vid = ?', array($vid));
	}
	*/

	protected function clearTerms($vid)
	{
		// TODO: use model delete() method instead, maybe put an event on 'taxonomy.vocabulary.delete'

		global $core;

		$terms = $core->getModule('taxonomy.terms');

		#
		# delete nodes
		#

		$model = $terms->model();

		$model->execute('DELETE FROM {self}_nodes WHERE (SELECT vid FROM {self} WHERE {self}_nodes.vtid = {self}.vtid) = ?', array($vid));
		$model->execute('DELETE FROM {self} WHERE vid = ?', array($vid));
	}
}