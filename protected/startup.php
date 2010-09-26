<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

/**
 * This startup file is used to launch the application. The framework shall already be running.
 */

#
# create the document
#

$document = new WdPDocument();

require 'includes/route.php';