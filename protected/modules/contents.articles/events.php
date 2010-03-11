<?php

class contents_articles_WdEvents
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
			$model = $core->getModule('contents.articles')->model();
		}
		catch (Exception $e)
		{
			return;
		}

		$model->execute
		(
			'UPDATE {self} SET contents = REPLACE(contents, ?, ?)', $event->path
		);
	}
}