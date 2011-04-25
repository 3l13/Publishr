<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class WdAttachmentsElement extends WdElement
{
	const T_NODEID = '#attachments-nodeid';
	const T_HARD_BOND = '#attachments-hard-bond';

	public function __construct($tags, $dummy=null)
	{
		parent::__construct('div', $tags);

		$this->addClass('resources-files-attached');
	}

	protected function getInnerHTML()
	{
		global $core;

		$document = $core->document;

		$document->css->add('attachments.css');
		$document->js->add('attachments.js');
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
				$lines .= self::create_attachment($entry, $hard_bond);
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

		$label_join = t('Add a new attachment');
		$label_limit = t('The maximum size for each attachment is :size', array(':size' => $limit_formated));

		return <<<EOT
<ol>
	$lines
	<li class="progress">&nbsp;</li>
</ol>

<button type="button">$label_join</button>

<div class="element-description">$label_limit.$formats</div>
EOT;
	}

	static public function create_attachment($entry, $hard_bond=false)
	{
		global $core;

		$hiddens = null;
		$links = array();

		$i = uniqid();
		$size = wd_format_size($entry->size);
		$preview = null;

		if ($entry instanceof WdUploaded)
		{
			$title = $entry->name;
			$extension = $entry->extension;

			$hiddens .= '<input type="hidden" class="file" name="resources_files_attached[' . $i .'][file]" value="' . wd_entities(basename($entry->location)) . '" />' . PHP_EOL;
			$hiddens .= '<input type="hidden" name="resources_files_attached[' . $i .'][mime]" value="' . wd_entities($entry->mime) . '" />' . PHP_EOL;

			$links = array
			(
				'<a href="#remove" class="remove">' . t('label.remove') . '</a>'
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
				'<a href="' . $core->contextualize_api_string('/admin/resources.files/' . $fid . '/edit') . '">' . t('label.edit') .'</a>',
				'<a href="' . WdOperation::encode('resources.files/' . $fid . '/download') . '">' . t('label.download') . '</a>',
				$hard_bond ? '<a href="#delete" class="danger">' . t('Delete file') .'</a>' : '<a href="#remove" class="warn">' . t('Break link') . '</a>'
			);

			$node = $core->models['system.nodes'][$entry->nid];

			if ($node instanceof resources_images_WdActiveRecord)
			{
				$preview = new WdElement
				(
					'img', array
					(
						'src' => $node->thumbnail('$icon'),
						'width' => resources_images_WdModule::ICON_WIDTH,
						'height' => resources_images_WdModule::ICON_HEIGHT,
						'alt' => $node->alt
					)
				);
			}
		}

		$title = wd_entities($title);
		$links = empty($links) ? '' : (' &ndash; ' . implode(', ', $links));

		if ($extension)
		{
			$extension = '<span class="lighter">(' . $extension . ')</span>';
		}

		return <<<EOT
<li>
	<span class="handle">↕</span>$preview<input type="text" name="resources_files_attached[$i][title]" value="$title" />
	<span class="small">
		<span class="info light">$size $extension</span> $links
	</span>

	$hiddens
</li>
EOT;
	}
}