<?php

return array
(
	WdModule::T_TITLE => 'Membres',
	WdModule::T_MODELS => array
	(
		'primary' => array
		(
			WdModel::T_EXTENDS => 'user.users',
			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'language' => array('char', 2),
					'gender' => array('integer', 'tiny'),
					'birthday' => 'date',

					#
					# address details
					#

					'company' => 'varchar',
					'street' => 'varchar',
					'postalcode' => 'varchar',
					'city' => 'varchar',
					'state' => 'varchar',
					'country' => 'varchar',

					#
					# contact details
					#

					'phone' => array('varchar', 32),
					'mobile' => array('varchar', 32),
					'fax' => array('varchar', 32),
					'website' => 'varchar'
				)
			)
		)
	)
);