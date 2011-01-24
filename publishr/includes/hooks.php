<?php

class publisher_WdHooks
{
	/**
	 * This callback is used to delete all the locks set by the user while editing entries.
	 *
	 * @param WdEvent $event
	 */

	static public function before_operation_disconnect(WdEvent $event)
	{
		global $core;

		$uid = $core->user_id;

		if (!$uid)
		{
			return;
		}

		try
		{
			$registry = $core->registry;

			$names = $registry->select('name')
			->where('name LIKE "admin.locks.%.uid" AND value = ?', $uid)
			->all(PDO::FETCH_COLUMN);

			if ($names)
			{
				$in = array();

				foreach ($names as $name)
				{
					$in[] = $name;
					$in[] = substr($name, 0, -3) . 'until';
				}

				$registry->where(array('name' => $in))->delete();
			}
		}
		catch (WdException $e) {  };
	}
}