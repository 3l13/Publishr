<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class WdAttachedFilesElement extends WdElement
{
	const T_NODEID = '#attached-nodeid';
	const T_HARD_BOND = '#attached-hard-bond';

	public function __construct($tags, $dummy=null)
	{
		parent::__construct('div', $tags);
	}

	protected function getInnerHTML()
	{
		global $core, $document;

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

		$options = array
		(
			'path' => WdDocument::getURLFromPath('../../resources.files/elements/Swiff.Uploader.swf'),
			'verbose' => false,
			'fileSizeMax' => $limit
		);

		$options_el = '<input type="hidden" class="element-options" value="' . wd_entities(json_encode($options)) . '" />';

		return <<<EOT
<div class="resources-files-attached">
	<ol>
		$lines
		<li class="progress">&nbsp;</li>
	</ol>

	<button type="button">Joindre une nouvelle pièce</button>

	<div class="element-description">La taille maximum de chaque pièce est de $limit_formated.$formats</div>

	$options_el
</div>
EOT;
	}

	static public function create_attached_entry($entry, $hard_bond=false)
	{
		$hiddens = null;
		$links = array();

		$i = uniqid();
		$size = wd_format_size($entry->size);

		// FIXME-201000624: The selector of MooTools 1.2.4 doesn't work correctly with '#remove'
		// thus we use the 'remove' class which should disappear as soon as the bug is fixed.

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
				'<a href="' . WdRoute::encode('/resources.files/' . $fid . '/edit') . '">Éditer</a>',
				'<a href="/do/resources.files/' . $fid . '/download">Télécharger</a>',
				$hard_bond ? '<a href="#remove" class="remove danger">Supprimer le fichier</a>' : '<a href="#remove" class="remove warn">Briser le lien</a>'
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