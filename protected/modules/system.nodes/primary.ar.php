<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class system_nodes_WdActiveRecord extends WdActiveRecord
{
	const NID = 'nid';
	const UID = 'uid';
	const TITLE = 'title';
	const SLUG = 'slug';
	const CONSTRUCTOR = 'constructor';
	const CREATED = 'created';
	const MODIFIED = 'modified';
	const IS_ONLINE = 'is_online';
	const LANGUAGE = 'language';
	const TNID = 'tnid';
	const IS_TRANSLATION_DEPRECATED = 'is_translation_deprecated';

	protected function model($name=null)
	{
		return parent::model($name ? $name : ($this->constructor ? $this->constructor : 'system.nodes'));
	}

	public function __construct()
	{
		#
		# If the slug is not defined, we remove (unset) the property so that it is created upon
		# access from the title.
		#

		if (empty($this->slug))
		{
			unset($this->slug);
		}

		parent::__construct();
	}

	protected function __get_slug()
	{
		return wd_normalize($this->title);
	}

	/**
	 * Return the next sibling for the node.
	 *
	 * @return mixed The next sibling for the node or false if there is none.
	 */

	protected function __get_next()
	{
		return $this->model()->loadRange
		(
			0, 1, 'WHERE is_online = 1 AND created > ? AND constructor = ? ORDER BY created ASC', array
			(
				$this->created, $this->constructor
			)
		)
		->fetchAndClose();
	}

	/**
	 * Return the previous sibling for the node.
	 *
	 * @return mixed The previous sibling for the node or false if there is none.
	 */

	protected function __get_previous()
	{
		return $this->model()->loadRange
		(
			0, 1, 'WHERE is_online = 1 AND created < ? AND constructor = ? ORDER BY created DESC', array
			(
				$this->created, $this->constructor
			)
		)
		->fetchAndClose();
	}

	/**
	 * Return the user object for the owner of the node.
	 *
	 * @return object The user object for the owner of the node.
	 */

	protected function __get_user()
	{
		return $this->model('user.users')->load($this->uid);
	}

	#
	# URL
	#

	static private $pages_model;
	static protected $urlCache = array();

	/**
	 * Return the URL type for the node.
	 *
	 * @param string $type The URL type.
	 *
	 * @return string The URL type for the node or a dummy anchor if the URL type is not defined,
	 * or the page associated with the URL type is not found.
	 */

	public function url($type='view')
	{
		if (self::$pages_model === false)
		{
			return '#';
		}
		else
		{
			try
			{
				self::$pages_model = self::model('site.pages');
			}
			catch (Exception $e)
			{
				return '#';
			}
		}

		$base = wd_camelCase($this->constructor, '.');
		$cacheKey = $base . '.' . $type;

		if (!isset(self::$urlCache[$cacheKey]))
		{
			global $registry;

			$pageid = $registry->get($base . '.url.' . $type);

			$page = self::$pages_model->load($pageid);

			if ($page)
			{
				#
				# if the page has a translation for the current language, we use the translation of
				# the page instead.
				#

				/*
				$translation = $page->translation;

				if ($translation)
				{
					$page = $translation;
				}
				*/

				$page = $page->translation;
			}

			self::$urlCache[$cacheKey] = $page;
		}

		$page = self::$urlCache[$cacheKey];

		if (!$page)
		{
			return '#uknown-url-' . $type . '-for-' . str_replace('.', '-', $this->constructor);
		}

		return $page->entryUrl($this);
	}

	/**
	 * Return the _primary_ URL for the node.
	 *
	 * @return string The primary URL for the node.
	 */

	protected function __get_url()
	{
		return $this->url();
	}

	/**
	 * Return the absolute URL type for the node.
	 *
	 * @param string $type The URL type.
	 *
	 */

	static protected $site_base;

	public function absoluteUrl($type='view')
	{
		if (!self::$site_base)
		{
			global $registry;

			self::$site_base = $registry->get('site.base');
		}

		return self::$site_base . $this->url($type);
	}

	/**
	 * Return the _primary_ absolute URL for the node.
	 *
	 * @return string The primary absolute URL for the node.
	 */

	protected function __get_absoluteUrl()
	{
		return $this->absoluteUrl();
	}

	#
	# translation
	#

	public function translation($language=null)
	{
		if (count(WdLocale::$languages) < 2)
		{
			return $this;
		}

		if (!$language)
		{
			$language = WdLocale::$language;
		}

		$rc = $this->model()->loadRange
		(
			0, 1, 'WHERE tnid = ? AND language = ?', array
			(
				$this->nid, $language
			)
		)
		->fetchAndClose();

		if (!$rc)
		{
			return $this;

			//wd_log_error('no translation for: \1', array($this));
		}

		return $rc;
	}

	protected function __get_translation()
	{
		return $this->translation();
	}

	protected function __get_native()
	{
		if ($this->tnid)
		{
			return $this->model()->load($this->tnid);
		}

		if (!$this->language || $this->language == WdLocale::$native)
		{
			return $this;
		}
	}

	public function lock(&$lock=null)
	{
		global $app;

		$user = $app->user;

		if ($user->isGuest())
		{
			throw new WdException('Guest users cannot lock nodes');
		}

		$model = self::model('system.nodes/locks');

		$lock = $model->load($this->nid);

		#
		# is the node already locked by another user ?
		#

		$until = date('Y-m-d H:i:s', time() + 2 * 60);

		if ($lock)
		{
			$now = time();

			if ($now > strtotime($lock->until))
			{
				#
				# there _was_ a lock, but its time has expired, we can claim it.
				#

				$lock->until = $until;
				$lock->uid = $user->uid;

				$lock->save();
			}
			else
			{
				if ($lock->uid != $user->uid)
				{
					return false;
				}

				$lock->until = $until;

				$lock->save();
			}
		}
		else
		{
			$rc = $model->save
			(
				array
				(
					'nid' => $this->nid,
					'uid' => $user->uid,
					'until' => $until
				),

				null
			);
		}

		return true;
	}

	public function unlock()
	{
		$model = $this->model('system.nodes/locks');
		$lock = $model->load($this->nid);

		if (!$lock)
		{
			return;
		}

		global $app;

		if ($lock->uid != $app->user->uid)
		{
			return false;
		}

		$lock->delete();
	}
}