<?php

return array
(
	'label' => array
	(
		'language' => 'Language',
		'tnid' => 'Source of translation'
	),

	'element.description' => array
	(
		'language' => "It's the language of the record. Generally, only records that have the same
		language as the page, or a neutral language, appear on the page.",

		'tnid' => "Establishes a connection with the record in the native language
		(<q>:native</q>) and its translations (here <q>:language</q>). Records that have a neutral
		language can not be translated, therefore they do not appear in the list."
	),

	'option' => array
	(
		'neutral' => '<neutral>',
		'none' => '<none>'
	),

	'section.title' => array
	(
		'i18n' => 'Internationalization'
	)
);