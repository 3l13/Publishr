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
				WdResume::COLUMN_LABEL => 'Dimensions',
				WdResume::COLUMN_HOOK => array(__CLASS__, 'surface_callback'),
				WdResume::COLUMN_CLASS => 'size'
			)
		);

		$columns['title'][WdResume::COLUMN_CLASS] = 'thumbnail';

		return $columns;
	}

	protected function get_cell_title($entry, $tag)
	{
		$path = $entry->path;

		#
		# we use 'resources.images' instead of 'this' to avoid problems with inheritence
		#

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

		//$rc .= WdResume::modify_callback($entry, $tag, $this);

		$rc .= parent::get_cell_title($entry, $tag);

		return $rc;
	}

	protected function get_cell_surface($entry)
	{
		return $entry->width . '&times;' . $entry->height . ' px';
	}
}