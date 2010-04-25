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

		if (!$app->userId)
		{
			return;
		}

		try
		{
			$model = $core->getModule('system.nodes')->model('locks');

			$model->execute('DELETE FROM {self} WHERE uid = ?', array($app->userId));
		}
		catch (WdException $e) {  };
	}
}