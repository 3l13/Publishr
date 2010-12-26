<?php

/*
 * This is the user's access control script.
 *
 * One can add rules and conditions for accessing a website. The following example shows how one
 * can issue a 503 for visitors outside of the local network.
 *
 */

$remote_addr = $_SERVER['REMOTE_ADDR'];

if (0 && substr($remote_addr, 0, 7) != '192.168')
{
	header('HTTP/1.1 503 Service Temporarily Unavailable');

	echo file_get_contents('protected/all/templates/503.html', true);

	exit;
}