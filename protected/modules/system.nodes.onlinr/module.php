<?php

class system_nodes_onlinr_WdModule extends WdPModule
{
	const REGISTRY_NEXTUPDATE = 'systemNodesOnlinr.nextUpdate';

	public function run()
	{
		global $core, $registry;

		if (!$this->model()->isInstalled())
		{
			return;
		}

		#
		# because we cannot use a return in a try/catch block
		#

		$return = false;

		try
		{
			$nextUpdate = $registry->get(self::REGISTRY_NEXTUPDATE);
			$nextUpdateTime = strtotime($nextUpdate);

			if (strtotime(date('Y-m-d')) <= $nextUpdateTime)
			{
				$return = true;
			}
			else
			{
				$registry->set(self::REGISTRY_NEXTUPDATE, $nextUpdateTime ? date('Y-m-d', strtotime('+1 day', $nextUpdateTime)) : date('Y-m-d'));
			}
		}
		catch (Exception $e) {}

		if ($return)
		{
			return;
		}

		#
		#
		#

		$model = $this->model();
		$delete = array();

		$nodesModel = $core->getModule('system.nodes')->model();

		#
		# between publicize and privatize
		#

		$entries = $model->select
		(
			'nid', 'WHERE (publicize + 0 != 0 AND publicize <= CURRENT_DATE) AND (privatize + 0 != 0 AND privatize >= CURRENT_DATE)'
		)
		->fetchAll(PDO::FETCH_COLUMN);

		if ($entries)
		{
			$rc = $nodesModel->execute('UPDATE {self} SET is_online = 1 WHERE is_online = 0 AND nid IN(' . implode(',', $entries) . ')');
		}

		#
		# publicize only
		#

		$entries = $model->select
		(
			'nid', 'WHERE publicize <= CURRENT_DATE AND privatize + 0 = 0'
		)
		->fetchAll(PDO::FETCH_COLUMN);

		if ($entries)
		{
			$delete += array_flip($entries);

			$rc = $nodesModel->execute('UPDATE {self} SET is_online = 1 WHERE is_online = 0 AND nid IN(' . implode(',', $entries) . ')');
		}

		#
		# privatize
		#

		$entries = $model->select
		(
			'nid', 'WHERE privatize <= CURRENT_DATE'
		)
		->fetchAll(PDO::FETCH_COLUMN);

		if ($entries)
		{
			$delete += array_flip($entries);

			$rc = $nodesModel->execute('UPDATE {self} SET is_online = 0 WHERE is_online = 1 AND nid IN(' . implode(',', $entries) . ')');
		}

		if (0)
		{
			#
			# clean
			#

			$entries = $nodesModel->select('nid')->fetchAll(PDO::FETCH_COLUMN);

			if ($entries)
			{
				$deprecated = $model->select('nid', 'WHERE nid NOT IN(' . implode(',', $entries) . ')')->fetchAll(PDO::FETCH_COLUMN);

				if ($deprecated)
				{
					$delete += array_flip($deprecated);
				}
			}
		}

		if ($delete)
		{
			$delete = array_keys($delete);

			$model->execute('DELETE FROM {self} WHERE nid IN(' . implode(',', $delete) . ')');
		}
	}

	public function event_system_nodes_save(WdEvent $event)
	{
		$params = &$event->operation->params;

		if (empty($params['system_nodes_onlinr']))
		{
			return;
		}

		$onlinr = $params['system_nodes_onlinr'];

		$nid = $event->rc['key'];

		if (!$onlinr['publicize'] && !$onlinr['privatize'])
		{
			$this->model()->delete($nid);
		}
		else
		{
			$this->model()->insert
			(
				array
				(
					'nid' => $nid,
					'publicize' => $onlinr['publicize'],
					'privatize' => $onlinr['privatize']
				),

				array
				(
					'on duplicate' => true
				)
			);
		}
	}

	/**
	 * This event callback adds a new element to the "online" group defined by the system.nodes
	 * module.
	 *
	 * @param WdEvent $event
	 *
	 */

	public function event_alter_block_edit(WdEvent $event)
	{
		if (!($event->module instanceof system_nodes_WdModule))
		{
			return;
		}

		//wd_log('event: \1', array($event));

		$nid = $event->key;

		$onlinr = $this->model()->load($nid);

		$event->tags[WdForm::T_VALUES]['system_nodes_onlinr'] = (array) $onlinr;

		//wd_log('onlinr: \1', array($onlinr));

		$event->tags = wd_array_merge_recursive
		(
			$event->tags, array
			(
				WdElement::T_CHILDREN => array
				(
					'system_nodes_onlinr' => new WdOnlineRangeElement
					(
						'div', array
						(
							WdForm::T_LABEL => 'Contrôle automatique de la visibilité',
							WdElement::T_GROUP => 'online',
							WdElement::T_WEIGHT => 100
						)
					)
				)
			)
		);
	}
}