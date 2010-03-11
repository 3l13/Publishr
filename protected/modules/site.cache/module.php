<?php

class site_cache_WdModule extends WdPModule
{
	const SIZE = 'size';
	const CLEAR_CONFIRM = 'clear-confirm';
	const OPERATION_CLEAR = 'clear';

	protected function block_manage()
	{
		return new site_cache_WdManager
		(
			$this, array
			(
				WdManager::T_COLUMNS_ORDER => array('id', 'uid', 'created')
			)
		);
	}












	public function clear()
	{
		try
		{
			$this->model()->truncate();
		}
		catch (Exception $e) {}
	}

	protected function validate_operation_clear(WdOperation $operation)
	{
		return !empty($operation->params[self::CLEAR_CONFIRM]);
	}

	protected function operation_clear(WdOperation $operation)
	{
		$this->clear();

		wd_log_done('The cache has been cleared');

		return true;
	}

	public function getCached($query, $constructor, $userdata=null)
	{
		if ($_POST)
		{
			return call_user_func($constructor ,$userdata);
		}

		//return call_user_func($constructor ,$userdata);

		global $user;

		if (0)
		{
			$file = sha1($query) . '-' . (int) $user->uid;

			return $this->cache()->load($file, $constructor, $userdata);
		}
		else
		{
			$id = sha1($query);
			$uid = (int) $user->uid;

			$entry = $this->model()->loadRange
			(
				0, 1, 'WHERE id = ? AND uid = ? AND created > ?', array
				(
					$id, $uid, date('Y-m-d H:i:s', strtotime('-1 week'))
				)
			)
			->fetchAndClose();

			if ($entry)
			{
				$contents = gzinflate($entry->contents);
			}
			else
			{
				$contents = call_user_func($constructor ,$userdata);

				$this->model()->save
				(
					array
					(
						'id' => $id,
						'contents' => gzdeflate($contents),
						'uid' => $uid
					),

					null,

					array
					(
						'on duplicate' => true
					)
				);
			}
		}

		return $contents;
	}
}