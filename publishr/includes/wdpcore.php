<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
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

	static public function exception_handler($exception)
	{
		global $document;

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
	 * Overrides the method to handle disabled modules.
	 *
	 * @see /wdcore/WdCore#readModules_construct()
	 */

	protected function index_modules()
	{
		parent::index_modules();

		$enableds = array();

		try
		{
			$registry = $this->module('system.registry');

//			wd_log_time('got registry module');

			$enableds = $registry['wdcore.enabled_modules'];

//			wd_log_time('read from registry');

			$enableds = (array) json_decode($enableds, true);
		}
		catch (Exception $e) { /* well... we don't care */ }

//		wd_log_time('load from db');

		foreach ($this->descriptors as $module_id => &$descriptor)
		{
			if (!empty($descriptor[WdModule::T_REQUIRED]))
			{
				continue;
			}

			if (in_array($module_id, $enableds))
			{
				continue;
			}

			$descriptor[WdModule::T_DISABLED] = true;
		}

		#
		# MULTISITE
		#

		$this->site = $site = site_sites_WdHooks::find_by_request($_SERVER);

		if ($site)
		{
			WdI18n::setLanguage($site->language);
//			WdI18n::setTimezone($site->timezone);
		}

//		wd_log_time('done disabling');
	}

	protected function read_module_infos($module_id, $module_root)
	{
		$infos = parent::read_module_infos($module_id, $module_root);

		if (file_exists($module_root . 'manager.php'))
		{
			$class_base = strtr($module_id, '.', '_');

			$infos['autoload'][$class_base . '_WdManager'] = $module_root . 'manager.php';
		}

		return $infos;
	}

	public function getModuleIdsByProperty($tag, $default=null)
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