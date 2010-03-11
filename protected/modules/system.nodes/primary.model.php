<?php

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
				'The %tag tag is mandatory: !tags', array
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
			global $user;

			$properties += array
			(
				Node::CONSTRUCTOR => $this->constructor,
				Node::UID => $user->uid
			);
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
			$properties[Node::SLUG] = wd_normalize($properties[Node::SLUG]);
		}

		return parent::save($properties, $key, $options);
	}

	/**
	 * The load() method is overriden so that entries are loaded using their true constructor.
	 *
	 * If the loaded entry is an object, the entry is cached.
	 *
	 * @see $wd/wdcore/WdModel#load($key)
	 */

	static protected $objects_cache_extended = array();

	public function load($key)
	{
		$entry = parent::load($key);

		if ($entry && empty(self::$objects_cache_extended[$key]) && $entry->constructor != $this->constructor)
		{
			#
			# we loaded an entry that was not created by this model, we need
			# to load the entry using the proper model and exchange the objects.
			#

			global $core;

			$entry = $core->getModule($entry->constructor)->model()->load($key);

			#
			# Don't forget to update the cache !
			#

			if (is_object($entry))
			{
				self::$objects_cache_extended[$key] = true;
				self::$objects_cache[$this->name][$key] = $entry;
			}
		}

		return $entry;
	}


	public function parseConditions(array $conditions)
	{
		$where = array();
		$params = array();

		foreach ($conditions as $identifier => $value)
		{
			switch ($identifier)
			{
				case 'title':
				case 'slug':
				{
					$where[] = '(title = ? OR slug = ?)';
					$params[] = $value;
					$params[] = $value;
				}
				break;

				case 'language':
				{
					$where[] = '(language = "" OR language = ?)';
					$params[] = $value;
				}
				break;
			}
		}

		return array($where, $params);
	}
}