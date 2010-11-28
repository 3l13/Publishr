<?php

class WdPDashboard
{
	static public function operation_order(WdOperation $operation)
	{
		global $core, $registry;

		if (!$core->user_id)
		{
			return false;
		}

		$order = $operation->params['order'];

		$registry['components.dashboard.order.uid_' . $core->user_id] = json_encode($order);

		return true;
	}
}