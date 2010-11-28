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
		global $core, $registry;

		$uid = $core->user_id;

		if (!$uid)
		{
			return;
		}

		try
		{
			$names = $registry->model->select
			(
				'name', 'WHERE name LIKE "admin.locks.%.uid" AND value = ?', array
				(
					$uid
				)
			)
			->fetchAll(PDO::FETCH_COLUMN);

			if ($names)
			{
				$in = array();

				foreach ($names as $name)
				{
					$in[] = '"' . $name . '"';
					$in[] = '"' . substr($name, 0, -3) . 'until' . '"';
				}

				$in = implode(', ', $in);

				$registry->model->execute('DELETE FROM {self} WHERE name IN(' . $in . ')');
			}
		}
		catch (WdException $e) {  };
	}
}