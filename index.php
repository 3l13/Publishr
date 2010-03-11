<?php

require_once 'startup.php';
require_once dirname(__FILE__) . '/protected/startup.php';

#
# create document
#

$document_time_start = microtime(true);

$document->title = 'WdPublisher';

$document->addStyleSheet('public/css/reset.css', 250);
$document->addStyleSheet('public/css/base.css', 200);
$document->addStyleSheet('public/css/input.css', 190);

$document->addJavaScript('public/js/mootools.js', 200);
$document->addJavaScript('public/js/mootools-more.js', 200);
$document->addJavaScript('public/js/spinner.js', 200);

$document->addJavaScript('public/js/publisher.js', 190);

$rc = (string) $document;

$document_time = microtime(true) - $document_time_start;

#
#
#

$queriesCount = 0;
$queriesStats = array();

foreach ($_SESSION['stats']['queries'] as $name => $count)
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