<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class user_users_WdModel extends WdConstructorModel
{
	public function save(array $properties, $key=null, array $options=array())
	{
		if ($key)
		{
			#
			# If the user has already been created and the PASSWORD property is empty,
			# in order to avoid setting an empty password, we unset the property.
			#

			if (empty($properties[User::PASSWORD]))
			{
				unset($properties[User::PASSWORD]);
			}
		}
		else
		{
			#
			# If the password is not defined, and the user should be activated, we create one.
			#

			if (empty($properties[User::PASSWORD]) && !empty($properties[User::IS_ACTIVATED]))
			{
				// FIXME-20110411: use nonce-login message should be sent by the "save" operation.

				$properties[User::PASSWORD] = WdSecurity::generate_token(64, 'wide');
			}
		}

		#
		# If defined, the password is encrypted before we pass it to our super class.
		#

		if (!empty($properties[User::PASSWORD]))
		{
			$properties[User::PASSWORD] = user_users_WdActiveRecord::hash_password($properties[User::PASSWORD]);
		}

		return parent::save($properties, $key, $options);
	}
}