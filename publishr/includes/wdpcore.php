<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class WdPCore extends WdCore
{
	public function __construct(array $tags=array())
	{
		$document_root = $_SERVER['DOCUMENT_ROOT'] . '/';
		$publishr_root = dirname(dirname(__FILE__));

		parent::__construct
		(
			wd_array_merge_recursive
			(
				array
				(
					'paths' => array
					(
						'config' => array
						(
							$publishr_root . '/framework/wdelements',
							$publishr_root . '/framework/wdpatron',
							$publishr_root,

							// TODO-20100926: MULTISITE! we have to check the current website

							$document_root . 'protected',
							$document_root . 'protected/all'
						),

						'i18n' => array
						(
							$publishr_root . '/framework/wdelements',
							$publishr_root,

							$document_root . 'protected/all/'
						)
					)
				),

				$tags
			)
		);
	}

	/**
	 * Override the method to provide our own accessor.
	 *
	 * @see WdCore::__get_modules()
	 */

	protected function __get_modules()
	{
		return new WdPublishrModulesAccessor();
	}

	/**
	 * Override the method to provide a nicer exception presentation.
	 *
	 * @param Exception $exception
	 */

	static public function exception_handler(Exception $exception)
	{
		if (headers_sent())
		{
			die($exception);
		}

		echo strtr
		(
			file_get_contents('exception.html', true),

			array
			(
				'#{css.base}' => WdDocument::resolve_url('../public/css/base.css'),
				'#{@title}' => ($exception instanceof WdException) ? $exception->getTitle() : 'Exception',
				'#{this}' => ($exception instanceof WdException) ? $exception : '<code>' . nl2br($exception) . '</code>'
			)
		);

		exit;
	}

	/**
	 * Override the method to select the site corresponding to the URL and set the appropriate
	 * language and timezone.
	 *
	 * @see WdCore::run_context()
	 */

	protected function run_context()
	{
		$this->site = $site = site_sites_WdHooks::find_by_request($_SERVER);

		WdI18n::setLanguage($site->language);
//		WdI18n::setTimezone($site->timezone);

		parent::run_context();
	}

	protected function run_operation($uri, array $params)
	{
		$path = $this->site->path;

		if ($path && preg_match('#^' . preg_quote($path . '/api/', '#') . '#', $uri))
		{
			$uri = substr($uri, strlen($path));
		}

		return parent::run_operation($uri, $params);
	}
}

/**
 * Accessor class for the modules of the framework.
 *
 */
class WdPublishrModulesAccessor extends WdModulesAccessor
{
	/**
	 * Overrides the method to disable selected modules before they are run.
	 *
	 * Modules are disabled againts a list of enabled modules. The enabled modules list is made
	 * from the registry values `wdcore.enabled_modules` and the value of the T_REQUIRED tag,
	 * which forces some modules to always be enaled.
	 *
	 * A cached value for the `wdcore.enabled_modules` is checked at
	 * ":repository.cache/core.enabled_modules". If the file is available, its content is used
	 * instead of querying the registry.
	 *
	 * @see WdModulesAccessor::run()
	 */

	public function run()
	{
		global $core;

		$enableds = (array) json_decode($core->vars['enabled_modules'], true);

		foreach ($this->descriptors as $module_id => &$descriptor)
		{
			if (!empty($descriptor[WdModule::T_REQUIRED]) || in_array($module_id, $enableds))
			{
				continue;
			}

			$descriptor[WdModule::T_DISABLED] = true;
		}

		parent::run();
	}

	/**
	 * Overrides the method to handle the autoloading of the manager's class for the specified
	 * module.
	 *
	 * @see WdModulesAccessor::index_module()
	 */

	protected function index_module($id, $path)
	{
		$info = parent::index_module($id, $path);

		if (file_exists($path . 'manager.php'))
		{
			$class_base = strtr($id, '.', '_');

			$info['autoload'][$class_base . '_WdManager'] = $path . 'manager.php';
		}

		return $info;
	}

	public function ids_by_property($tag, $default=null)
	{
		$rc = array();

		foreach ($this->descriptors as $id => $descriptor)
		{
			if (!isset($descriptor[$tag]))
			{
				if ($default === null)
				{
					continue;
				}

				$value = $default;
			}
			else
			{
				$value = $descriptor[$tag];
			}

			$rc[$id] = $value;
		}

		return $rc;
	}
}