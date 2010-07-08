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
		return new feedback_hits_WdManager
		(
			$this
		);
	}
}