<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class WdVideoUploadElement extends WdFileUploadElement
{
	public function __construct($tags, $dummy=null)
	{
		parent::__construct($tags, $dummy);

		global $document;

		$document->css->add('../public/wdvideouploadelement.css');
		$document->js->add('../public/wdvideouploadelement.js');
	}

	protected function details($path)
	{
		$rc = parent::details($path);

		require_once 'flvinfo.php';

		$flv = new Flvinfo();

		$info = $flv->getInfo($_SERVER['DOCUMENT_ROOT'] . $path);

		if ($info && $info->hasVideo)
		{
			$rc[] = $info->video->width . ' &times; ' . $info->video->height . ' @ ' . $info->video->fps . 'fps, ' . round($info->duration) . ' secs';
			$rc[] = $info->video->codecStr . ($info->hasAudio ? '/' . $info->audio->codecStr : '');
		}

		return $rc;
	}

	protected function preview($path)
	{
		$rc = new WdElement
		(
			'a', array
			(
				'href' => $path . '?w=64&h=64',
				'rel' => 'nonver',

				WdElement::T_INNER_HTML => 'NONVER'
			)
		);

		return $rc;
	}
}