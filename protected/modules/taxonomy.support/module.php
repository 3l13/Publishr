<?php

class taxonomy_support_WdModule extends WdPModule
{
	public function run()
	{
		global $core;

		try
		{
			$this->vocabulary = $core->getModule('taxonomy.vocabulary');
			$this->terms = $core->getModule('taxonomy.terms');
		}
		catch (Exception $e)
		{
			wd_log_error($e->getMessage());
		}
	}

	/**
	 * The getManageColumns method can be used by modules whishing to display
	 * vocabularies columns in their management table.
	 *
	 * @param $scope
	 * The scope for which to retreive columns.
	 *
	 * @return
	 * An array of columns for the WdResume class
	 */

	public function getManageColumns($scope)
	{
		$vocabularies = $this->vocabulary->model('scope')->loadAll
		(
			'where scope = ? and is_multiple = 0 order by weight, vocabulary', array
			(
				$scope
			)
		);

		$columns = array();

		foreach ($vocabularies as $vocabulary)
		{
			$columns[$vocabulary->vocabularyslug] = array
			(
				WdResume::COLUMN_LABEL => $vocabulary->vocabulary/*,
				WdResume::COLUMN_HOOK => array('WdResume', 'select_callback'),*/
			);
		}

		return $columns;
	}

	public function getSelectIdentifiers($scope)
	{
		$vocabularies = $this->vocabulary->model('scope')->loadAll
		(
			'where scope = ? order by weight, vocabulary', array
			(
				$scope
			)
		);

		$i = 0;
		$identifiers = array();

		foreach ($vocabularies as $vocabulary)
		{
			$i++;

			$vid = $vocabulary->vid;
			$identifier = $vocabulary->vocabularyslug;

			#
			# update identifiers
			#

			$definition = '(select ';

			if ($vocabulary->is_multiple)
			{
				$definition .= 'GROUP_CONCAT(term)';
			}
			else
			{
				$definition .= 'term';
			}

			$definition .= ' from {prefix}taxonomy_terms_nodes as s' . $i . 't1 inner join `{prefix}taxonomy_terms` as s' . $i . 't2 on (s' . $i . 't1.vtid = s' . $i . 't2.vtid and s' . $i .'t2.vid = ' . $vid . ')
			where s' . $i . 't1.nid = t1.nid)';

			$identifiers[$identifier] = $definition;
		}

		return $identifiers;
	}

	/**
	 * Complete a WdDatabaseView schema in order to incorporate the vocabulary
	 * of a given scope.
	 *
	 * @param $schema
	 * The schema that will be used by WdDatabaseView to create a view.
	 *
	 * @return array
	 * The modified schema
	 */

	public function completeViewSchema(array $schema, $scope)
	{
		$vocabularies = $this->vocabulary->model('scope')->loadAll
		(
			'where scope = ? order by weight, vocabulary', array
			(
				$scope
			)
		);

		$identifiers = &$schema['identifiers'];
		$fields = &$schema['fields'];

		$i = 0;

		foreach ($vocabularies as $vocabulary)
		{
			$i++;

			$vid = $vocabulary->vid;
			$identifier = $vocabulary->slug;

			#
			# update fields
			#

			$fields[$identifier] = array('type' => 'varchar');

			#
			# update identifiers
			#

			$definition = '(select ';

			if ($vocabulary->is_multiple)
			{
				$definition .= 'GROUP_CONCAT(term)';
			}
			else
			{
				$definition .= 'term';
			}

			$definition .= ' from {prefix}taxonomy_terms_nodes as s' . $i . 't1 inner join `{prefix}taxonomy_terms` as s' . $i . 't2 on (s' . $i . 't1.vtid = s' . $i . 't2.vtid and s' . $i .'t2.vid = ' . $vid . ')
			where s' . $i . 't1.nid = t1.nid) as `' . $identifier . '`';

			$identifiers[$identifier] = $definition;
		}

		return $schema;
	}
}