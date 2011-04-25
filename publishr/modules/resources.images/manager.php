<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class resources_images_WdManager extends resources_files_WdManager
{
	public function __construct($module, array $tags=array())
	{
		parent::__construct($module, $tags);

		global $document;

		$document->js->add('public/slimbox.js');
		$document->css->add('public/slimbox.css');

		$document->js->add('public/manage.js');
		$document->css->add('public/manage.css');
	}

	protected function columns()
	{
		$columns = parent::columns() + array
		(
			'surface' => array
			(
				'label' => 'Dimensions',
				'class' => 'size'
			)
		);

		$columns['title']['class'] = 'thumbnail';

		return $columns;
	}

	/**
	 * Alters the range query to support the "surface" virtual property.
	 *
	 * @see WdResume::alter_range_query()
	 */
	protected function alter_range_query(WdActiveRecordQuery $query, array $options)
	{
		if (isset($options['order']['surface']))
		{
			$query->order('(width * height) ' . ($options['order']['surface'] < 0 ? 'DESC' : ''));

			$options['order'] = array();
		}

		return parent::alter_range_query($query, $options);
	}

	protected function render_cell_title(system_nodes_WdActiveRecord $record, $property)
	{
		$path = $record->path;

		$rc  = '<a href="' . wd_entities($path) . '" rel="lightbox[]">';
		$rc .= '<img alt="' . basename($path) . '"';
		$rc .= ' src="' . wd_entities($record->thumbnail('$icon')) . '"';
		$rc .= ' width="' .  resources_images_WdModule::ICON_WIDTH . '"';
		$rc .= ' height="' . resources_images_WdModule::ICON_HEIGHT . '"';
		$rc .= ' />';
		$rc .= '<input type="hidden" value="' . wd_entities($record->thumbnail('$popup')) . '" />';
		$rc .= '</a>';

		$rc .= parent::render_cell_title($record, $property);

		return $rc;
	}

	protected function render_cell_surface($record)
	{
		return $record->width . '&times;' . $record->height . '&nbsp;px';
	}
}