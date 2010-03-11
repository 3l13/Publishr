<?php

echo 'entering ' . __FILE__ . ' without a clue<br />';

require_once WDPUBLISHER_ROOT . 'includes/wdpelement.php';

function _welcomeBlocks()
{
	global $core;

	$rc = NULL;
	$blocks = array();

	foreach ($core->descriptors as $id => $descriptor)
	{
		if (empty($descriptor[WdModule::T_BLOCKS]['welcome']))
		{
			continue;
		}

		$blocks[$id] = $descriptor[WdModule::T_BLOCKS]['welcome'];
	}

	arsort($blocks);

	foreach ($blocks as $id => $priority)
	{
		$module = $core->getModule($id);

		$block = $module->getBlock('welcome');

		$rc .= '<h2>' . t($block['title']) . '</h2>';
		$rc .= '<div class="fold">';
		$rc .= '<div class="fold-contents">' . $block['element'] . '</div>';
		$rc .= '</div>';
	}

	return $rc;
}

$rc = '<h1>' . t('Welcome to the <span>Wd</span>Publisher')  . '</h1>';
$rc .= '<div id="welcome">';
$rc .= _welcomeBlocks();
$rc .= '</div>';

$document->addStyleSheet('welcome.css', 0, dirname(__FILE__));
$document->AddJavaScript('welcome.js', 0, dirname(__FILE__));

$document->addToBlock($rc, 'main');

?>