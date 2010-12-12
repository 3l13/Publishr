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
		# If the slug is not defined, we remove (unset) the property so that it is created from
		# the title on access.
		#

		if (empty($this->slug) && !empty($this->title))
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
		global $core;

		return $core->models['user.users']->load($this->uid);
	}

	#
	# translation
	#

	public function translation($language=null)
	{
		if (!$language)
		{
			$language = WdI18n::$language;
		}
		// TODO-20101121: go multisite
		if (!$this->language || $this->language == $language || count(WdI18n::$languages) < 2)
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
		if (!$this->language || $this->language == WdI18n::$native)
		{
			return $this;
		}
		*/

		return $this;
	}
}