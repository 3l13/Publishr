<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class feedback_hits_WdModule extends WdPModule
{
	const OPERATION_HIT = 'hit';

	protected function validate_operation_hit(WdOperation $operation)
	{
		if (!$operation->key)
		{
			wd_log_error('Missing node id');

			return false;
		}

		// TODO: should test for uniqid

		return true;
	}

	protected function operation_hit(WdOperation $operation)
	{
		$nid = $operation->key;

		$this->model()->execute
		(
			'INSERT {self} (`nid`, `hits`, `first`, `last`) VALUES (?, 1, NOW(), NOW())
			ON DUPLICATE KEY UPDATE `hits` = `hits` + 1, `last` = NOW()', array
			(
				$nid
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