<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class user_users_WdModel extends WdModel
{
	const T_CONSTRUCTOR = 'constructor';

	protected $constructor;

	public function __construct($tags)
	{
		if (empty($tags[self::T_CONSTRUCTOR]))
		{
			throw new WdException('The %tag tag is required: !tags', array('%tag' => self::T_CONSTRUCTOR, '!tags' => $tags));
		}

		$this->constructor = $tags[self::T_CONSTRUCTOR];

		parent::__construct($tags);
	}

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
			# We are creating a new entry, we set its constructor (the module that is creating
			# the entry).
			#

			$properties[User::CONSTRUCTOR] = $this->constructor;

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

	public function find($key)
	{
		$args = func_get_args();
		$record = call_user_func_array((PHP_MAJOR_VERSION > 5 || (PHP_MAJOR_VERSION == 5 && PHP_MINOR_VERSION > 2)) ? 'parent::' . __FUNCTION__ : array($this, 'parent::' . __FUNCTION__), $args);

		if ($record instanceof WdActiveRecord)
		{
			global $core;

			$entry_model = $core->models[$record->constructor];

			if ($this !== $entry_model)
			{
				#
				# we loaded an entry that was not created by this model, we need
				# to load the entry using the proper model and change the object.
				#

				$record = $entry_model[$key];
			}
		}

		return $record;
	}
}