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
		return $this->model()
		->where('is_online = 1 AND created > ? AND constructor = ?', $this->created, $this->constructor)
		->order('created ASC')
		->limit(1)
		->one;
	}

	/**
	 * Return the previous sibling for the node.
	 *
	 * @return mixed The previous sibling for the node or false if there is none.
	 */

	protected function __get_previous()
	{
		return $this->model()
		->where('is_online = 1 AND created < ? AND constructor = ?', $this->created, $this->constructor)
		->order('created DESC')
		->limit(1)
		->one;
	}

	/**
	 * Return the user object for the owner of the node.
	 *
	 * @return object The user object for the owner of the node.
	 */

	protected function __get_user()
	{
		global $core;

		return $core->models['user.users'][$this->uid];
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

		$rc = $this->model()->where('(tnid = ? OR nid = ?) AND language = ?', $this->nid, $this->tnid, $language)->one;

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
			$arq = $this->model()->where('(nid = ? OR tnid = ?) AND nid != ?', $tnid, $tnid, $nid);
		}
		else
		{
			$arq = $this->model()->where('tnid = ?', $nid);
		}

		return $arq->order('language')->all;
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
			return $this->model()->find($this->tnid);
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