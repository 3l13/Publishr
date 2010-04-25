<?php

class system_nodes_attachments_WdEvents
{
	static public function operation_save(WdEvent $event)
	{
		global $core;

		$params = &$event->operation->params;

		if (empty($params['system_nodes_attachments']))
		{
			return;
		}

		try
		{
			$model = $core->getModule('system.nodes.attachments')->model('nodes');
		}
		catch (WdException $e)
		{
			return;
		}

		$nid = $event->rc['key'];

		foreach ($params['system_nodes_attachments'] as $id => $attachment)
		{
			if (!$attachment)
			{
				$model->execute
				(
					'DELETE FROM {self} WHERE attachmentid = ? AND nid = ?', array
					(
						$id, $nid
					)
				);

				continue;
			}

			$model->save
			(
				array
				(
					'attachmentid' => $id,
					'nid' => $nid,
					'targetid' => $attachment
				),

				null,

				array
				(
					'on duplicate' => array
					(
						'targetid' => $attachment
					)
				)
			);
		}
	}
}