<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class WdFileUploadElement extends WdElement
{
	public function __construct($tags, $dummy=null)
	{
		global $core;

		parent::__construct
		(
			'div', $tags + array
			(
				'class' => 'file-upload-element'
			)
		);

		$this->dataset += $this->options();

		$document = $core->document;

		$document->js->add('Swiff.Uploader.js');
		$document->js->add('fileupload.js');
		$document->css->add('fileupload.css');
	}

	protected function infos()
	{
		$path = $this->get('value');
		$details = $this->details($path);
		$preview = $this->preview($path);

		$rc = '';

		if ($preview)
		{
			$rc .= '<div class="preview">';
			$rc .= $preview;
			$rc .= '</div>';
		}

		if ($details)
		{
			$rc .= '<ul class="details">';

			foreach ($details as $detail)
			{
				$rc .= '<li>' . $detail . '</li>';
			}

			$rc .= '</ul>';
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
		$rc = '<a class="download" href="' . $path . '">' . t('download', array(), array('scope' => array('fileupload', 'element'))) . '</a>';

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
			'name' => $this->get('name'),
			'path' => $document->resolve_url('Swiff.Uploader.swf'),
			'max-file-size' => $limit * 1024
		);
	}

	public function getInnerHTML()
	{
		$name = $this->get('name');
		$path = $this->get('value');

		#
		#
		#

//		$rc  = '<var class="options" style="display: none; font-size: .8em; font-family: monospace">' . json_encode($this->options()) . '</var>';

		$rc  = '<div class="input">';

		$rc .= new WdElement
		(
			WdElement::E_TEXT, array
			(
				'value' => $this->get('value'),
				'readonly' => true,
				'name' => $name,
				'class' => 'reminder'
			)
		);

		$rc .= ' <span class="button trigger">' . t('choose_a_file', array(), array('scope' => array('fileupload', 'element'))) . '<input type="file" /></span>';

		$rc .= '</div>';

		#
		# uploading element
		#

		$rc .= '<div class="uploading">';
		$rc .= '<span class="progress"><span class="position"><span class="label">&nbsp;</span></span></span> ';
		$rc .= '<button type="button" class="danger cancel">' . t('cancel', array(), array('scope' => array('element', 'label'))) . '</button>';
		$rc .= '</div>';

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

			$limit = wd_format_size($limit * 1024);

			$rc .= PHP_EOL . '<div class="file-size-limit small" style="margin-top: .5em">';
			$rc .= t('The maximum file size must be less than :size.', array(':size' => $limit));
			$rc .= '</div>';
		}

		#
		# infos
		#

		$infos = null;

		if ($path)
		{
			if (!is_file($_SERVER['DOCUMENT_ROOT'] . $path))
			{
				$infos = t('The file %file is missing !', array('%file' => basename($path)));
			}
			else
			{
				$infos = $this->infos();
			}
		}

		if ($infos)
		{
			$this->addClass('has-info');
		}

		$rc .= '<div class="error"></div>';
		$rc .= '<div class="infos">' . $infos . '</div>';

		return $rc;
	}
}