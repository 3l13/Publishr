<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

require_once WDCORE_ROOT . 'wdcore.php';

class WdPCore extends WdCore
{
	const CACHE_DISABLED_MODULES = 'disabled-modules';

	public function __construct(array $config=array())
	{
		parent::__construct($config);

		#
		# add some i18n catalogs
		#

		WdLocale::addPath(WDELEMENTS_ROOT);
		WdLocale::addPath(WDPUBLISHER_ROOT . 'includes');
		WdLocale::addPath(WDPUBLISHER_ROOT . 'protected');
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
				'#{@title}' => $exception->getTitle(),
				'#{this}' => $exception
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

//			$registry->set('wdcore.disabledModules', null);

			$disableds = $registry->get('wdcore.disabledModules');

			if ($disableds)
			{
				$disableds = (array) json_decode($disableds);

				foreach ($disableds as $id)
				{
					if (empty($this->descriptors[$id]))
					{
						continue;
					}

					$this->descriptors[$id][WdModule::T_DISABLED] = true;
				}
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