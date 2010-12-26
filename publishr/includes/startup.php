<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

define('WDCORE_ROOT', PUBLISHR_ROOT . '/framework/wdcore/');

require_once PUBLISHR_ROOT . '/framework/wdcore/wdcore.php';
require_once 'wdpcore.php';

$wddebug_time_reference = microtime(true);

$core = new WdPCore();

//wd_log_time('core created');

$core->run();

//wd_log_time('core is running');