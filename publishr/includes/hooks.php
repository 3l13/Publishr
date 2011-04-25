<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class publisher_WdHooks
{
	/**
	 * This callback is used to delete all the locks set by the user while editing entries.
	 *
	 * @param WdEvent $event
	 */

	static public function before_operation_disconnect(WdEvent $event)
	{
		global $core;

		$uid = $core->user_id;

		if (!$uid)
		{
			return;
		}

		try
		{
			$registry = $core->registry;

			$names = $registry->select('name')
			->where('name LIKE "admin.locks.%.uid" AND value = ?', $uid)
			->all(PDO::FETCH_COLUMN);

			if ($names)
			{
				$in = array();

				foreach ($names as $name)
				{
					$in[] = $name;
					$in[] = substr($name, 0, -3) . 'until';
				}

				$registry->where(array('name' => $in))->delete();
			}
		}
		catch (WdException $e) {  };
	}

	static public function before_operation_components_all(WdEvent $event)
	{
		global $core;

		$language = $core->user->language;

		if ($language)
		{
			$core->language = $language;
		}
	}

	/**
	 * This callback is used to alter the operation's response by adding the document's assets
	 * addresses.
	 *
	 * The callback is called when an event matches the 'operation.components/*' pattern.
	 *
	 * @param WdEvent $event
	 */
	static public function operation_components_all(WdEvent $event)
	{
		global $core;

		$operation = $event->operation;

		if (empty($core->document))
		{
			return;
		}

		$document = $core->document;

		$operation->response->assets = array
		(
			'css' => $document->css->get(),
			'js' => $document->js->get()
		);
	}

	static public function query_operation(array $params)
	{
		static $suffix = '_WdModule';
		static $suffix_lenght = 9;
		global $core;

		$module = $core->modules[$params['module']];

		$try = get_class($module);

		while ($try && substr($try, -$suffix_lenght) == $suffix)
		{
			$class = substr($try, 0, -$suffix_lenght) . "__query_operation_WdOperation";

			if (class_exists($class, true))
			{
				break;
			}

			$class = null;
			$try = get_parent_class($try);
		}

		if (!$class)
		{
			//throw new WdException("Unable to query %module module for the %operation operation, the interface is missing.", array('%module' => $module->id, '%operation' => $params['operation']));
			$class = 'query_operation_WdOperation';
		}

		return new $class($module, 'query_operation', $params);
	}
}