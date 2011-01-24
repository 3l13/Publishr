<?php

/*
 * This is the user's startup sequence, which gets evaluated before any website page is published.
 *
 * In the folowing example, we override the global document object and add some scripts that will
 * be used by pages.
 */

$document = new WdDocument();

$document->js->add('/publishr/public/js/mootools-core.js');
$document->js->add('/publishr/public/js/mootools-more.js');
$document->js->add('/publishr/framework/wdcore/wdcore.js');