<?php

return array
(
	'alter.block.edit' => array('m:taxonomy.terms.descriptions', 'event_alter_block_edit'),
	'taxonomy.terms.save' => array('m:taxonomy.terms.descriptions', 'event_taxonomy_terms_save'),
	'ar.property' => array('m:taxonomy.terms.descriptions', 'event_ar_property')
);