<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

require_once 'startup.php';

$core->document = $document = new WdPDocument();

require 'includes/route.php';

$document->css->add('public/css/reset.css', -250);
$document->css->add('public/css/base.css', -200);
$document->css->add('public/css/input.css', -190);

$document->js->add('public/js/mootools-core.js', -200);
$document->js->add('public/js/mootools-more.js', -200);
$document->js->add('framework/wdcore/wdcore.js', -190);
$document->js->add('public/js/widget.js', -185);
$document->js->add('public/js/spinner.js', -180);
$document->js->add('public/js/publisher.js', -180);

echo $document;

#
# statistics
#

$elapsed_time = microtime(true) - $wddebug_time_reference;

$queries_count = 0;
$queries_stats = array();

foreach ($core->connections as $id => $connection)
{
	$count = $connection->queries_count;
	$queries_count += $count;
	$queries_stats[] = $id . ': ' . $count;
}

echo PHP_EOL . PHP_EOL . '<!-- ' . t
(
	'publishr - time: :elapsed sec, memory usage: :memory-usage (peak: :memory-peak), queries: :queries-count (:queries-details)', array
	(
		':elapsed' => number_format($elapsed_time, 3, '.', ''),
		':memory-usage' => memory_get_usage(),
		':memory-peak' => memory_get_peak_usage(),
		':queries-count' => $queries_count,
		':queries-details' => implode(', ', $queries_stats)
	)
)

. ' -->';