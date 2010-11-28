<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

#
# Roots
#

define('WDPUBLISHER_ROOT', dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR);

define('WD_ROOT', dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR);
define('WDCORE_ROOT', WD_ROOT . 'wdcore' . DIRECTORY_SEPARATOR);
define('WDPATRON_ROOT', WD_ROOT . 'wdpatron' . DIRECTORY_SEPARATOR);
define('WDELEMENTS_ROOT', WD_ROOT . 'wdelements' . DIRECTORY_SEPARATOR);

#
# setup and run the core
#

require_once WDCORE_ROOT . 'wdcore.php';
require_once 'wdpcore.php';

$wddebug_time_reference = microtime(true);

$core = new WdPCore
(
	array
	(
		'paths' => array
		(
			'i18n' => array
			(
				$_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'protected'
			)
		)
	)
);

//wd_log_time('core created');

$core->run();

//wd_log_time('core is running');