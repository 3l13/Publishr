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
 * @property string $repository Path to the thumbnails repository.
 */
class thumbnailer_WdModule extends WdModule
{
	/**
	 * Getter for the $repository magic property.
	 */
	protected function __get_repository()
	{
		global $core;

		return $core->config['repository.cache'] . '/thumbnailer';
	}

	/**
	 * Creates the repository folder where generated thumbnails are saved.
	 *
	 * @see WdModule::install()
	 */
	public function install()
	{
		$repository = $this->repository;

		// TODO: use is_writable() to know if we can create the repository folder
		// FIXME: 0777 ? really ?

		$rc = mkdir($_SERVER['DOCUMENT_ROOT'] . $repository, 0777, true);

		if (!$rc)
		{
			wd_log_error('Unable to create folder %path', array('%path' => $repository));
		}

		return $rc;
	}

	/**
	 * Check if the repository folder has been created.
	 *
	 * @see WdModule::is_installed()
	 */
	public function is_installed()
	{
		return is_dir($_SERVER['DOCUMENT_ROOT'] . $this->repository);
	}
}