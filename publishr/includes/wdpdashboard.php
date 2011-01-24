<?php

class WdPDashboard
{
	static public function operation_order(WdOperation $operation)
	{
		global $core;

		if (!$core->user_id)
		{
			return false;
		}

		$order = $operation->params['order'];

		$core->user->metas['components.dashboard.order'] = json_encode($order);

		return true;
	}
}