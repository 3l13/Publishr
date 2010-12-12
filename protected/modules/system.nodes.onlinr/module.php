<?php

class system_nodes_onlinr_WdModule extends WdPModule
{
	const REGISTRY_NEXTUPDATE = 'system_nodes_onlinr.next_update';

	public function run()
	{
		global $core, $registry;

		#
		# changes only happen at night, before the sun arise.
		#

		$hour = date('H');

		if ($hour > 6)
		{
			return;
		}

		#
		#
		#

		try
		{
			$nextUpdate = $registry[self::REGISTRY_NEXTUPDATE];
			$nextUpdateTime = strtotime($nextUpdate);

			if (strtotime(date('Y-m-d')) <= $nextUpdateTime)
			{
				return;
			}

			$registry[self::REGISTRY_NEXTUPDATE] = $nextUpdateTime ? date('Y-m-d', strtotime('+1 day', $nextUpdateTime)) : date('Y-m-d');
		}
		catch (Exception $e)
		{
			return;
		}

		#
		#
		#

		$model = $this->model();
		$delete = array();

		$nodesModel = $core->models['system.nodes'];

		#
		# between publicize and privatize
		#

		$entries = $model->select('nid')->where('(publicize + 0 != 0 AND publicize <= CURRENT_DATE) AND (privatize + 0 != 0 AND privatize >= CURRENT_DATE)')->all(PDO::FETCH_COLUMN);

		if ($entries)
		{
			$rc = $nodesModel->execute('UPDATE {self} SET is_online = 1 WHERE is_online = 0 AND nid IN(' . implode(',', $entries) . ')');
		}

		#
		# publicize only
		#

		$entries = $model->select('nid')->where('publicize <= CURRENT_DATE AND privatize + 0 = 0')->all(PDO::FETCH_COLUMN);

		if ($entries)
		{
			$delete += array_flip($entries);

			$rc = $nodesModel->execute('UPDATE {self} SET is_online = 1 WHERE is_online = 0 AND nid IN(' . implode(',', $entries) . ')');
		}

		#
		# privatize
		#

		$entries = $model->select('nid')->where('privatize <= CURRENT_DATE')->all(PDO::FETCH_COLUMN);

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

			$entries = $nodesModel->select('nid')->all(PDO::FETCH_COLUMN);

			if ($entries)
			{
				$deprecated = $model->select('nid')->where(array('!nid' => $entries))->all(PDO::FETCH_COLUMN);

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

	public function event_operation_save(WdEvent $event)
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
		//wd_log('event: \1', array($event));

		$nid = $event->key;

		$onlinr = $this->model[$nid];

		$event->tags[WdForm::T_VALUES]['system_nodes_onlinr'] = (array) $onlinr;

		//wd_log('onlinr: \1', array($onlinr));

		$event->tags = wd_array_merge_recursive
		(
			$event->tags, array
			(
				WdElement::T_CHILDREN => array
				(
					'system_nodes_onlinr' => new WdDateRangeElement
					(
						array
						(
							WdElement::T_GROUP => 'online',
							WdElement::T_WEIGHT => 100,

							WdElement::T_DESCRIPTION => "Les dates de <em>publication</em> et de
							<em>dépublication</em> permettent de définir un intervalle pendant
							laquelle l'entrée est visible. Si la date de publication est définie,
							l'entrée sera visible à partir de la date définie. Si la date de
							dépublication est définie, l'entrée ne sera plus visible à partir de
							la date définie.",

							WdDateRangeElement::T_START_TAGS => array
							(
								WdElement::T_LABEL => 'Publication',

								'name' => 'system_nodes_onlinr[publicize]'
							),

							WdDateRangeElement::T_FINISH_TAGS => array
							(
								WdElement::T_LABEL => 'Dépublication',

								'name' => 'system_nodes_onlinr[privatize]'
							)
						)
					)
				)
			)
		);
	}
}