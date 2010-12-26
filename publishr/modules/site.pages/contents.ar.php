<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class site_pages_contents_WdActiveRecord extends WdActiveRecord
{
	public $pageid;
	public $contentid;
	public $content;
	public $editor;
	public $type;

	protected function model($name='site.pages/contents')
	{
		return parent::model($name);
	}

	private $rendered = null;

	public function render()
	{
		if ($this->rendered)
		{
			return $this->rendered;
		}

		$class = $this->editor . '_WdEditorElement';

		$this->rendered = call_user_func(array($class, 'render'), $this->content);

		return $this->rendered;
	}

	public function __toString()
	{
		try
		{
			$rc = (string) $this->render();

			#
			# transform A markup with external URL
			#

			$rc = self::handle_external_anchors($rc);
		}
		catch (Exception $e)
		{
			return (string) $e;
		}

		return $rc;
	}

	static protected function handle_external_anchors($html)
	{
		return preg_replace_callback('#<a\s+[^>]+>#', array(__CLASS__, 'handle_external_anchors_callback'), $html);
	}

	static public function handle_external_anchors_callback($matches)
	{
		$str = array_shift($matches);

		preg_match_all('#([a-z]+)\="([^"]+)#', $str, $matches, 0, PREG_SET_ORDER);

		if (empty($matches[1]))
		{
			return $str;
		}

		$attributes = array_combine($matches[1], $matches[2]);

		if (isset($attributes['href']))
		{
			if (preg_match('#^http(s)?://#', $attributes['href'], $m))
			{
				$attributes['target'] = '_blank';
			}
		}

		$str = '<a';

		foreach ($attributes as $attribute => $value)
		{
			$str .= ' ' . $attribute . '="' . $value . '"';
		}

		$str .= '>';

		return $str;
	}
}