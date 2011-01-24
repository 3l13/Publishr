<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class contents_WdActiveRecord extends system_nodes_WdActiveRecord
{
	const SUBTITLE = 'subtitle';
	const BODY = 'body';
	const EXCERPT = 'excerpt';
	const DATE = 'date';
	const EDITOR = 'editor';
	const IS_HOME_EXCLUDED = 'is_home_excluded';

	public $subtitle;
	public $body;
	public $excerpt;
	public $date;
	public $editor;
	public $is_home_excluded;

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
		global $core;

		static $use_cache;

		if ($use_cache === null)
		{
			$use_cache = !empty($core->registry['contents.cache_rendered_body']);
		}

		$rendered_body = $body = $this->body;

//		TODO-20100425: should I sanitize the rendered contents, or should it be handled by the editor ?

		try
		{
			if ($use_cache)
			{
				$metas = $this->metas;
				$rendered_body_timestamp = strtotime($this->modified);

				if ($metas['rendered_body.timestamp'] >= $rendered_body_timestamp)
				{
					return $metas['rendered_body'];
				}

				if ($this->editor)
				{
					$class = $this->editor . '_WdEditorElement';
					$rendered_body = call_user_func(array($class, 'render'), $body);
				}

				if ($rendered_body && $rendered_body != $body)
				{
					$metas['rendered_body.timestamp'] = $rendered_body_timestamp;
					$metas['rendered_body'] = $rendered_body;
				}
			}
			else if ($this->editor)
			{
				$class = $this->editor . '_WdEditorElement';
				$rendered_body = call_user_func(array($class, 'render'), $body);
			}
		}
		catch (WdException $e)
		{
			$rendered_body = $e->getMessage();
		}

		return $rendered_body;
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
		global $core;

		$constructor = $this->constructor;

		return $core->models[$constructor]
		->where('is_online = 1 AND date > ? AND constructor = ?', $this->date, $constructor)
		->order('date')
		->limit(1)
		->one();
	}

	protected function __get_previous()
	{
		global $core;

		$constructor = $this->constructor;

		return $core->models[$constructor]
		->where('is_online = 1 AND date < ? AND constructor = ?', $this->date, $constructor)
		->order('date DESC')
		->limit(1)
		->one();
	}

	protected function __get_excerpt()
	{
		return wd_excerpt((string) $this);
	}

	public function excerpt($limit=55)
	{
		return isset($this->excerpt) ? wd_excerpt($this->excerpt, $limit) : wd_excerpt((string) $this, $limit);
	}
}