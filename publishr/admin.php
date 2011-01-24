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

$document = new WdPDocument();

require 'includes/route.php';

#
# document
#

$document_time_start = microtime(true);

$document->css->add('public/css/reset.css', -250);
$document->css->add('public/css/base.css', -200);
$document->css->add('public/css/input.css', -190);

$document->js->add('public/js/mootools-core.js', -200);
$document->js->add('public/js/mootools-more.js', -200);
$document->js->add('framework/wdcore/wdcore.js', -190);
$document->js->add('public/js/spinner.js', -190);
$document->js->add('public/js/publisher.js', -190);
$document->js->add('public/js/initializer.js', 1000);

$rc = (string) $document;

#
# statistics
#

$elapsed_time = microtime(true) - $wddebug_time_reference;

$queries_count = 0;
$queries_stats = array();

foreach (WdDatabase::$stats['queries_by_connection'] as $connection => $count)
{
	$queries_count += $count;
	$queries_stats[] = $connection . ': ' . $count;
}

$rc .= PHP_EOL . PHP_EOL . '<!-- ' . t
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

echo $rc;