<?php

return array
(
	'patron.markups' => array
	(
		array
		(
			'taxonomy:popularity' => array
			(
				array('taxonomy_support_WdMarkups', 'popularity'), array
				(
					'vocabulary' => null,
					'scope' => null,
					'scale' => null
				)
			),

			'taxonomy:terms' => array
			(
				array('taxonomy_support_WdMarkups', 'terms'), array
				(
					'vocabulary' => null,
					'scope' => null
				)
			),

			'taxonomy:nodes' => array
			(
				array('taxonomy_support_WdMarkups', 'nodes'), array
				(
					'vocabulary' => null,
					'scope' => null,
					'term' => null,

					'by' => 'title',
					'order' => 'asc',
					'limit' => null
				)
			)
		)
	)
);