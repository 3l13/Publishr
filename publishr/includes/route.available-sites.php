<?php

function _route_add_available_sites()
{
	global $core;

	$document = $core->document;
	$document->page_title = 'Select a website';

	$ws_title = wd_entities($core->site->admin_title ? $core->site->admin_title : $core->site->title .':' . $core->site->language);
	$site_model = $core->models['site.sites'];

	$available = $site_model
	->where('siteid IN(' . $core->user->metas['available_sites'] . ')')
	->order('admin_title, title')
	->all;

	$uri = substr($_SERVER['REQUEST_URI'], strlen($core->site->path));
	$options = array();

	foreach ($available as $site)
	{
		$title = $site->title . ':' . $site->language;

		if ($site->admin_title)
		{
			$title .= ' (' . $site->admin_title . ')';
		}

		$options[$site->url . $uri] = $title;
	}

	$form = new WdForm
	(
		array
		(
			WdElement::T_CHILDREN => array
			(
				new WdElement
				(
					'select', array
					(
						WdElement::T_LABEL => 'Available sites',
						WdElement::T_LABEL_POSITION => 'before',
						WdElement::T_OPTIONS => $options
					)
				),

				' &nbsp; ',

				new WdElement
				(
					WdElement::E_SUBMIT, array
					(
						WdElement::T_INNER_HTML => 'Change',

						'class' => 'continue'
					)
				)
			),

			'name' => 'change-working-site'
		)
	);

	$rc = <<<EOT
<div class="group">
<h2>Access denied</h2>
<p>You don't have permission to access the administration interface for the website <q>$ws_title</q>,
please select another website to work with:</p>
$form
</div>
EOT;

	$core->document->addToBlock($rc, 'contents');
}