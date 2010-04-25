<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class contents_WdActiveRecord extends system_nodes_WdActiveRecord
{
	const CONTENTS = 'contents';
	const EXCERPT = 'excerpt';
	const DATE = 'date';

	public function __construct()
	{
		parent::__construct();

		if (empty($this->excerpt))
		{
			unset($this->excerpt);
		}
	}

	public function __toString()
	{
		if (isset($this->editor))
		{
			$class = $this->editor . '_WdEditorElement';

			try
			{
				// TODO-20100425: should I sanitize the rendered contents ?
				
				return call_user_func(array($class, 'render'), $this->contents);
			}
			catch (WdException $e)
			{
				return $e->getMessage();
			}
		}
		else
		{
			return $this->contents;
		}
	}

	protected function __get_author()
	{
		return $this->user->name;
	}

	protected function __get_year()
	{
		return substr($this->date, 0, 4);
	}

	protected function __get_month()
	{
		return substr($this->date, 5, 2);
	}

	protected function __get_next()
	{
		$constructor = $this->constructor;

		return self::model($constructor)->loadRange
		(
			0, 1, 'WHERE is_online = 1 AND date > ? AND constructor = ? ORDER BY date ASC', array
			(
				$this->date, $constructor
			)
		)
		->fetchAndClose();
	}

	protected function __get_previous()
	{
		$constructor = $this->constructor;

		return self::model($constructor)->loadRange
		(
			0, 1, 'WHERE is_online = 1 AND date < ? AND constructor = ? ORDER BY date DESC', array
			(
				$this->date, $constructor
			)
		)
		->fetchAndClose();
	}

	protected function __get_excerpt()
	{
		return wd_excerpt((string) $this);
	}

	protected function __get_categoryslug()
	{
		$category = $this->category;

		if (!$category)
		{
			return 'unknown-category';
		}

		return $this->category->termslug;
	}

	protected function __get_formatedDate()
	{
		$stime = strtotime($this->date);

		if (empty($this->finish) || !((int) $this->finish))
		{
			return strftime('%d %B %Y', $stime);
		}

		list($sy, $sm, $sd) = explode('-', $this->date);
		list($fy, $fm, $fd) = explode('-', $this->finish);

		$ftime = strtotime($this->finish);

		$rc = 'Du ';

		if ($sy == $fy && $sm == $fm)
		{
			$rc .= strftime('%d', $stime);
		}
		else if ($sy == $fy)
		{
			$rc .= strftime('%d %B', $stime);
		}
		else
		{
			$rc .= strftime('%d %B %Y', $stime);
		}

		$rc .= ' au ' . strftime('%d %B %Y', $ftime);

		return $rc;
	}
}