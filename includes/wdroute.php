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
	static protected $routes = array();

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
				//throw new WdException('route defaults fuck: \1', array($config));
				$config['defaults'] = array();
			}

			if (empty($config['defaults']['module']))
			{
				//throw new WdException('route module fuck: \1', array($config));
				$config['defaults']['module'] = null;
			}

			list($definitions) = $config;

			$defaults = $config['defaults'];
			$module_id = $config['defaults']['module'];

			if ($module_id && !$core->hasModule($module_id))
			{
				// TODO-20100630: watchout for caches !!

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

				if (!$core->hasModule($route['module']))
				{
					wd_log('module is disabled for route: \1', array($route));

					continue;
				}

				$routes[$pattern] = $route;
			}
		}

		//wd_log('routes: \1', array($routes));

		return $routes;
	}

	static private $parse_cache = array();

	static public function parse($pattern)
	{
		if (isset(self::$parse_cache[$pattern]))
		{
			return self::$parse_cache[$pattern];
		}

		$regex = '#^';
		$interleave = array();
		$params = array();
		$n = 0;

		$parts = preg_split('#<((\w+):)?(.*?)?>#', $pattern, -1, PREG_SPLIT_DELIM_CAPTURE);

		foreach ($parts as $i => $part)
		{
			switch ($i % 4)
			{
				case 0:
				{
					$regex .= $part;
					$interleave[] = $part;
				}
				break;

				case 2:
				{
					if (!$part)
					{
						$part = $n++;
					}

					$interleave[] = array($part, $parts[$i + 1]);
					$params[] = $part;
				}
				break;

				case 3:
				{
					$regex .= '(' . $part . ')';
				}
				break;
			}
		}

		$regex .= '$#';

		return self::$parse_cache[$pattern] = array($interleave, $params, $regex);
	}

	static public function match($uri, $pattern)
	{
		$parsed = self::parse($pattern);

		list(, $params, $regex) = $parsed;

		$match = preg_match($regex, $uri, $values);

		if (!$match)
		{
			return false;
		}
		else if (!$params)
		{
			return true;
		}

		array_shift($values);

		return array_combine($params, $values);
	}

	static public function find_matching($uri)
	{
		$routes = self::routes();

		foreach ($routes as $pattern => $route)
		{
			$match = self::match($uri, $pattern);

			if (!$match)
			{
				continue;
			}

			return array($route, $match, $pattern);
		}
	}

	/**
	 *
	 * Returns a route formated using a pattern and values.
	 *
	 * @param string $pattern The route pattern
	 * @param mixed $values The values to format the pattern, either as an array or an object.
	 */

	static public function format($pattern, $values=array())
	{
		if (is_array($values))
		{
			$values = (object) $values;
		}

		$url = '';
		$parsed = self::parse($pattern);

		foreach ($parsed[0] as $i => $value)
		{
			$url .= ($i % 2) ? urlencode($values->$value[0]) : $value;
		}

		return $url;
	}

	// TODO-20100629: this method should die in favour of the format() method.

	static public function encode($route, array $params=array())
	{
		if ($params)
		{
			WdDebug::trigger('params are not implemented');
		}

		return $_SERVER['SCRIPT_NAME'] . $route;
	}
}