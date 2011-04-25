<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once 'publishr/includes/startup.php';

require_once 'user-access.php';
require_once 'publishr/startup.php';
require_once 'user-startup.php';

$publisher = WdPublisher::getSingleton();
$publisher->run();