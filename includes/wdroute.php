<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class WdRoute
{
	static protected $configs = array();
	static protected $routes = array();

	static public function autoconfig()
	{
		throw new WdException('autoconfig is deprecated');

		$configs = func_get_args();

		if (self::$configs)
		{
			array_unshift($configs, self::$configs);

			self::$configs = call_user_func_array('array_merge', $configs);
		}
		else
		{
			self::$configs = $configs;
		}

//		var_dump($configs);

		// TODO: better than this ?

		self::$routes = array();
	}

	static public function routes()
	{
		if (!self::$routes)
		{
			self::$routes = WdCore::getConstructedConfig('route', array(__CLASS__, 'routes_constructor'));
		}

		return self::$routes;
	}

	static public function routes_constructor($configs)
	{
		global $core;

		//WdDebug::trigger('routes() called baby');

		$routes = array();

		foreach ($configs as $config)
		{
			if (isset($config['routes']))
			{
				throw new WdException('The %key key is not valid, please used zero index: !ar', array('%key' => 'route', '!ar' => $config));
			}

			if (empty($config['defaults']))
			{
				throw new WdException('route defaults fuck: \1', array($config));
			}

			if (empty($config['defaults']['module']))
			{
				throw new WdException('route module fuck: \1', array($config));
			}

			list($definitions) = $config;
			$defaults = $config['defaults'];
			$module_id = $config['defaults']['module'];

			if (!$core->hasModule($module_id))
			{
				continue;
			}

			$config = array();

			foreach ($definitions as $pattern => $route)
			{
				$config[$pattern] = $route + $defaults;
			}

			foreach ($config as $pattern => $route)
			{
				list($workspace) = explode('.', $module_id, 2);

				switch ($pattern)
				{
					case 'manage':
					{
						$pattern = '/{self}';

						$route += array
						(
							'title' => 'Liste',
							'block' => 'manage',
							'index' => true
						);
					}
					break;

					case 'create':
					{
						$pattern = '/{self}/create';

						$route += array
						(
							'title' => 'Nouveau',
							'block' => 'edit'
						);
					}
					break;

					case 'edit':
					{
						$pattern = '/{self}/<\d+>/{block}';

						$route += array
						(
							'title' => 'Éditer',
							'block' => 'edit',
							'visibility' => 'auto'
						);
					}
					break;

					case 'config':
					{
						$pattern = '/{self}/config';

						$route += array
						(
							'title' => 'Config.',
							'block' => 'config'
						);
					}
					break;
				}

				if (is_string($pattern))
				{
					$pattern = strtr
					(
						$pattern, array
						(
							'{self}' => $module_id,
							'{module}' => $module_id,
							'{block}' => isset($route['block']) ? $route['block'] : 'unknown'
						)
					);
				}

				$route += array
				(
					'module' => $module_id,
					'workspace' => $workspace,
					'visibility' => 'visible'
				);

				$routes[$pattern] = $route;
			}
		}

		return $routes;

		//self::$routes = array_merge(self::$routes, $routes);

		//wd_log('routes: \1', array(self::$routes));

		//return self::$routes;
	}

	static public function encode($route, array $params=array())
	{
		if ($params)
		{
			WdDebug::trigger('params are not implemented');
		}

		return $_SERVER['SCRIPT_NAME'] . $route;
	}

	static protected $parseCache = array();

	static public function parse($pattern)
	{
		if (isset(self::$parseCache[$pattern]))
		{
			return self::$parseCache[$pattern];
		}

		$parts = preg_split('/<((\w+):)?(.*?)?>/', $pattern, -1, PREG_SPLIT_DELIM_CAPTURE);

		//wd_log('parse parts: \1', array($parts));

		$expression = '#^';
		$interleave = array();
		$params_keys = array();
		$param_i = 0;

		foreach ($parts as $i => $part)
		{
			//wd_log('<code>[\1]: \2</code>', array($i % 4, $part));

			switch ($i % 4)
			{
				case 0:
				{
					$expression .= $part;
					$interleave[] = $part;
				}
				break;

				case 2:
				{
					if (!$part)
					{
						$part = $param_i++;
					}

					$interleave[] = array($part, $parts[$i + 1]);
					$params_keys[] = $part;
				}
				break;

				case 3:
				{
					$expression .= '(' . $part . ')';
				}
				break;
			}
		}

		$expression .= '$#';

		return self::$parseCache[$pattern] = array($interleave, $params_keys, $expression);
	}

	static public function match($uri, $pattern)
	{
		$parts = preg_split('/<((\w+):)?(.*?)?>/', $pattern, -1, PREG_SPLIT_DELIM_CAPTURE);

		//wd_log('pattern: %pattern !parts', array('%pattern' => $pattern, '!parts' => $parts));

		/*
		preg_match_all('/<((\w+):)?(.*?)?>/', $pattern, $matches);

		wd_log('pattern: %pattern !matches', array('%pattern' => $pattern, '!matches' => $matches));
		*/

		$expression = '#^';
		$params_keys = array();
		$param_i = 0;

		foreach ($parts as $i => $part)
		{
			//wd_log('<code>[\1]: \2</code>', array($i % 4, $part));

			switch ($i % 4)
			{
				case 0:
				{
					$expression .= $part;
				}
				break;

				case 2:
				{
					$params_keys[] = $part ? $part : $param_i++;
				}
				break;

				case 3:
				{
					$expression .= '(' . $part . ')';
				}
				break;
			}
		}

		$expression .= '$#';

		//wd_log('param keys: \1', array($params_keys));

		#
		# capture
		#

		$match = preg_match($expression, $uri, $matches);

		$params = array();

		if (!$match)
		{
			return false;
		}
		else if (!$params_keys)
		{
			return true;
		}

		$capture = array_shift($matches);

		//wd_log('combine: \1 and \2', array($params_keys, $matches));

		$params = array_combine($params_keys, $matches);

		return $params;
	}
}