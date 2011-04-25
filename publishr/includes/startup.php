<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

define('PUBLISHR_ROOT', dirname(dirname(__FILE__)));

require_once PUBLISHR_ROOT . '/framework/wdcore/wdcore.php';
require_once 'wdpcore.php';

$wddebug_time_reference = microtime(true);

if ($_SERVER['QUERY_STRING'])
{
	$_SERVER['REQUEST_PATH'] = substr($_SERVER['REQUEST_URI'], 0, -1 - strlen($_SERVER['QUERY_STRING']));
}
else
{
	$_SERVER['REQUEST_PATH'] = $_SERVER['REQUEST_URI'];
}

$core = WdPCore::get_instance();

//wd_log_time('core created');

$core->run();

//wd_log_time('core is running');