<?php

return array
(
	WdModule::T_TITLE => 'Membres',
	WdModule::T_CATEGORY => 'users',

	WdModule::T_MODELS => array
	(
		'primary' => array
		(
			WdModel::T_EXTENDS => 'user.users',
			WdModel::T_SCHEMA => array
			(
				'fields' => array
				(
					'gender' => array('integer', 'tiny'),

					#
					# numbers
					#

					'number_work' => array('varchar', 30),
					'number_home' => array('varchar', 30),
					'number_fax' => array('varchar', 30),
					'number_pager' => array('varchar', 30),
					'number_mobile' => array('varchar', 30),

					#
					# private
					#

					'address' => 'varchar',
					'address_complement' => 'varchar',
					'city' => array('varchar', 80),
					'state' => array('varchar', 80),
					'postalcode' => array('varchar', 10),
					'country' => array('varchar', 80),
					'webpage' => 'varchar',

					'birthday' => 'date',

					#
					# professional
					#

					'position' => array('varchar', 80),
					'service' => array('varchar', 80),
					'company' => array('varchar', 80),
					'company_address' => 'varchar',
					'company_address_complement' => 'varchar',
					'company_city' => array('varchar', 80),
					'company_state' => array('varchar', 80),
					'company_postalcode' => array('varchar', 10),
					'company_country' => array('varchar', 80),
					'company_webpage' => 'varchar',

					#
					# misc
					#

					'misc1' => 'varchar',
					'misc2' => 'varchar',
					'misc3' => 'varchar',
					'misc4' => 'varchar',
					'notes' => 'text',

					#
					# photo
					#

					'photo' => 'varchar'
				)
			)
		)
	)
);