<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class organize_lists__save_WdOperation extends system_nodes__save_WdOperation
{
	protected function process()
	{
		$rc = parent::process();

		try
		{
			$listid = $rc['key'];
			$model = $this->module->model('nodes');
			$model->where('listid = ?', $listid)->delete();

			$params = $this->params;

			if (isset($params['nodes']))
			{
				$nodes = $params['nodes'];
				$labels = $params['labels'];

				$weight = 0;

				foreach ($nodes as $i => $nodeid)
				{
					$model->insert
					(
						array
						(
							'listid' => $listid,
							'nodeid' => $nodeid,
							'weight' => $weight++,
							'label' => $labels[$i]
						)
					);
				}
			}
		}
		catch (Exception $e)
		{
			wd_log_error($e->getMessage());
		}

		return $rc;
	}
}