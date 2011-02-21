<?php

function _route_add_available_sites()
{
	global $core;

	$document = $core->document;
	$document->page_title = 'Select a website';

	$ws_title = wd_entities($core->working_site->admin_title ? $core->working_site->admin_title : $core->working_site->title .':' . $core->working_site->language);
	$site_model = $core->models['site.sites'];

	$options = $site_model
	->select('siteid, IF(admin_title != "", admin_title, concat(title, ":", language))')
	->where('siteid IN(' . $core->user->metas['available_sites'] . ')')
	->order('admin_title, title')
	->pairs;

	$form = new WdForm
	(
		array
		(
			WdElement::T_CHILDREN => array
			(
				'change_working_site' => new WdElement
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
			)
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