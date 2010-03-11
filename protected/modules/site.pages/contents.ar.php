<?php

class site_pages_contents_WdActiveRecord extends WdActiveRecord
{
	protected function model($name='site.pages/contents')
	{
		return parent::model($name);
	}

	public function __toString()
	{
		$class = $this->editor . '_WdEditorElement';

		try
		{
			$rc = (string) call_user_func(array($class, 'render'), $this->contents);
		}
		catch (Exception $e)
		{
			return (string) $e;
		}

		return $rc;
	}
}