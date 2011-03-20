<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class resources_videos_WdManager extends resources_files_WdManager
{
	public function __construct($module, array $tags=array())
	{
		parent::__construct($module, $tags);

		global $document;

		$document->css->add('public/manage.css');
	}

	protected function columns()
	{
		return parent::columns() + array
		(
			'poster' => array
			(
				self::COLUMN_LABEL => null,
				self::COLUMN_CLASS => 'poster'
			),

			'surface' => array
			(
				self::COLUMN_LABEL => 'Dimensions',
				self::COLUMN_CLASS => 'size'
			),

			'duration' => array
			(
				self::COLUMN_LABEL => 'Durée',
				self::COLUMN_CLASS => 'size'
			)
		);
	}

	protected function get_cell_surface($entry)
	{
		return $entry->width . '&times;' . $entry->height . ' px';
	}

	protected function get_cell_duration($entry, $tag)
	{
		$duration = $entry->$tag;

		if ($duration > 60 * 60)
		{

		}
		else if ($duration > 60)
		{
			return round($duration / 60) . ' mins';
		}
		else
		{
			return round($duration) . ' secs';
		}
	}

	protected function get_cell_poster($entry)
	{
		$poster = $entry->poster;

		if (!$poster)
		{
			return;
		}

		// TODO-20110108: should use $poster->thumbnail()

		return new WdElement
		(
			'img', array
			(
				'src' => WdOperation::encode
				(
					'thumbnailer/get', array
					(
						'src' => $poster->path,
						'version' => '$icon'
					)
				),

				'alt' => ''
			)
		);
	}
}