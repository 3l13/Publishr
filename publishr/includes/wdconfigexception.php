<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class WdConfigException extends WdException
{
	public function __construct($message, array $params=array(), $code=500)
	{
		global $core;

		if ($message instanceof WdModule)
		{
			$params += array
			(
				':module_id' => (string) $message,
				'!title' => (string) $message
			);

			$message = 'You need to <a href="' . $core->site->path . '/admin/:module_id/config">configure the <q>!title</q> module</a>.';
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