<?php

class system_nodes_WdEvents
{
	static public function operation_disconnect(WdEvent $event)
	{
		global $core, $app;

		if (!($event->module instanceof user_users_WdModule))
		{
			return;
		}

		if (!$app->user_id)
		{
			return;
		}

		try
		{
			$model = $core->getModule('system.nodes')->model('locks');

			$model->execute('DELETE FROM {self} WHERE uid = ?', array($app->user_id));
		}
		catch (WdException $e) {  };
	}
}