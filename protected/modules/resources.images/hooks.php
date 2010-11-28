<?php

class resources_images_WdHooks
{
	static private $light_box_added = false;

	static public function textmark_images_reference(array $args, Textmark_Parser $textmark, array $matches)
	{
		static $model;

		if (!$model)
		{
			global $core;

			$model = $core->models['resources.images'];
		}

		$align = $matches[2];
		$alt = $matches[3];
		$id = $matches[4];

		# for shortcut links like ![this][].

		if (!$id)
		{
			$id = $alt;
		}

		$entry = $model->loadRange
		(
			0, 1, 'WHERE (nid = ? OR slug = ? OR title = ?) ORDER BY created DESC', array
			(
				(int) $id, $id, $id
			)
		)
		->fetchAndClose();

		if (!$entry)
		{
			$matches[2] = $matches[3];
			$matches[3] = $matches[4];

			WdDebug::trigger('should call standard one !');

			//return parent::_doImages_reference_callback($matches);

			return;
		}

		$src = $entry->path;
		$w = $entry->width;
		$h = $entry->height;

		$light_src = null;

		if ($w > 600)
		{
			$w = 600;
			$h = null;

			$light_src = $src;

			$src = '/api/resources.images/' . $entry->nid . '/thumbnail?' . http_build_query
			(
				array
				(
					'w' => $w,
					'method' => 'fixed-width',
					'quality' => 80
				)
			);
		}

		$params = array
		(
			'src' => $src,
			'alt' => $alt,
			'width' => $w,
			'height' => $h
		);

		if ($align)
		{
			switch ($align)
			{
				case '<': $align = 'left'; break;
				case '=':
				case '|': $align = 'middle'; break;
				case '>': $align = 'right'; break;
			}

			$params['align'] = $align;
		}

		$rc = new WdElement('img', $params);

		if ($light_src)
		{
			// TODO-20101107: Well, this doesn't work if the content is cached...
			// maybe we could parse the published result, search for "lightbox" and '/repository/files"
			// and add our things then.

			if (!self::$light_box_added)
			{
				global $document;

				$document->css->add('public/slimbox.css');
				$document->js->add('public/slimbox.js');

				self::$light_box_added = true;
			}

			$rc = '<a href="' . $light_src . '" rel="lightbox[]">' . $rc . '</a>';
		}

		return $rc;
	}
}