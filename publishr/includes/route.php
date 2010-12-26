<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

if (!$core->user || $core->user->is_guest())
{
	$request_route = '/admin/authenticate';
}
else
{
	$request_route = $_SERVER['REQUEST_URI'];

	if ($_SERVER['QUERY_STRING'])
	{
		$request_route = substr($request_route, 0, -(strlen($_SERVER['QUERY_STRING']) + 1));
	}
}

$routes = WdRoute::routes();

#
# create location for workspaces // TODO-20091118: that's quite bad, but enought for the time being
#

function _create_ws_locations($routes)
{
	global $core;

	$ws = array();
	$user = $core->user;

	foreach ($routes as $pattern => $route)
	{
		if (empty($route['workspace']) || empty($route['index']) || empty($route['module']))
		{
			continue;
		}

		$module_id = $route['module'];

		if (!$user->has_permission(WdModule::PERMISSION_ACCESS, $module_id) || !$core->has_module($module_id))
		{
			continue;
		}

		$ws_pattern = '/admin/' . $route['workspace'];

		if (isset($ws[$ws_pattern]))
		{
			$cmp_route = $routes[$ws[$ws_pattern]['location']];

			$cmp_title = $core->descriptors[$cmp_route['module']][WdModule::T_TITLE];
			$title = $core->descriptors[$module_id][WdModule::T_TITLE];

			//wd_log('compare \1 and \2 == \3', array($cmp_title, $title, strcmp($cmp_title, $title)));

			if (strcmp($cmp_title, $title) < 0)
			{
				continue;
			}
		}

		$ws[$ws_pattern]= array
		(
			'location' => $pattern
		);
	}

	$routes += $ws;

	//wd_log('ws: \1', array($ws));

	return $routes;
}

$routes = _create_ws_locations($routes);

/*
 * special routes are created from modules descriptors. For exemple, one can define the route 'edit' which
 * will be updated using a union with complete pattern (replaces key), module reference and workspace...
 *
 */

function _route_add_block($route, $params)
{
	global $core, $document;

	try
	{
		$module_id = $route['module'];
		$module = $core->module($module_id);

		array_unshift($params, $route['block']);

		//wd_log('params: \1', array($params));

		$block = call_user_func_array(array($module, 'getBlock'), $params);

		if (is_array($block))
		{
			$block = $block['element'];
		}
	}
	catch (Exception $e)
	{
		$block = '<div class="group">' . $e . '</div>';
	}

	//$document->addToBlock((string) $block, 'contents');

	$document->addToBlock(is_object($block) ? $block->__toString() : (string) $block, 'contents');
}

function _route_add_options($requested, $req_pattern)
{
	global $core, $document, $routes;

	if (empty($requested['workspace']))
	{
		return;
	}

	$req_ws = $requested['workspace'];
	$req_module = $requested['module'];

	$options = array();
	$user = $core->user;

	foreach ($routes as $pattern => $route)
	{
		if (is_numeric($pattern))
		{
			continue;
		}

		$module = isset($route['module']) ? $route['module'] : null;

		if (!$module || $module != $req_module)
		{
			continue;
		}

		$permission = isset($route['permission']) ? $route['permission'] : WdModule::PERMISSION_ACCESS;

		if (!$user->has_permission($permission, $module))
		{
			continue;
		}

		/*
		 * TODO: implement acces callback
		 *
		 */

		// TODO: les blocs qui utilisent des patterns devrait avoir une visibility = true

		/*
		if (empty($route['visibility']))
		{
			throw new WdException
			(
				'Missing %parameter for route %pattern !definition', array
				(
					'%parameter' => 'visibility',
					'%pattern' => $pattern,
					'!definition' => $route
				)
			);
		}
		*/

		if (empty($route['visibility']) || ($route['visibility'] == 'auto' && $pattern != $req_pattern))
		{
			continue;
		}

		$options[$pattern] = $route;
	}

	$template = <<<EOT
		<div id="menu">
			<ul class="items">#{items}</ul>
			<div id="menu-options">#{menu-options}</div>
			<div class="clear"></div>
		</div>
EOT;

	$items = null;

	global $request_route;

	foreach ($options as $pattern => $route)
	{
		if (empty($route['title']))
		{
			//WdDebug::trigger('Route has no title: !route', array('!route' => $route));

			continue;
		}

		if ($req_pattern == $pattern)
		{
			$items .= '<li class="selected">';
			$items .= '<a href="' . $request_route . '">' . $route['title'] . '</a>';
			$items .= '</li>';
		}
		else
		{
			$items .= '<li>';
			$items .= '<a href="' . $pattern . '">' . $route['title'] . '</a>';
			$items .= '</li>';
		}
	}

	#
	#
	#

	$options = $document->getBlock('menu-options');

	$block = strtr
	(
		$template,array
		(
			'#{items}' => $items,
			'#{menu-options}' => $options
		)
	);

	$document->addToBlock($block, 'contents-header');
}

