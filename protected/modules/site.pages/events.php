<?php

class site_pages_WdEvents
{
	/**
	 * The callback is called when the `resources.files.path.change` is triggered, allowing us to
	 * update contents to the changed path of resource.
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
			'UPDATE {self} SET contents = REPLACE(contents, ?, ?)', $event->path
		);
	}

	/**
	 * The callback is called when the `site.pages.url.change` event is triggered, allowing us to
	 * update contents to the changed url of the page.
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
			$model = $core->getModule('site.pages')->model('contents');
		}
		catch (Exception $e)
		{
			return;
		}

		list($old, $new) = $event->path;

		$entries = $model->loadAll('WHERE contents LIKE ?', array('%' . $old . '%'));

		foreach ($entries as $entry)
		{
			$contents = $entry->contents;

			$contents = preg_replace('~=\"' . preg_quote($old, '~') . '(\"|\/)~', '="' . $new . '$1', $contents);

			if ($contents == $entry->contents)
			{
				continue;
			}

			$model->execute
			(
				'UPDATE {self} SET contents = ? WHERE pageid = ? AND contentsid = ?', array
				(
					$contents, $entry->pageid, $entry->contentsid
				)
			);
		}
	}

	/**
	 * The callback is called when the `alter.block.config` event is triggered, allowing us to
	 * extend the config block with two elements to set the _view_ and _head_ urls.
	 *
	 * @param WdEvent $ev
	 */

	static public function alter_block_config(WdEvent $ev)
	{
		global $core;

		if (!($ev->module instanceof system_nodes_WdModule) || ($ev->module instanceof site_pages_WdModule))
		{
			return;
		}

		if (!$core->hasModule('site.pages'))
		{
			return;
		}

		// TODO-20100108: get the `base` from the Event

		$base = wd_camelCase((string) $ev->module, '.');

		$ev->tags = wd_array_merge_recursive
		(
			$ev->tags, array
			(
				WdElement::T_GROUPS => array
				(
					'url' => array
					(
						'weight' => -10,
						'title' => 'Pages'
					)
				),

				WdElement::T_CHILDREN => array
				(
					$base . '[url][view]' => new WdPageSelectorElement
					(
						'select', array
						(
							WdForm::T_LABEL => 'Page d\'affichage',
							WdElement::T_DESCRIPTION => "La <em>page d'affichage</em> est destinée à
							l'affichage d'une entrée unique. Une URL unique sera crée par la
							page en fonction des paramètres de l'entrée.",
							WdElement::T_GROUP => 'url',
							WdElement::T_WEIGHT => -10
						)
					),

					$base . '[url][head]' => new WdPageSelectorElement
					(
						'select', array
						(
							WdForm::T_LABEL => 'Page de liste',
							WdElement::T_DESCRIPTION => "La <em>page de liste</em> est destinée à
							l'affichage en liste des entrées.",
							WdElement::T_GROUP => 'url',
							WdElement::T_WEIGHT => -9
						)
					)
				)
			)
		);
	}
}