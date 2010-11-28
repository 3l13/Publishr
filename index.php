<?php

require_once 'startup.php';
require_once dirname(__FILE__) . '/protected/startup.php';

#
# create document
#

$document_time_start = microtime(true);

$document->css->add('public/css/reset.css', -250);
$document->css->add('public/css/base.css', -200);
$document->css->add('public/css/input.css', -190);

$document->js->add('public/js/mootools-core.js', -200);
$document->js->add('public/js/mootools-more.js', -200);
$document->js->add('public/js/spinner.js', -190);
$document->js->add('public/js/publisher.js', -190);

$rc = (string) $document;

$document_time = microtime(true) - $document_time_start;

#
#
#

$queriesCount = 0;
$queriesStats = array();

foreach ($stats['queries'] as $name => $count)
{
	$queriesCount += $count;
	$queriesStats[] = $name . ': ' . $count;
}

$rc .= '<!-- ' . PHP_EOL . PHP_EOL . t
(
	'wdpublisher # time: :elapsed sec, memory usage :memory-usage (peak: :memory-peak), queries: :queries-count (:queries-details)', array
	(
		':elapsed' => number_format($document_time, 3, '\'', ''),
		':memory-usage' => memory_get_usage(),
		':memory-peak' => memory_get_peak_usage(),
		':queries-count' => $queriesCount,
		':queries-details' => implode(', ', $queriesStats)
	)
)

. ' -->';

echo $rc;