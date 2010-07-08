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
	static protected $url_cache = array();

	/**
	 * Return the URL type for the node.
	 *
	 * The URL is creating
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

		$key = 'views.targets.' . strtr($this->constructor, '.', '_') . '/' . $type;

		if (!isset(self::$url_cache[$key]))
		{
			global $registry;

			$page_id = $registry[$key];
			$page = self::$pages_model->load($page_id);

			self::$url_cache[$key] = $page ? $page->translation->url_pattern : false;
		}

		$pattern = self::$url_cache[$key];

		if (!$pattern)
		{
			return '#uknown-url-' . $type . '-for-' . strtr($this->constructor, '.', '-');
		}

		return WdRoute::format($pattern, $this);
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

	public function absolute_url($type='view')
	{
		return 'http://' . $_SERVER['HTTP_HOST'] . $this->url($type);
	}

	/**
	 * Return the _primary_ absolute URL for the node.
	 *
	 * @return string The primary absolute URL for the node.
	 */

	protected function __get_absolute_url()
	{
		return $this->absolute_url();
	}

	#
	# translation
	#

	public function translation($language=null)
	{
		if (!$language)
		{
			$language = WdLocale::$language;
		}

		if (!$this->language || $this->language == $language || count(WdLocale::$languages) < 2)
		{
			return $this;
		}

		$rc = $this->model()->loadRange
		(
			0, 1, 'WHERE (tnid = ? OR nid = ?) AND language = ?', array
			(
				$this->nid, $this->tnid, $language
			)
		)
		->fetchAndClose();

		if (!$rc)
		{
			wd_log('no translation in "\1" for \2', array($language, $this));

			return $this;
		}

		return $rc;
	}

	protected function __get_translation()
	{
		return $this->translation();
	}

	protected function __get_translations()
	{
		$nid = $this->nid;
		$tnid = $this->tnid;

		if ($tnid)
		{
			return self::model()->loadAll('WHERE (nid = ? OR tnid = ?) AND nid != ? ORDER BY language', array($tnid, $tnid, $nid))->fetchAll();
		}
		else
		{
			return self::model()->loadall('WHERE tnid = ? ORDER BY language', array($nid))->fetchAll();
		}
	}

	/**
	 *
	 * Return the native node for this translated node.
	 */

	// TODO-20100629: Maybe we should rename the `tnid` property to `native_nid`

	protected function __get_native()
	{
		if ($this->tnid)
		{
			return $this->model()->load($this->tnid);
		}

		/*
		if (!$this->language || $this->language == WdLocale::$native)
		{
			return $this;
		}
		*/

		return $this;
	}

	public function lock(&$lock=null)
	{
		global $app;

		$user = $app->user;

		if ($user->is_guest())
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
		global $core;

		$lock = $core->models['system.nodes/locks']->load($this->nid);

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

		return true;
	}
}