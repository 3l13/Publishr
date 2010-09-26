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
	public function __construct()
	{
		#
		# Add initial config
		#

		$adminroot = dirname(dirname(dirname(__FILE__)));

		WdConfig::add($adminroot . '/wdelements', 20);
		WdConfig::add($adminroot . '/wdpatron', 20);
		WdConfig::add($adminroot . '/wdpublisher/protected', 20);

		// TODO-20100926: we have to check the current website

		WdConfig::add($_SERVER['DOCUMENT_ROOT'] . '/sites/all', 20);

		#
		#
		#

		parent::__construct();

		#
		# add some i18n catalogs
		#

		WdLocale::addPath(WDELEMENTS_ROOT);
		WdLocale::addPath(dirname(__FILE__));
		WdLocale::addPath(dirname(dirname(__FILE__)) . '/protected/');
		WdLocale::addPath(dirname(dirname(__FILE__)) . '/protected/includes/');

		#
		#
		#

		//
		// FIXME-20100830: this is only implemented to select the language to use, according to the
		// request URL, we have to use site objects is the future.
		//

		$url = $_SERVER['REQUEST_URI'];

		if (strlen($url) > 3 && $url[0] == '/' && $url[3] == '/')
		{
			$req = substr($url, 1, 2);

			foreach (WdLocale::$languages as $language)
			{
				if ($req != $language)
				{
					continue;
				}

				WdLocale::setLanguage($req);

				break;
			}
		}
	}

	static public function exceptionHandler($exception)
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
				'#{css.base}' => WdDocument::getURLFromPath('../public/css/base.css'),
				'#{@title}' => ($exception instanceof WdException) ? $exception->getTitle() : 'Exception',
				'#{this}' => ($exception instanceof WdException) ? $exception : '<code>' . nl2br($exception) . '</code>'
			)
		);

		exit;
	}

	static public function event_packages(WdEvent $ev)
	{
		global $core;

		$core->cache->clear();

		// TODO-20100108: move this to the module.

		header('Location: ' . $_SERVER['REQUEST_URI']);

		exit;
	}

	/**
	 * Override to handle disabled modules
	 * @see wd/wdcore/WdCore#readModules_construct()
	 */

	public function readModules()
	{
		parent::readModules();

		try
		{
			$registry = $this->getModule('system.registry');

			$enableds = $registry['wdcore.enabled_modules'];
			$enableds = (array) json_decode($enableds, true);

			foreach ($this->descriptors as $module_id => &$descriptor)
			{
				if (!empty($descriptor[WdModule::T_MANDATORY]))
				{
					continue;
				}

				if (in_array($module_id, $enableds))
				{
					continue;
				}

				$descriptor[WdModule::T_DISABLED] = true;
			}
		}
		catch (Exception $e) { /* well... we don't care */ }
	}

	/*
	 * Return modules sorted by title and packages
	 */

	public function getModulesTree()
	{
		$tree = array();

		foreach ($this->descriptors as $m_id => $descriptor)
		{
			if (empty($descriptor[WdModule::T_TITLE]))
			{
				continue;
			}

			$m_name = $descriptor[WdModule::T_TITLE];

			list($p_id) = explode('.', $m_id);

			$tree[$p_id][$m_name] = $descriptor;
		}

		ksort($tree);

		return $tree;
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