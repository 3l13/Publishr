<?php

//echo 'entering: ' . __FILE__ . '<br /><br />';

#
# check installation
#

//if (is_readable(dirname(__FILE__) . '/config.php'))
{
	require_once dirname(__FILE__) . '/includes/startup.php';

	return;
}

#
# wdpublisher is not installed, we create a page inviting the user
# to proceed to the installation
#

define('WDPUBLISHER_ROOT', dirname(__FILE__) . '/');
define('WDCORE_ROOT', dirname(WDPUBLISHER_ROOT) . '/wdcore/');

require_once WDCORE_ROOT . 'wdlocale.php';

$locale = new WdLocale();

define('WDPUBLISHER_URL', str_replace(DIRECTORY_SEPARATOR, '/', substr(dirname(__FILE__), strlen($_SERVER['DOCUMENT_ROOT']) - 1)) . '/' );

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>WdPublisher</title>
<link href="<?php echo WDPUBLISHER_URL ?>css/base.css" type="text/css" rel="stylesheet" />
<link href="<?php echo WDPUBLISHER_URL ?>css/install.css" type="text/css" rel="stylesheet" />
</head>

<body>

<div id="navigation">
<h1>WdPublisher</h1>
</div>

<div id="main" class="large">

<h1><span>Wd</span>Publisher</h1>

<p><?php echo l('WdPublisher is not installed yet.') ?></p>

<p><a href="<?php echo WDPUBLISHER_URL ?>" class="go_install"><?php echo l('Install it now !') ?></a></p>

</div>

</body>
</html><?php

exit;