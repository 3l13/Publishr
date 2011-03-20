<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class site_pages__save_WdOperation extends system_nodes__save_WdOperation
{
	protected function __get_properties()
	{
		global $core;

		$properties = parent::__get_properties();

		if (!$this->key)
		{
			$siteid = $core->working_site_id;
			$properties[Node::SITEID] = $siteid;

			if (empty($properties[Page::WEIGHT]))
			{
				$weight = $this->module->model
				->where('siteid = ? AND parentid = ?', $siteid, isset($properties[Page::PARENTID]) ? $properties[Page::PARENTID] : 0)
				->maximum('weight');

				$properties[Page::WEIGHT] = ($weight === null) ? 0 : $weight + 1;
			}
		}

		if (isset($properties[Page::LABEL]))
		{
			$properties[Page::LABEL] = trim($properties[Page::LABEL]);
		}

		if (isset($properties[Page::PATTERN]))
		{
			$properties[Page::PATTERN] = trim($properties[Page::PATTERN]);
		}

		return $properties;
	}

	protected function process()
	{
		global $core;

		$record = null;
		$oldurl = null;

		if ($this->record)
		{
			$record = $this->record;
			$pattern = $record->url_pattern;

			if (!WdRoute::is_pattern($pattern))
			{
				$oldurl = $pattern;
			}
		}

		WdEvent::fire
		(
			'site.pages.save:before', array
			(
				'target' => $this,
				'operation' => $this
			)
		);

		$rc = parent::process();
		$nid = $rc['key'];

		#
		# update contents
		#

		$content_ids = array();
		$contents_model = $this->module->model('contents');

		if (isset($this->params['contents']))
		{
			$contents = $this->params['contents'];
			$content_ids = array_keys($contents);

			foreach ($contents as $content_id => $values)
			{
				$editor = $values['editor'];
				$editor_class = $editor . '_WdEditorElement';
				$content = call_user_func(array($editor_class, 'to_content'), $values, $content_id, $nid);

				#
				# if the content is made of an array of values, the values are serialized in JSON.
				#

				if (is_array($content))
				{
					$content = json_encode($content);
				}

				#
				# if there is no content, the content object is deleted
				#

				if (!$content)
				{
					$contents_model->where(array('pageid' => $nid, 'contentid' => $content_id))->delete();

					continue;
				}

				$values['content'] = $content;

				$contents_model->insert
				(
					array
					(
						'pageid' => $nid,
						'contentid' => $content_id
					)

					+ $values,

					array
					(
						'on duplicate' => $values
					)
				);
			}
		}

		#
		# we delete possible remaining content for the page
		#

		$arr = $contents_model->find_by_pageid($nid);

		if ($content_ids)
		{
			$arr->where(array('!contentid' => $content_ids));
		}

		$arr->delete();

		#
		# trigger `site.pages.url.change` event
		#

		if ($record && $oldurl)
		{
			$record = $this->module->model[$nid];
			$newurl = $record->url;

			//wd_log('oldurl: \1, newurl: \2', array($oldurl, $newurl));

			if ($oldurl != $newurl)
			{
				WdEvent::fire
				(
					'site.pages.url.change', array
					(
						'path' => array
						(
							$oldurl,
							$newurl
						),

						'entry' => $record, // TODO-20101124: update listener to use `target`
						// TODO-20110105: rename 'entry' as 'record'
						'target' => $record,
						'module' => $this
					)
				);
			}
		}

		return $rc;
	}
}