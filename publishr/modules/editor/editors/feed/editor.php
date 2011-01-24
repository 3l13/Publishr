<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class feed_WdEditorElement extends WdEditorElement
{
	private $elements = array();

	public function __construct($tags, $dummy=null)
	{
		parent::__construct
		(
			'div', $tags + array
			(
				self::T_CHILDREN => array
				(
					$this->elements['constructor'] = new WdElement
					(
						'select', array
						(
							WdElement::T_LABEL => 'Module',
							WdElement::T_LABEL_POSITION => 'above',
							WdElement::T_REQUIRED => true,
							WdElement::T_OPTIONS => array
							(
								null => '<sélectionner un module>',
								'contents.news' => 'Actualités',
								'contents.articles' => 'Articles'
							)
						)
					),

					$this->elements['limit'] = new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdElement::T_LABEL => "Nombre d'entrées dans le flux",
							WdElement::T_LABEL_POSITION => 'above',
							WdElement::T_REQUIRED => true,
							WdElement::T_DEFAULT => 10,

							'size' => 4
						)
					),

					$this->elements['settings'] = new WdElement
					(
						WdElement::E_CHECKBOX_GROUP, array
						(
							WdElement::T_LABEL => 'Options',
							WdElement::T_LABEL_POSITION => 'above',
							WdElement::T_OPTIONS => array
							(
								'is_with_author' => "Mentionner l'auteur",
								'is_with_category' => "Mentionner les catégories",
								'is_with_attached' => "Ajouter les pièces jointes"
							),

							'class' => 'list'
						)
					)
				),

				'class' => 'editor feed combo'
			)
		);
	}

	public function set($name, $value=null)
	{
		if (is_string($name) && $name == 'name')
		{
			foreach ($this->elements as $identifier => $element)
			{
				$element->set('name', $value . '[' . $identifier . ']');
			}
		}

		return parent::set($name, $value);
	}

	public function getInnerHTML()
	{
		$value = $this->get('value');

		if ($value)
		{
			$values = json_decode($value, true);

			foreach ($values as $identifier => $value)
			{
				$this->elements[$identifier]->set('value', $value);
			}
		}

		return parent::getInnerHTML();
	}

	static public function to_content(array $params, $content_id, $page_id)
	{
		global $core;

		$contents = parent::to_content($params, $content_id, $page_id);

		if (!$contents)
		{
			return;
		}

		// TODO-20101130: there is no cleanup for that, if the content is deleted, the view's target won't be removed

		$constructor = $contents['constructor'];
		$view_target_key = 'views.targets.' . strtr($constructor, '.', '_') . '/feed';

		$core->site->metas[$view_target_key] = $page_id;

		return json_encode($contents);
	}

	// http://tools.ietf.org/html/rfc4287

	static public function render($contents)
	{
		global $core, $page;

		$site = $page->site;
		$data = json_decode($contents, true);
		$constructor = $data['constructor'];
		$limit = $data['limit'];
		$gmt_offset = '+01:00';

		header('Content-Type: application/atom+xml');
		//header('Content-Type: text/plain');

		$host = preg_replace('#^www\.#', '', $_SERVER['HTTP_HOST']);
		$page_created = wd_format_time($page->created, '%Y-%m-%d');

		$entries = $core->models[$constructor]->where(array('is_online' => true, 'constructor' => $constructor))->order('date DESC')->limit($limit)->all;

		ob_start();

?>

	<id>tag:<?php echo $host ?>,<?php echo wd_format_time($page->created, '%Y-%m-%d') ?>:<?php echo $page->slug ?></id>
	<title><?php echo $page->title ?></title>
	<link href="<?php echo $page->absolute_url ?>" rel="self" />
	<link href="<?php echo $page->home->absolute_url ?>" />

	<author>
		<name><?php echo $page->user->firstname . ' ' . $page->user->lastname ?></name>
	</author>

	<updated><?php

	$updated = '';

	foreach ($entries as $entry)
	{
		if (strcmp($updated, $entry->modified) < 0)
		{
			$updated = $entry->modified;
		}
	}

	echo wd_format_time($updated, '%Y-%m-%dT%H:%M:%S') . $gmt_offset ?></updated>

<?php

		foreach ($entries as $entry)
		{
?>
	<entry>
		<title><?php echo $entry->title ?></title>
		<link href="<?php echo $entry->absolute_url ?>" />
		<id>tag:<?php echo $host ?>,<?php echo wd_format_time($entry->created, '%Y-%m-%d') ?>:<?php echo $entry->slug ?></id>
		<updated><?php echo wd_format_time($entry->modified, '%Y-%m-%dT%H:%M:%S') . $gmt_offset ?></updated>
		<published><?php echo wd_format_time($entry->date, '%Y-%m-%dT%H:%M:%S') . $gmt_offset ?></published>
		<author>
			<name><?php echo $entry->user->firstname . ' ' . $entry->user->lastname ?></name>
		</author>
		<category term="<?php echo $entry->category ?>" />
		<content type="html" xml:lang="<?php echo $entry->language ? $entry->language : $site->language  ?>"><![CDATA[<?php echo $entry ?>]]></content>
	</entry>
<?php
		}

		$rc = ob_get_clean();
		$rc = preg_replace('#(href|src)="/#', '$1="http://' . $host .'/', $rc);

		return '<?xml version="1.0" encoding="utf-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">' . $rc . '</feed>';;
	}
}