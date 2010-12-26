<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

// TODO-20101116: move this code to the "contents" module.

class contents_articles_WdHooks
{
	/**
	 * The callback is called when the `resources.files.path.change` is triggered, allowing us to
	 * update contents to the changed path of resources.
	 *
	 * @param WdEvent $event
	 */

	static public function resources_files_path_change(WdEvent $event)
	{
		global $core;

		try
		{
			$model = $core->models['contents.articles'];
		}
		catch (Exception $e)
		{
			return;
		}

		$model->execute
		(
			'UPDATE {self} SET body = REPLACE(body, ?, ?)', $event->path
		);
	}
}