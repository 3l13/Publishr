<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

$publishr_root = dirname(__FILE__) . '/publishr';

if (preg_match('#/admin/?#', $_SERVER['REQUEST_URI']))
{
	require_once $publishr_root . '/admin.php';

	exit;
}

require_once 'user-access.php';
require_once $publishr_root . '/startup.php';
require_once 'user-startup.php';

$publisher = WdPublisher::getSingleton();
$publisher->run();