/*
 *
 */

function _route_add_tabs($requested, $req_pattern)
{
	global $document, $routes, $core;

	$user = $core->user;

	if (!isset($requested['workspace']))
	{
		throw new WdException('Missing <em>workspace</em> for requested route !requested', array('!requested' => $requested));
	}

	$req_ws = $requested['workspace'];
	$req_module = $requested['module'];

	$tabs = array();

	foreach ($routes as $pattern => $route)
	{
		if (empty($route['workspace']) || $route['workspace'] != $req_ws)
		{
			//wd_log('discard pattern %pattern because ws %ws != %req', array('%pattern' => $pattern, '%ws' => isset($route['workspace']) ? $route['workspace'] : null, '%req' => $req_ws));

			continue;
		}

		if (empty($route['index']))
		{
			continue;
		}

		if (!$core->has_module($route['module']))
		{
			continue;
		}

		if (!$user->has_permission(WdModule::PERMISSION_ACCESS, $route['module']))
		{
			continue;
		}

		$tabs[$pattern] = $route;
	}

	if (!$tabs)
	{
		return;
	}

	//wd_log('tabs routes: \1', array($tabs));

	global $core;

	foreach ($tabs as $pattern => &$route)
	{
		$route['tab-title'] = t($core->descriptors[$route['module']][WdModule::T_TITLE]);
	}

	wd_array_sort_by($tabs, 'tab-title');

	$rc = '<ul class="tabs">';

	foreach ($tabs as $pattern => $route)
	{
		if ($route['module'] == $req_module)
		{
			$rc .= '<li class="selected">';

			$document->title = $route['tab-title'];
		}
		else
		{
			$rc .= '<li>';
		}


		$rc .= '<a href="' . $pattern . '">' . t($route['tab-title']) . '</a></li>';
	}

	$rc .= '</ul>';
	$rc .= '<div class="clear"></div>';

	$document->addToBlock($rc, 'contents-header');
}

/*
 *
 */

$matching_route = null;

foreach ($routes as $pattern => $route)
{
	$match = WdRoute::match($request_route, $pattern);

	if ($match === false)
	{
		continue;
	}

	if (isset($route['location']))
	{
		$location = $route['location'];

		header('Location: ' . $location);

		exit;
	}

	$matching_route = $route;

	//wd_log('pattern: %pattern, match: !match, route: !route', array('%pattern' => $pattern, '!match' => $match, '!route' => $route));

	break;
}

if ($request_route === false || $request_route == '/admin/' || $request_route == '/admin/dashboard')
{
	require_once 'route.dashboard.php';

	_route_add_dashboard();
}
else if ($matching_route)
{
	_route_add_block($route, is_array($match) ? $match : array());
	_route_add_tabs($route, $pattern);
	_route_add_options($route, $pattern);
}
else
{
	wd_log_error('unable to find matching pattern for route %route', array('%route' => $request_route));

	if ($core->user_id == 1)
	{
		$rc = wd_dump(array_keys(WdRoute::routes()));

		$document->addToBlock($rc, 'contents');
	}
}