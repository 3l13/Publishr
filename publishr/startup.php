<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if (!defined('PHP_MAJOR_VERSION'))
{
	#
	# The PHP_MAJOR_VERSION and PHP_MINOR_VERSION constants are available since 5.2.7
	#

	define('PHP_MAJOR_VERSION', 5);
	define('PHP_MINOR_VERSION', 2);
}

require_once dirname(__FILE__) . '/includes/startup.php';

$uri = $_SERVER['REQUEST_URI'];
$site = $core->site;
$suffix = $site->path;

if ($suffix && preg_match('#^' . preg_quote($suffix) . '/#', $uri))
{
	$uri = substr($uri, strlen($suffix));
}

if (preg_match('#^/admin/#', $uri) || preg_match('#^/admin$#', $uri))
{
	if (!$site->siteid)
	{
		$site = site_sites_WdHooks::find_by_request(array('REQUEST_PATH' => '/', 'HTTP_HOST' => $_SERVER['HTTP_HOST']));

		if ($site->path)
		{
			header('Location: ' . $site->path . $uri);

			exit;
		}
	}

	require dirname(__FILE__) . '/admin.php';
}