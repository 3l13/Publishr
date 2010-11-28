<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class site_pages_WdHooks
{
	/**
	 * The callback is called when the `resources.files.path.change` is triggered, allowing us to
	 * update content to the changed path of resource.
	 *
	 * @param WdEvent $event
	 */

	static public function resources_files_path_change(WdEvent $event)
	{
		global $core;

		try
		{
			$model = $core->getModule('site.pages')->model('contents');
		}
		catch (Exception $e)
		{
			return;
		}

		$model->execute
		(
			'UPDATE {self} SET content = REPLACE(content, ?, ?)', $event->path
		);
	}

	/**
	 * The callback is called when the `site.pages.url.change` event is triggered, allowing us to
	 * update content to the changed url of the page.
	 *
	 * Note that *only* url within something that looks like a HTML attribute are updated, the
	 * matching pattern is ~="<url>("|/)~
	 *
	 * @param WdEvent $event
	 */

	static public function site_pages_url_change(WdEvent $event)
	{
		global $core;

		try
		{
			$model = $core->models['site.pages/contents'];
		}
		catch (Exception $e)
		{
			return;
		}

		list($old, $new) = $event->path;

		$entries = $model->loadAll('WHERE content LIKE ?', array('%' . $old . '%'));

		foreach ($entries as $entry)
		{
			$content = $entry->content;

			$content = preg_replace('~=\"' . preg_quote($old, '~') . '(\"|\/)~', '="' . $new . '$1', $contents);

			if ($content == $entry->content)
			{
				continue;
			}

			$model->execute
			(
				'UPDATE {self} SET content = ? WHERE pageid = ? AND contentid = ?', array
				(
					$content, $entry->pageid, $entry->contentid
				)
			);
		}
	}
}