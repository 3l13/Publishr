<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class site_pages_contents_WdActiveRecord extends WdActiveRecord
{
	/**
	 * The identifier of the page the content belongs to.
	 *
	 * @var int
	 */
	public $pageid;

	/**
	 * The identifier of the content.
	 *
	 * @var string
	 */
	public $contentid;

	/**
	 * The content of the content.
	 *
	 * @var string
	 */
	public $content;

	/**
	 * The editor used to edit and render the content.
	 *
	 * @var string
	 */
	public $editor;

	/**
	 * Returns the model used for the page contents.
	 *
	 * @see WdActiveRecord::model()
	 */
	protected function model($name='site.pages/contents')
	{
		return parent::model($name);
	}

	/**
	 * The rendered version of the page content.
	 *
	 * @var string|object
	 */
	private $rendered;

	/**
	 * Renders the page content as a string or an object.
	 *
	 * Exceptions thrown during the rendering are caught. The message of the exception is used
	 * as rendered content and the exception is rethrown.
	 *
	 * @throws Exception
	 *
	 * @return string|object The rendered page content.
	 */
	public function render()
	{
		if ($this->rendered !== null)
		{
			return $this->rendered;
		}

		$class = $this->editor . '_WdEditorElement';

		try
		{
			$rendered = call_user_func(array($class, 'render'), $this->content);

			if (is_string($rendered) || (is_object($rendered) && method_exists($rendered, '__toString')))
			{
				$rendered = self::handle_external_anchors((string) $rendered);
			}
		}
		catch (Exception $e)
		{
			$this->rendered = $e->getMessage();

			throw $e;
		}

		return $this->rendered = $rendered;
	}

	public function __toString()
	{
		try
		{
			$rc = (string) $this->render();
		}
		catch (Exception $e)
		{
			return (string) $e;
		}

		return $rc;
	}

	/**
	 * Adds a blank target to external href.
	 *
	 * @param string $html
	 */
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