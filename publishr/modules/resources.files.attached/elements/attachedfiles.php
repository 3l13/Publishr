<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class WdAttachedFilesElement extends WdElement
{
	const T_NODEID = '#attached-nodeid';
	const T_HARD_BOND = '#attached-hard-bond';

	public function __construct($tags, $dummy=null)
	{
		parent::__construct('div', $tags);

		$this->addClass('resources-files-attached');
	}

	protected function getInnerHTML()
	{
		global $core;

		$document = $core->document;

		$document->css->add('../public/attached.css');
		$document->js->add('../public/attached.js');
		$document->js->add('../../resources.files/elements/Swiff.Uploader.js');

		$nid = $this->get(self::T_NODEID);
		$hard_bond = $this->get(self::T_HARD_BOND, false);

		$lines = null;

		if ($nid)
		{
			$entries = $core->models['resources.files.attached']->query
			(
				'SELECT attached.*, file.nid, file.size, file.path
				FROM {self} attached
				INNER JOIN {prefix}resources_files file ON attached.fileid = file.nid
				WHERE nodeid = ?', array
				(
					$nid
				)
			)
			->fetchAll(PDO::FETCH_OBJ);

			foreach ($entries as $entry)
			{
				$lines .= self::create_attached_entry($entry, $hard_bond);
			}
		}

		$formats = null;

		//$formats = 'Seules les pièces avec les extensions suivantes sont prises en charge&nbsp;: jpg jpeg gif png txt doc xls pdf ppt pps odt ods odp.';

		$limit = ini_get('upload_max_filesize') * 1024 * 1024;
		$limit_formated = wd_format_size($limit);

		$this->dataset = array
		(
			'path' => WdDocument::resolve_url('../../resources.files/elements/Swiff.Uploader.swf'),
			'verbose' => false,
			'file-size-max' => $limit
		)

		+ $this->dataset;

		return <<<EOT
<ol>
	$lines
	<li class="progress">&nbsp;</li>
</ol>

<button type="button">Joindre une nouvelle pièce</button>

<div class="element-description">La taille maximum de chaque pièce est de $limit_formated.$formats</div>
EOT;
	}

	static public function create_attached_entry($entry, $hard_bond=false)
	{
		$hiddens = null;
		$links = array();

		$i = uniqid();
		$size = wd_format_size($entry->size);

		if ($entry instanceof WdUploaded)
		{
			$title = $entry->name;
			$extension = $entry->extension;

			$hiddens .= '<input type="hidden" class="file" name="resources_files_attached[' . $i .'][file]" value="' . wd_entities(basename($entry->location)) . '" />' . PHP_EOL;
			$hiddens .= '<input type="hidden" name="resources_files_attached[' . $i .'][mime]" value="' . wd_entities($entry->mime) . '" />' . PHP_EOL;

			$links = array
			(
				'<a href="#remove" class="remove">Retirer</a>'
			);
		}
		else
		{
			$fid = $entry->nid;
			$title = $entry->title;
			$extension = substr($entry->path, strrpos($entry->path, '.'));

			$hiddens .= '<input type="hidden" name="resources_files_attached[' . $i .'][fileid]" value="' . $fid . '" />';

			$links = array
			(
				'<a href="/admin/resources.files/' . $fid . '/edit">Éditer</a>',
				'<a href="/api/resources.files/' . $fid . '/download">Télécharger</a>',
				$hard_bond ? '<a href="#delete" class="danger">Supprimer le fichier</a>' : '<a href="#remove" class="warn">Briser le lien</a>'
			);
		}

		$title = wd_entities($title);
		$links = empty($links) ? '' : (' &ndash; ' . implode(', ', $links));

		if ($extension)
		{
			$extension = '<span class="lighter">(' . $extension . ')</span>';
		}

		return <<<EOT
<li>
	<span class="handle">↕</span><input type="text" name="resources_files_attached[$i][title]" value="$title" />
	<span class="small">
		<span class="info light">$size $extension</span> $links
	</span>

	$hiddens
</li>
EOT;
	}
}