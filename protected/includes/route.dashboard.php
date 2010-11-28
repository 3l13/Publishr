<?php

function _route_add_dashboard()
{
	global $core, $registry, $document;

	$document->title = 'Dashboard';
	$document->css->add('../../public/css/dashboard.css');
	$document->js->add('../../public/js/dashboard.js');


	$event = WdEvent::fire
	(
		'alter.block.dashboard', array
		(
			'panels' => array()
		)
	);

	$panels = WdConfig::get_constructed('dashboard', 'merge');

	foreach ($panels as $i => $panel)
	{
		$panels[$i] += array
		(
			'column' => 0,
			'weight' => 0
		);
	}

	$user_config = $registry['components.dashboard.order.uid_' . $core->user_id];

	if ($user_config)
	{
		$user_config = json_decode($user_config);

		foreach ($user_config as $column_index => $user_panels)
		{
			foreach ($user_panels as $panel_weight => $panel_id)
			{
				$panels[$panel_id]['column'] = $column_index;
				$panels[$panel_id]['weight'] = $panel_weight;
			}
		}
	}

	uasort($panels, create_function('$a,$b', 'return $a[\'weight\'] - $b[\'weight\'];'));

	#
	#
	#

	$colunms = array
	(
		array(),
		array()
	);

	// config sign: ⚙

	foreach ($panels as $id => $descriptor)
	{
		try
		{
			$contents = call_user_func($descriptor['callback']);
		}
		catch (Exception $e)
		{
			$contents = $e->getMessage();
		}

		if (!$contents)
		{
			continue;
		}

		$title = t($descriptor['title']);

		$panel = <<<EOT
<div class="panel" id="$id">
	<div class="panel-title">$title</div>
	<div class="panel-contents">$contents</div>
</div>
EOT;

		$colunms[$descriptor['column']][] = $panel;
	}

	$rc = '<div id="dashboard"><div id="dashboard-panels">';

	foreach ($colunms as $i => $panels)
	{
		$panels = implode(PHP_EOL, $panels);

		$rc .= <<<EOT
<div class="column">
	$panels
	<div class="panel-holder">&nbsp;</div>
</div>
EOT;
	}

	$rc .= '</div></div>';

	$document->addToBlock($rc, 'contents');
}