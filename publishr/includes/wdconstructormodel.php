<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

/**
 * This is the super class for all models using constructors (currently system.nodes and
 * user.users). It provides support for the `constructor` property whethers it is for saving
 * records or filtering them throught the `own` scope.
 */
class WdConstructorModel extends WdModel
{
	const T_CONSTRUCTOR = 'constructor';

	protected $constructor;

	public function __construct($tags)
	{
		if (empty($tags[self::T_CONSTRUCTOR]))
		{
			throw new WdException('The T_CONSTRUCTOR tag is required');
		}

		$this->constructor = $tags[self::T_CONSTRUCTOR];

		parent::__construct($tags);
	}

	/**
	 * Overwrites the `constructor` property of new records.
	 *
	 * @see WdModel::save()
	 */
	public function save(array $properties, $key=null, array $options=array())
	{
		if (!$key && empty($properties[Node::CONSTRUCTOR]))
		{
			$properties[Node::CONSTRUCTOR] = $this->constructor;
		}

		return parent::save($properties, $key, $options);
	}

	/**
	 * We override the load() method to make sure that records are loaded using their true
	 * constructor.
	 *
	 * @see WdModel::load()
	 */
	public function find($key)
	{
		global $core;

		$args = func_get_args();
		$record = call_user_func_array((PHP_MAJOR_VERSION > 5 || (PHP_MAJOR_VERSION == 5 && PHP_MINOR_VERSION > 2)) ? 'parent::' . __FUNCTION__ : array($this, 'parent::' . __FUNCTION__), $args);

		if ($record instanceof WdActiveRecord)
		{
			$entry_model = $core->models[$record->constructor];

			if ($this !== $entry_model)
			{
				$record = $entry_model[$key];
			}
		}

		return $record;
	}

	/**
	 * Adds the "constructor = <constructor>" condition to the query.
	 *
	 * @return WdActiveRecordQuery
	 */
	protected function scope_own(WdActiveRecordQuery $query)
	{
		return $query->where('constructor = ?', $this->constructor);
	}
}