<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class system_nodes_WdModel extends WdConstructorModel
{
	public function save(array $properties, $key=null, array $options=array())
	{
		global $core;

		if (!$key && !array_key_exists(Node::UID, $properties))
		{
			$properties[Node::UID] = $core->user_id;
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
	 * Makes sure the node to delete is not used as a native target by other nodes.
	 *
	 * @see WdDatabaseTable::delete()
	 * @throws WdException An exception is raised if the node to delete is the native target of
	 * another node.
	 */
	public function delete($key)
	{
		$native_refs = $this->select('nid')->find_by_tnid($key)->all(PDO::FETCH_COLUMN);

		if ($native_refs)
		{
			throw new WdException('Node record cannot be deleted because it is used as native source by the following records: \1', array(implode(', ', $native_refs)));
		}

		return parent::delete($key);
	}

	protected function scope_online(WdActiveRecordQuery $query)
	{
		return $query->where('is_online = 1');
	}

	protected function scope_offline(WdActiveRecordQuery $query)
	{
		return $query->where('is_online = 0');
	}

	protected function scope_visible(WdActiveRecordQuery $query)
	{
		global $core;

		return $query->where('is_online = 1 AND (siteid = 0 OR siteid = ?) AND (language = "" OR language = ?)', $core->site->siteid, $core->site->language);
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