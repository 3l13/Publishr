<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class system_nodes_WdModel extends WdModel
{
	const T_CONSTRUCTOR = 'constructor';

	protected $constructor;

	public function __construct($tags)
	{
		if (empty($tags[self::T_CONSTRUCTOR]))
		{
			throw new WdException
			(
				'The %tag tag is required: !tags', array
				(
					'%tag' => self::T_CONSTRUCTOR,
					'!tags' => $tags
				)
			);
		}

		$this->constructor = $tags[self::T_CONSTRUCTOR];

		parent::__construct($tags);
	}

	public function save(array $properties, $key=null, array $options=array())
	{
		if (!$key)
		{
			global $core;

			$properties += array
			(
				Node::CONSTRUCTOR => $this->constructor,
				Node::UID => $core->user_id
			);

			if (empty($properties[Node::CONSTRUCTOR]))
			{
				throw new WdException('Missing <em>constructor</em>, required to save nodes');
			}
		}

		$properties += array
		(
			Node::MODIFIED => date('Y-m-d H:i:s')
		);

		if (empty($properties[Node::SLUG]) && isset($properties[Node::TITLE]))
		{
			$properties[Node::SLUG] = $properties[Node::TITLE];
		}

		if (isset($properties[Node::SLUG]))
		{
			$properties[Node::SLUG] = trim(substr(wd_normalize($properties[Node::SLUG]), 0, 80), '-');
		}

		return parent::save($properties, $key, $options);
	}

	/**
	 * The load() method is overridden so that entries are loaded using their true constructor.
	 *
	 * If the loaded entry is an object, the entry is cached.
	 *
	 * @see $wd/wdcore/WdModel#load($key)
	 */

	public function load($key)
	{
		$entry = parent::load($key);

		if ($entry)
		{
			global $core;

			$entry_model = $core->models[$entry->constructor];

			if ($this !== $entry_model)
			{
				#
				# we loaded an entry that was not created by this model, we need
				# to load the entry using the proper model and change the object.
				#

				$entry = $entry_model->load($key);
			}
		}

		return $entry;
	}

	public function parseConditions(array $conditions)
	{
		$where = array();
		$args = array();

		foreach ($conditions as $identifier => $value)
		{
			switch ($identifier)
			{
				case 'nid':
				{
					$where[] = '`nid` = ?';
					$args[] = $value;
				}
				break;

				case 'constructor':
				{
					$where[] = '`constructor` = ?';
					$args[] = $value;
				}
				break;

				case 'slug':
				case 'title':
				{
					$where[] = '(slug = ? OR title = ?)';
					$args[] = $value;
					$args[] = $value;
				}
				break;

				case 'language':
				{
					$where[] = '(language = "" OR language = ?)';
					$args[] = $value;
				}
				break;

				case 'is_online':
				{
					$where[] = 'is_online = ?';
					$args[] = $value;
				}
				break;
			}
		}

		return array($where, $args);
	}
}