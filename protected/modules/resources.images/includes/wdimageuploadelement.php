<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class WdImageUploadElement extends WdFileUploadElement
{
	protected function preview($path)
	{
		$w = $this->w;
		$h = $this->h;

		$url = WdOperation::encode
		(
			'thumbnailer', 'get', array
			(
				'src' => $path,
				'w' => $w,
				'h' => $h,
				'format' => 'jpeg',
				'quality' => 90,
				'background' => 'silver,white,medium',
				'uniqid' => uniqid()
			),

			'r'
		);

		$img = new WdElement
		(
			'img', array
			(
				'src' => $url,
				'width' => $w,
				'height' => $h,
				'alt' => ''
			)
		);

		$repository = WdCore::getConfig('repository.temp');

		if (strpos($path, $repository) === 0)
		{
			return $img;
		}

		return '<a href="' . $path . '&amp;uniqid=' . uniqid() . '" rel="lightbox">' . $img . '</a>';
	}

	protected function details($path)
	{
		#
		# extends document
		#

		global $document;

		$document->js->add('../public/slimbox.js');
		$document->css->add('../public/slimbox.css');

		// FIXME: should be "wdimageuploadelement.css" instead of "edit.css"

		$document->css->add('../public/edit.css');

		#
		#
		#

		$path = $this->get('value');

		list($entry_width, $entry_height) = getimagesize($_SERVER['DOCUMENT_ROOT'] . $path);

		$w = $entry_width;
		$h = $entry_height;

		#
		# if the image is larger then the thumbnail dimensions, we resize the image using
		# the "surface" mode.
		#

		$resized = false;

		if (($w * $h) > (resources_images_WdModule::THUMBNAIL_WIDTH * resources_images_WdModule::THUMBNAIL_HEIGHT))
		{
			$resized = true;

			$ratio = sqrt($w * $h);

			$w = round($w / $ratio * resources_images_WdModule::THUMBNAIL_WIDTH);
			$h = round($h / $ratio * resources_images_WdModule::THUMBNAIL_HEIGHT);
		}

		$this->w = $w;
		$this->h = $h;

		#
		# infos
		#

		$details = array
		(
			'<span title="Path: ' . $path . '">' . basename($path) . '</span>',
			t('Image size: \1&times;\2px', array($entry_width, $entry_height))
		);

		if (($entry_width != $w) || ($entry_height != $h))
		{
			$details[] = t('Display ratio: :ratio%', array(':ratio' => round(($w * $h) / ($entry_width * $entry_height) * 100)));
		}
		else
		{
			$details[] = t('Displayed as is');
		}

		$details[] = wd_format_size(filesize($_SERVER['DOCUMENT_ROOT'] . $path));

		return $details;
	}
}