<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
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
				$properties[User::PASSWORD] = user_users_WdModule::generatePassword();
			}
		}

		#
		# If defined, the password is encrypted before we pass it to our super class.
		#

		if (!empty($properties[User::PASSWORD]))
		{
			$properties[User::PASSWORD] = md5($properties[User::PASSWORD]);
		}

		return parent::save($properties, $key, $options);
	}
}