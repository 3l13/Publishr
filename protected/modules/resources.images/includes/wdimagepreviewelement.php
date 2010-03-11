<?php

class WdImagePreviewElement extends WdElement
{
	public function __construct($tags, $dummy=null)
	{
		parent::__construct
		(
			'div', $tags + array
			(
				'class' => 'wd-filepreview wd-imagepreview'
			)
		);

		global $document;

		$document->addStyleSheet('../public/wdimagepreview.css');
	}

	protected function getInnerHTML()
	{
		$rc = parent::getInnerHTML();

		$path = $this->getTag('value');

		$details = $this->details($path);

		if ($details)
		{
			$rc .= '<ul class="details">';

			foreach ($details as $detail)
			{
				$rc .= '<li>' . $detail . '</li>';
			}

			$rc .= '</ul>';
		}

		$preview = $this->preview($path);

		if ($preview)
		{
			$rc .= '<div class="preview">';
			$rc .= $preview;
			$rc .= '</div>';
		}

		return $rc;
	}

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

			true
		);

		$rc = '<a href="' . $path . '&amp;uniqid=' . uniqid() . '" rel="lightbox">';

		$rc .= new WdElement
		(
			'img', array
			(
				'src' => $url,
				'width' => $w,
				'height' => $h,
				'alt' => ''
			)
		);

		$rc .= '</a>';

		return $rc;
	}

	protected function details($path)
	{
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

		$details[] = WdResume::size_callback
		(
			(object) array
			(
				File::SIZE => filesize($_SERVER['DOCUMENT_ROOT'] . $path)
			),

			File::SIZE, null, null
		);

		return $details;
	}
}