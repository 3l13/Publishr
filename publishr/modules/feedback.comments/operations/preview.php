<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Gives the user a visual feedback of the message he's typing.
 */
class feedback_comments__preview_WdOperation extends WdOperation
{
	protected function validate()
	{
		return !empty($this->params['contents']);
	}

	protected function process()
	{
		$contents = $this->params['contents'];
		$contents = Textmark_Parser::parse($contents);

		return WdKses::sanitizeComment($contents);
	}
}