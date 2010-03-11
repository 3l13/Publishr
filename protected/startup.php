<?php

/*
 *
 * This startup file is used to launch the application. The framework
 * shall already be running.
 *
 */

WdLocale::addPath(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'includes');

#
# create the document
#

require_once 'includes/wddocument.php';

$document = new WdPDocument();

require 'includes/route.php';