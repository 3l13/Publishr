<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class system_registry_WdHooks
{
	/**
	 * This is the callback for the `metas` virtual property added to the "nodes", "users" and
	 * "sites" active records.
	 *
	 * @param object An instance of system_nodes_WdActiveRecord, user_users_WdActiveRecord or
	 * site_sites_WdActiveRecord.
	 *
	 * @return object A system_registry_WdMetasHandler object that can be used to access or
	 * modify the metadatas associated with that object.
	 */

	static public function __get_metas(WdActiveRecord $target)
	{
		return new system_registry_WdMetasHandler($target);
	}

	/**
	 * This si the callback for the `registry` virtual property added to the core object.
	 *
	 * @param WdCore $target The core object.
	 * @return WdModule The "system.registry" module.
	 */

	static public function __get_registry(WdCore $target)
	{
		return $target->models['system.registry'];
	}

	/**
	 * This callback alter the edit block of the "system.nodes", "user.users" and "site.sites"
	 * modules, adding support for metadatas by loading the metadatas associated with the edited
	 * object and merging them with the current properties.
	 *
	 * @param WdEvent $event
	 * @throws WdException
	 */

	static public function alter_block_edit(WdEvent $event)
	{
		global $core;

		if (!$event->key)
		{
			return;
		}

		$target = $event->target;

		if ($target instanceof system_nodes_WdModule)
		{
			$type = 'node';
		}
		else if ($target instanceof user_users_WdModule)
		{
			$type = 'user';
		}
		else if ($target instanceof site_sites_WdModule)
		{
			$type = 'site';
		}
		else
		{
			throw new WdException('Metadatas are not supported for instances of the given class: %class', array('%class' => get_class($target)));
		}

		$model = $core->models['system.registry/' . $type];
		$metas = $model->select('name, value')->find_by_targetid($event->key)->pairs;

		if (isset($event->properties['metas']))
		{
			if ($event->properties['metas'] instanceof system_registry_WdMetasHandler)
			{
				$event->properties['metas'] = $event->properties['metas']->to_a();
			}

			$event->properties['metas'] += $metas;
		}
		else
		{
			$event->properties['metas'] = $metas;
		}
	}

	/**
	 * This callback saves the metadatas associated with the object targeted by the operation.
	 *
	 * @param WdEvent $event
	 * @throws WdException
	 */

	static public function operation_save(WdEvent $event)
	{
		global $core;

		$target = $event->target;
		$params = &$event->operation->params;

		if (!array_key_exists('metas', $params))
		{
			return;
		}

		$targetid = $event->rc['key'];

		if ($target instanceof system_nodes_WdModule)
		{
			$type = 'node';
		}
		else if ($target instanceof user_users_WdModule)
		{
			$type = 'user';
		}
		else if ($target instanceof site_sites_WdModule)
		{
			$type = 'site';
		}
		else
		{
			throw new WdException('Metadatas are not supported for instances of the given class: %class', array('%class' => get_class($target)));
		}

		$model = $core->models['system.registry/' . $type];

		$delete_statement = '';

		$update_groups = array();
		$delete_args = array();

		foreach ($params['metas'] as $name => $value)
		{
			if (is_array($value))
			{
				$value = serialize($value);
			}
			else if (!strlen($value))
			{
				$value = null;

				$delete_statement .= ', ?';
				$delete_args[] = $name;

				continue;
			}

			$update_groups[] = array($targetid, $name, $value);
		}

		$model->connection->begin();

		if ($delete_statement)
		{
			array_unshift($delete_args, $targetid);

			$delete_statement = 'DELETE FROM {self} WHERE targetid = ? AND name IN (' . substr($delete_statement, 2) . ')';

			$model->execute($delete_statement, $delete_args);
		}

		if ($update_groups)
		{
			$update = $model->prepare('INSERT OR REPLACE INTO {self} (targetid, name, value) VALUES(?,?,?)');

			foreach ($update_groups as $values)
			{
				$update->execute($values);
			}
		}

		$model->connection->commit();
	}

	static public function operation_delete(WdEvent $event)
	{
		global $core;

		$target = $event->target;

		if ($target instanceof system_nodes_WdModule)
		{
			$type = 'node';
		}
		else if ($target instanceof user_users_WdModule)
		{
			$type = 'user';
		}
		else if ($target instanceof site_sites_WdModule)
		{
			$type = 'site';
		}
		else
		{
			throw new WdException('Metadatas are not supported for instances of the given class: %class', array('%class' => get_class($target)));
		}

		$model = $core->models['system.registry/' . $type];

		$model->execute('DELETE FROM {self} WHERE targetid = ?', array($event->operation->key));
	}
}

/**
 *
 * This class is used to create objects to handle reading and modifing of metadatas associated with
 * a target object.
 *
 * @author olivierlaviale
 *
 */

class system_registry_WdMetasHandler implements ArrayAccess
{
	private static $models;
	private $values;

	public function __construct($target)
	{
		if ($target instanceof system_nodes_WdActiveRecord)
		{
			$this->targetid = $target->nid;
			$type = 'node';
		}
		else if ($target instanceof user_users_WdActiveRecord)
		{
			$this->targetid = $target->uid;
			$type = 'user';
		}
		else if ($target instanceof site_sites_WdActiveRecord)
		{
			$this->targetid = $target->siteid;
			$type = 'site';
		}
		else
		{
			throw new WdException('Metadatas are not supported for instances of the given class: %class', array('%class' => get_class($target)));
		}

		if (empty(self::$models[$type]))
		{
			global $core;

			self::$models[$type] = $core->models['system.registry/' . $type];
		}

		$this->model = self::$models[$type];
	}

	public function get($name, $default=null)
	{
		if ($this->values === null)
		{
			$this->values = $this->model->select('name, value')->find_by_targetid($this->targetid)->order('name')->pairs;
		}

		if ($name == 'all')
		{
			return $this->values;
		}

		if (!isset($this->values[$name]))
		{
			return $default;
		}

		return $this->values[$name];
	}

	public function set($name, $value)
	{
		$this->values[$name] = $value;

		if ($value === null)
		{
			//wd_log('delete %name because is has been set to null', array('%name' => $name));

			$this->model->execute
			(
				'DELETE FROM {self} WHERE targetid = ? AND name = ?', array
				(
					$this->targetid, $name
				)
			);
		}
		else
		{
			$this->model->insert
			(
				array
				(
					'targetid' => $this->targetid,
					'name' => $name,
					'value' => $value
				),

				array
				(
					'on duplicate' => true
				)
			);
		}
	}

	public function to_a()
	{
		return $this->get('all');
	}

	public function offsetSet($offset, $value)
	{
        $this->set($offset, $value);
    }

    public function offsetExists($offset)
    {
        return $this->get($offset) !== null;
    }

    public function offsetUnset($offset)
    {
        $this->set($offset, null);
    }

    public function offsetGet($offset)
    {
    	return $this->get($offset);
    }
}