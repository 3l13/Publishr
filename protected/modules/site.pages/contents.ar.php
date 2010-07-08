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

		$this->rendered = call_user_func(array($class, 'render'), $this->contents);

		return $this->rendered;
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
}