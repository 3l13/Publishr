<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class resources_images_WdManagerGallery extends resources_images_WdManager
{
	public function __construct($module, array $tags=array())
	{
		parent::__construct
		(
			$module, $tags + array
			(
				self::T_BLOCK => 'gallery'
			)
		);

		global $document;

		$document->css->add('public/gallery.css');
	}

	protected function parseOptions($name)
	{
		return parent::parseOptions($name . '/gallery');
	}

	protected function getContents()
	{
		$size = isset($_GET['size']) ? $_GET['size'] : 128;
		$size = min($size, max($size, 16), 512);

		$module_id = (string) $this->module;

		$display_by = $this->tags[self::BY];

		$rc = PHP_EOL . '<tr id="gallery"><td colspan="' . (count($this->columns) + 1) . '">';

		$template = <<<EOT
<div class="thumbnailer-wrapper" style="width: #{size}px;">
<a href="#{path}" rel="lightbox[]">#{img}</a>
<div class="key">#{key}</div>
#{label}
</div>
EOT;

		global $app;

		$user = $app->user;

		foreach ($this->entries as $entry)
		{
			$title = $entry->title;
			$path = $entry->path;

			$key = null;

			$label = new WdElement
			(
				'a', array
				(
					WdElement::T_INNER_HTML => wd_entities($title),

					'class' => 'edit',
					'title' => t('Edit this item'),
					'href' => WdRoute::encode('/' . $module_id . '/' . $entry->nid . '/edit')
				)
			);

			if ($size >= 64)
			{
				if ($user->hasOwnership($module_id, $entry))
				{
					$this->checkboxes++;

					$key = new WdElement
					(
						WdElement::E_CHECKBOX, array
						(
							'class' => 'key',
							'title' => t('Toggle selection for entry #\1', array($entry->nid)),
							'value' => $entry->nid
						)
					);
				}

				if ($display_by == 'modified')
				{
					$label .= ' <span class="small">(' . $this->get_cell_datetime($entry, 'modified') . ')</span>';
				}
				else if ($display_by == 'size')
				{
					$label .= ' <span class="small">(' . self::size_callback($entry, 'size') . ')</span>';
				}
			}

			$rc .= strtr
			(
				$template, array
				(
					'#{size}' => $size,
					'#{path}' => $path,
					'#{img}' => new WdElement
					(
						'img', array
						(
							'src' => WdOperation::encode
							(
								'thumbnailer', 'get', array
								(
									'src' => $path,
									'w' => $size,
									'h' => $size,
									'method' => 'constrained',
									'interlace' => true,
									'quality' => 90
								),

								true
							),

							'title' => $title,
							'alt' => $title
						)
					),

					'#{key}' => $key,
					'#{label}' => $label
				)
			);
		}

		$rc .= '</td></tr>' . PHP_EOL;

		return $rc;
	}
}