<?php

class resources_images_WdManager extends resources_files_WdManager
{
	public function __construct($module, array $tags=array())
	{
		parent::__construct($module, $tags);

		global $document;

		$document->addJavascript('public/slimbox.js');
		$document->addStyleSheet('public/slimbox.css');
		$document->addJavascript('public/manage.js');
		$document->addStyleSheet('public/manage.css');
	}

	protected function columns()
	{
		$columns = parent::columns() + array
		(
			'surface' => array
			(
				self::COLUMN_LABEL => 'Dimensions',
				self::COLUMN_CLASS => 'size'
			)
		);

		$columns['title'][self::COLUMN_CLASS] = 'thumbnail';

		return $columns;
	}

	protected function get_cell_title($entry, $tag)
	{
		$path = $entry->path;

		$icon = WdOperation::encode
		(
			'thumbnailer', 'get', array
			(
				'src' => $path,
				'version' => '$icon'
			),

			true
		);

		$thumbnail = WdOperation::encode
		(
			'thumbnailer', 'get', array
			(
				'src' => $path,
				'version' => '$popup'
			),

			true
		);

		$rc  = '<a href="' . $path . '" rel="lightbox[]">';
		$rc .= '<img alt="' . basename($path) . '"';
		$rc .= ' src="' . $icon . '"';
		$rc .= ' width="' .  resources_images_WdModule::ICON_WIDTH . '"';
		$rc .= ' height="' . resources_images_WdModule::ICON_HEIGHT . '"';
		$rc .= ' />';
		$rc .= '<input type="hidden" value="' . $thumbnail . '" />';
		$rc .= '</a>';

		$rc .= parent::get_cell_title($entry, $tag);

		return $rc;
	}

	protected function get_cell_surface($entry)
	{
		return $entry->width . '&times;' . $entry->height . ' px';
	}
}