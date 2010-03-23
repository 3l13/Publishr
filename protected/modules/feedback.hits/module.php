<?php

class feedback_hits_WdModule extends WdPModule
{
	const OPERATION_HIT = 'hit';

	protected function validate_operation_hit(WdOperation $operation)
	{
		// TODO: should test for uniqid

		return true;
	}

	protected function operation_hit(WdOperation $operation)
	{
		$params = &$operation->params;

		if (empty($params['uniqid']) || empty($params['nid']))
		{
			wd_log_error('Missing node id');

			return false;
		}

		$nid = $params['nid'];
		$now = date('Y-m-d H:i:s');

		$this->model()->execute
		(
			'INSERT {self} (`nid`, `hits`, `first`, `last`) VALUES (?, 1, ?, ?)
			ON DUPLICATE KEY UPDATE `hits` = `hits` + 1, `last` = ?', array
			(
				$nid, $now, $now,
				$now
			)
		);

		return true;
	}

	protected function block_manage()
	{
		$resume = new WdResume
		(
			$this, $this->model(), array
			(
				WdResume::T_COLUMNS => array
				(
					array
					(
						WdResume::COLUMN_LABEL => 'Name',
						WdResume::COLUMN_HOOK => array(__CLASS__, 'name_callback')
					),

					'hits' => array
					(
						WdResume::COLUMN_LABEL => 'Count',
						WdResume::COLUMN_CLASS => 'align-right'
					),

					'first' => array
					(
						WdResume::COLUMN_LABEL => 'First',
						WdResume::COLUMN_CLASS => 'date'
					),

					'last' => array
					(
						WdResume::COLUMN_LABEL => 'Last',
						WdResume::COLUMN_CLASS => 'date',
						WdResume::COLUMN_SORT => WdResume::ORDER_DESC,
					)
				)/*,

				WdResume::T_KEY => 'nid'*/
			)
		);

		return $resume;
	}

	static public function name_callback($entry, $tag, $resume)
	{
		global $core;

		$node = $core->getModule('system.nodes')->model()->load($entry->nid);

		$name = $node->title;

		if (!$name)
		{
			$name = '<em>' . $entry->resource . '</em>';
		}

		return wd_entities($name);
	}
}