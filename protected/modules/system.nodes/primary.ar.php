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
	const SITEID = 'siteid';
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
			wd_log('There is no translation in %language for %title (nid: %nid)', array('%language' => $language, '%title' => $this->title, '%nid' => $this->nid));

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

	protected function __get_metas()
	{
		return new system_nodes_WdMetasHandler($this);
	}

	// TODO-20100903: we should use metas for the lock handling. with 'lock.uid' and 'lock.until'
	// meta properties.

	public function lock()
	{
		global $app;

		$user = $app->user;

		if ($user->is_guest())
		{
			throw new WdException('Guest users cannot lock nodes');
		}

		#
		# is the node already locked by another user ?
		#

		$metas = $this->metas;
		$until = date('Y-m-d H:i:s', time() + 2 * 60);

		if ($metas['lock.uid'])
		{
			$now = time();

			// TODO-20100903: too much code, cleanup needed !

			if ($now > strtotime($metas['lock.uid']))
			{
				#
				# there _was_ a lock, but its time has expired, we can claim it.
				#

				$metas['lock.uid'] = $user->uid;
				$metas['lock.until'] = $until;
			}
			else
			{
				if ($metas['lock.uid'] != $user->uid)
				{
					return false;
				}

				$metas['lock.until'] = $until;
			}
		}
		else
		{
			$metas['lock.uid'] = $user->uid;
			$metas['lock.until'] = $until;
		}

		return true;
	}

	public function unlock()
	{
		global $app;

		$metas = $this->metas;
		$lock_uid = $metas['lock.uid'];

		if (!$lock_uid)
		{
			return;
		}

		if ($lock_uid != $app->user->uid)
		{
			return false;
		}

		$metas['lock.uid'] = null;
		$metas['lock.until'] = null;

		return true;
	}
}

class system_nodes_WdMetasHandler implements ArrayAccess
{
	private $nid;
	static private $model;

	public function __construct($node)
	{
		$this->nid = $node->nid;

		if (!self::$model)
		{
			global $core;

			self::$model = $core->models['system.nodes/metas'];
		}
	}

	private $values;

	public function get($name, $default=null)
	{
		if ($this->values === null)
		{
			$this->values = self::$model->select
			(
				array('name', 'value'), 'WHERE nid = ? ORDER BY name', array
				(
					$this->nid
				)
			)
			->fetchPairs();
		}

		if ($name == 'all')
		{
			return $this->values;
		}

		if (!isset($this->values[$name]))
		{
			return $default;
		}

		return $this->values[$name];
	}

	public function set($name, $value)
	{
		$this->values[$name] = $value;

		if ($value === null)
		{
			//wd_log('delete %name because is has been set to null', array('%name' => $name));

			self::$model->execute
			(
				'DELETE FROM {self} WHERE nid = ? AND name = ?', array
				(
					$this->nid, $name
				)
			);
		}
		else
		{
			//wd_log('set <code>:name := !value</code>', array(':name' => $name, '!value' => $value));

			self::$model->insert
			(
				array
				(
					'nid' => $this->nid,
					'name' => $name,
					'value' => $value
				),

				array
				(
					'on duplicate' => true
				)
			);
		}
	}

	public function offsetSet($offset, $value)
	{
        $this->set($offset, $value);
    }

    public function offsetExists($offset)
    {
        return $this->get($offset) !== null;
    }

    public function offsetUnset($offset)
    {
        $this->set($offset, null);
    }

    public function offsetGet($offset)
    {
    	return $this->get($offset);
    }
}