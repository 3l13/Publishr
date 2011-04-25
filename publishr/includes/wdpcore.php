<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * The following properties are injected by the "system.registry" module.
 *
 * @property system_registry_WdModel $registry Global registry object.
 *
 * The following properties are injected by the "site.sites" module.
 *
 * @property int $site_id Identifier of the current site.
 * @property site_sites_WdActiveRecord $site Current site object.
 *
 * The following properties are injected by the "user.users" module.
 *
 * @property user_users_WdActiveRecord $user Current user object (might be a visitor).
 * @property int $user_id Identifier of the current user ("0" for visitors).
 */
class WdPCore extends WdCore
{
	/**
	 * Returns the unique core instance.
	 *
	 * @param array $options
	 * @param string $class
	 *
	 * @return WdPCore The core object.
	 */
	static public function get_instance(array $options=array(), $class=__CLASS__)
	{
		$document_root = $_SERVER['DOCUMENT_ROOT'] . '/';
		$publishr_root = dirname(dirname(__FILE__));

		return parent::get_instance
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

							//$document_root . 'protected',
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

				$options
			),

			$class
		);
	}

	/**
	 * Override the method to provide a nicer exception presentation.
	 *
	 * @param Exception $exception
	 */
	static public function exception_handler(Exception $exception)
	{
		global $core;

		if (headers_sent())
		{
			exit((string) $exception);
		}

		$site = isset($core->site) ? $core->site : null;

		echo strtr
		(
			file_get_contents('exception.html', true), array
			(
				'#{css.base}' => WdDocument::resolve_url('../public/css/base.css'),
				'#{@title}' => ($exception instanceof WdException) ? $exception->getTitle() : 'Exception',
				'#{this}' => ($exception instanceof WdException) ? $exception : '<code>' . nl2br($exception) . '</code>',
				'#{site_title}' => $site ? $site->title : 'Publishr',
				'#{site_url}' => $site ? $site->path : '',
				'#{version}' => preg_replace('#\s\([^\)]+\)#', '', WdPublisher::VERSION)
			)
		);

		exit;
	}

	/**
	 * Override the method to provide our own accessor.
	 *
	 * @see WdCore::__get_modules()
	 */
	protected function __get_modules()
	{
		$config = $this->config;

		return new WdPublishrModulesAccessor($config['modules'], $config['cache modules'], $config['repository.cache'] . '/core');
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
		$this->language = $site->language;

		if ($site->timezone)
		{
			$this->timezone = $site->timezone;
		}

		parent::run_context();
	}

	/**
	 * Contextualize the API string by prefixing it with the current site path.
	 *
	 * @see WdCore::contextualize_api_string()
	 */
	public function contextualize_api_string($string)
	{
		return $this->site->path . $string;
	}

	/**
	 * Decontextualize the API string by removing the current site path.
	 *
	 * @see WdCore::decontextualize_api_string()
	 */
	public function decontextualize_api_string($string)
	{
		$path = $this->site->path;

		if ($path && preg_match('#^' . preg_quote($path . '/api/', '#') . '#', $string))
		{
			$string = substr($string, strlen($path));
		}

		return $string;
	}
}

/**
 * Accessor class for the modules of the framework.
 */
class WdPublishrModulesAccessor extends WdModulesAccessor
{
	/**
	 * Overrides the method to disable selected modules before they are run.
	 *
	 * Modules are disabled againts a list of enabled modules. The enabled modules list is made
	 * from the "enabled_modules" persistant var and the value of the T_REQUIRED tag,
	 * which forces some modules to always be enabled.
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