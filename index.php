<?php

require_once 'user-access.php';
require_once dirname(__FILE__) . '/publishr/startup.php';
require_once 'user-startup.php';

$publisher = WdPublisher::getSingleton();
$publisher->run();