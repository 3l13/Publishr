<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class moo_WdEditorElement extends WdEditorElement
{
	public function __construct($tags, $dummy=null)
	{
		parent::__construct
		(
			'textarea', $tags + array
			(
				'class' => 'editor moo',

				'rows' => 16
			)
		);
	}

	static public function to_content(array $params, $content_id, $page_id)
	{
		$contents = $params['contents'];

		//$contents = str_replace('<p>&nbsp;</p>', '', $contents);

		$contents = preg_replace('#<([^>]+)>[\s' . "\xC2\xA0" . ']+</\1>#', '', $contents);

		//wd_log('contents: ' . wd_entities($contents));

		return $contents;
	}

	public function getMarkup()
	{
		global $document;

		$css = $this->get(self::T_STYLESHEETS, array());

//		wd_log('css: \1', array($css));

		if (!$css)
		{
			$info = site_pages_WdModule::get_template_info('page.html');

			if (isset($info[1]))
			{
				$css = $info[1];
			}
		}

		array_unshift($css, $document->resolve_url('public/body.css'));

		if (count($css) == 1)
		{
			$css[] = $document->resolve_url('public/css/reset.css');
		}

		$document->css->add('public/assets/MooEditable.css');
		$document->css->add('public/assets/MooEditable.Image.css');
		$document->css->add('public/assets/MooEditable.Extras.css');
		$document->css->add('public/assets/MooEditable.SilkTheme.css');
		$document->css->add('public/assets/MooEditable.Paste.css');

		$document->js->add('public/source/MooEditable.js');
		$document->js->add('public/source/MooEditable.Image.js');
		$document->js->add('public/source/MooEditable.UI.MenuList.js');
		$document->js->add('public/source/MooEditable.Extras.js');
		$document->js->add('public/source/MooEditable.Paste.js');

		$document->js->add('public/auto.js');

		new WdPopNodeElement();
		new WdAdjustImageElement();

		$this->dataset['base-url'] = '/';
		$this->dataset['actions'] = 'bold italic underline strikethrough | formatBlock justifyleft justifyright justifycenter justifyfull | insertunorderedlist insertorderedlist indent outdent | undo redo | createlink unlink | image | removeformat paste toggleview';
		$this->dataset['external-css'] = $css;

		return parent::getMarkup();
	}
}