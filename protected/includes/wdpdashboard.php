<?php

class WdPDashboard
{
	static public function operation_order(WdOperation $operation)
	{
		global $app, $registry;

		if (!$app->user_id)
		{
			return false;
		}

		$order = $operation->params['order'];

		$registry['components.dashboard.order.uid_' . $app->user_id] = json_encode($order);

		return true;
	}
}