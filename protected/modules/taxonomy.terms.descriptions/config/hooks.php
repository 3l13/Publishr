<?php

return array
(
	'events' => array
	(
		'alter.block.edit' => array
		(
			array('m:taxonomy.terms.descriptions', 'event_alter_block_edit'),

			'instanceof' => 'taxonomy_terms_WdModule'
		),

		'operation.save' => array
		(
			array('m:taxonomy.terms.descriptions', 'event_operation_save'),

			'instanceof' => 'taxonomy_terms_WdModule'
		),

		'ar.property' => array
		(
			array('m:taxonomy.terms.descriptions', 'event_ar_property')
		)
	)
);