<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class WdFileUploadElement extends WdElement
{
	public function __construct($tags, $dummy=null)
	{
		parent::__construct(self::E_FILE, $tags);

		global $document;

		$document->js->add('../public/fancyupload/Swiff.Uploader.js');
		$document->js->add('../public/wdfileuploadelement.js');

		$document->css->add('../public/wdfileuploadelement.css');
	}

	protected function infos()
	{
		$rc = '';

		$path = $this->get('value');

		/*
		$rc  = '<div class="details">';
		$rc .= '<span title="Path: ' . $path . '">' . basename($path) . '</span>';
		$rc .= '<br />' . WdUploaded::getMIME($_SERVER['DOCUMENT_ROOT'] . $path);
		$rc .= '<br />' . WdResume::size_callback
		(
			(object) array
			(
				resources_images_WdModule::SIZE => filesize($_SERVER['DOCUMENT_ROOT'] . $path)
			),

			resources_images_WdModule::SIZE, null, null
		);
		$rc .= '</div>';
		*/

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

	protected function details($path)
	{
		$file = basename($path);

		if (strlen($file) > 40)
		{
			$file = substr($file, 0, 16) . '…' . substr($file, -16, 16);
		}

		$rc[] = '<span title="Path: ' . $path . '">' . $file . '</span>';
		$rc[] = WdUploaded::getMIME($_SERVER['DOCUMENT_ROOT'] . $path);
		$rc[] = WdResume::size_callback
		(
			(object) array
			(
				resources_files_WdActiveRecord::SIZE => filesize($_SERVER['DOCUMENT_ROOT'] . $path)
			),

			resources_files_WdActiveRecord::SIZE, null, null
		);

		return $rc;
	}

	protected function preview($path)
	{
		$rc = '<a class="download" href="' . $path . '">Télécharger</a>';

		return $rc;
	}

	protected function options()
	{
		global $document;

		$limit = $this->get(self::T_FILE_WITH_LIMIT, 2 * 1024);

		if ($limit === true)
		{
			$limit = ini_get('upload_max_filesize') * 1024;
		}

		return array
		(
			'path' => $document->getURLFromPath('../public/fancyupload/Swiff.Uploader.swf'),
			'fileSizeMax' => $limit * 1024
		);
	}

	public function __toString()
	{
		$path = $this->get('value');

		#
		#
		#

		$rc  = '<div class="file-upload-element">';

		$rc .= '<var class="options" style="display: none; font-size: .8em; font-family: monospace">' . json_encode($this->options()) . '</var>';

		$rc .= '<div class="input">';

		if ($path)
		{
			if (1)
			{
				$rc .= new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdElement::T_LABEL => 'URL du fichier' . (strpos($path, '$temp') !== false ? ' <small>(temporaire)</small>' : ''),
						WdElement::T_LABEL_POSITION => 'left',

						'name' => $this->get('name'),
						'value' => $this->get('value'),
						'readonly' => true
					)
				);
			}

			$rc .= ' ';
		}

		#
		#
		#

		if (0)
		{
			$rc .= new WdElement
			(
				'input', array
				(
					'type' => 'file',
					'name' => $this->get('name')
				)
			);
		}

		$rc .= '<button type="button">Choisir un fichier</button>';

		#
		# the T_FILE_WITH_LIMIT tag can be used to add a little text after the element
		# reminding the maximum file size allowed for the upload
		#

		$limit = $this->get(self::T_FILE_WITH_LIMIT);

		if ($limit)
		{
			if ($limit === true)
			{
				$limit = ini_get('upload_max_filesize') * 1024;
			}

			$rc .= PHP_EOL . '<div class="file-size-limit small" style="margin-top: .5em">';

			if ($limit > 1024)
			{
				$rc .= t('The maximum file size must be less than :size Mb.', array(':size' => $limit / 1024));
			}
			else
			{
				$rc .= t('The maximum file size must be less than :size Kb.', array(':size' => $limit));
			}

			$rc .= '</div>';
		}

		#
		#
		#

		$rc .= '</div>';

		#
		# infos
		#

		$rc .= '<div class="infos">';

		if ($path)
		{
			if (!is_file($_SERVER['DOCUMENT_ROOT'] . $path))
			{
				$rc .= t('The file %file is missing !', array('%file' => basename($path)));
			}
			else
			{
				$rc .= $this->infos();
			}
		}

		$rc .= '</div>';

		$rc .= '<div class="clear"></div>';

		$rc .= '</div>';

		return $rc;
	}
}