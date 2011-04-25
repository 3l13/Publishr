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
 * Extends the save_WdOperation class to add save mode (continue, list, new).
 */
class publishr_save_WdOperation extends save_WdOperation
{
	const MODE = '#operation-save-mode';
	const MODE_CONTINUE = 'continue';
	const MODE_LIST = 'list';
	const MODE_NEW = 'new';

	/**
	 * @var string The save mode of the operation, one of the following: MODE_CONTINUE, MODE_LIST
	 * or MODE_NEW.
	 */
	protected $mode;

	/**
	 * Update the session save mode according to the save mode defined in the operation parameters.
	 *
	 * @see WdOperation::__invoke()
	 */
	public function __invoke()
	{
		global $core;

		$params = &$this->params;
		$this->mode = $mode = isset($params[self::MODE]) ? $params[self::MODE] : null;

		if ($mode)
		{
			$core->session->wdpmodule[self::MODE][(string) $this->module] = $mode;
		}

		return parent::__invoke();
	}

	/**
	 * Changes the operation location depending on the save mode.
	 *
	 * - list: The constructor index location.
	 * - continue: The record edit location.
	 * - new: The edit location for new records.
	 *
	 * @see save_WdOperation::process()
	 */
	protected function process()
	{
		global $core;

		$rc = parent::process();
		$mode = $this->mode;

		if ($mode)
		{
			$route = '/admin/' . (string) $this->module;

			switch ($mode)
			{
				case self::MODE_CONTINUE:
				{
					$route .= '/' . $rc['key'] . '/edit';
				}
				break;

				case self::MODE_NEW:
				{
					$route .= '/create';
				}
				break;
			}

			$this->location = $core->site->path . $route;
		}

		return $rc;
	}
}