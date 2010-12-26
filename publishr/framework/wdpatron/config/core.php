<?php

#
# FIXME: not sure this is the best place, because if the config gets cached, the Patron function
# will not be defined.
#

require_once($root . 'includes/patron.php');

#
#
#

$includes_root = $root . 'includes' . DIRECTORY_SEPARATOR;

return array
(
	'autoload' => array
	(
		'WdHTMLParser' => $includes_root . 'wdhtmlparser.php',
		'WdPatron' => $includes_root . 'wdpatron.php',
		'WdTextHole' => $includes_root . 'wdtexthole.php',
		'Textmark_Parser' => $includes_root . 'textmark.php',

		'patron_WdMarkup' => $includes_root . 'patron_wdmarkup.php',
		'patron_markups_WdHooks' => $includes_root . 'markups.php',

		'patron_native_WdMarkups' => $root . 'markups/native.markups.php',
		'patron_feed_WdMarkups' => $root . 'markups/feed.markups.php',
		'patron_elements_WdMarkups' => $root . 'markups/elements.markups.php'
	)
);