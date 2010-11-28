<?php

return array
(
	'events' => array
	(
		'alter.block.edit' => array
		(
			array('site_firstposition_WdModule', 'event_alter_block_edit')
		),

		'publisher.publish' => array
		(
			array('m:site.firstposition', 'event_publisher_publish')
		)
	),

	'patron.markups' => array
	(
		'document:metas' => array
		(
			array('site_firstposition_WdModule', 'markup_document_metas'), array()
		),

		'document:title' => array
		(
			array('site_firstposition_WdModule', 'markup_document_title'), array()
		),
	)
);