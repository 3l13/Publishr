<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class WdConfigException extends WdException
{
	public function __construct($message, array $params=array(), $code=500)
	{
		if ($message instanceof WdModule)
		{
			$params += array
			(
				':module_id' => (string) $message,
				'!title' => (string) $message
			);

			$message = 'You need to <a href="/admin/:module_id/config">configure the <q>!title</q> module</a>.';
		}

		parent::__construct($message, $params, $code);
	}

	public function __toString()
	{
		parent::__toString();

		if ($this->code && !headers_sent())
		{
			header('HTTP/1.0 ' . $this->code . ' ' . $this->title);
		}

		$rc  = '<code class="exception">';
		$rc .= '<strong>' . $this->title . ', with the following message:</strong><br /><br />';
		$rc .= $this->getMessage() . '<br />';
		$rc .= '</code>';

		return $rc;
	}
}