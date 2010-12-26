<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

define('PUBLISHR_ROOT', dirname(__FILE__) . '/publishr');

if (substr($_SERVER['REQUEST_URI'], 0, 7) == '/admin/')
{
	require_once PUBLISHR_ROOT . '/admin.php';

	exit;
}

require_once 'user-access.php';
require_once PUBLISHR_ROOT . '/startup.php';
require_once 'user-startup.php';

$publisher = WdPublisher::getSingleton();
$publisher->run();