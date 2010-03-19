<?php

#
# Roots
#

define('WDPUBLISHER_ROOT', dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR);

define('WD_ROOT', dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR);
define('WDCORE_ROOT', WD_ROOT . 'wdcore' . DIRECTORY_SEPARATOR);
define('WDPATRON_ROOT', WD_ROOT . 'wdpatron' . DIRECTORY_SEPARATOR);
define('WDELEMENTS_ROOT', WD_ROOT . 'wdelements' . DIRECTORY_SEPARATOR);

#
# WdPublisher
#

define('WDPUBLISHER_URL', '/$wd/wdpublisher/'); // FIXME-20100211: this should be computed

#
# setup and run the core
#

require_once WDPUBLISHER_ROOT . 'includes/wdpcore.php';

wd_log_time('init');

WdCore::addConfig(dirname(WDPUBLISHER_ROOT) . DIRECTORY_SEPARATOR . 'wdelements');
WdCore::addConfig(dirname(WDPUBLISHER_ROOT) . DIRECTORY_SEPARATOR . 'wdpatron');
WdCore::addConfig(WDPUBLISHER_ROOT . 'protected');

#
#
#

$_user_root = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'protected';

if (is_dir($_user_root))
{
	WdCore::addConfig($_user_root);
}

$core = new WdPCore();

#
# load user i18n catalogs
#

if (is_dir($_user_root))
{
	WdLocale::addPath($_user_root);
}

$core->run();