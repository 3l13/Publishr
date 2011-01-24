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

	public $nid;
	public $uid;
	public $siteid;
	public $title;
	public $slug;
	public $constructor;
	public $created;
	public $modified;
	public $is_online;
	public $language;
	public $tnid;

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

	private static $translations_keys;

	protected function __get_translations_keys()
	{
		global $core;

		$native_language = $this->siteid ? $this->site->native->language : WdI18n::$native;

		if (!self::$translations_keys)
		{
			$groups = $core->models['system.nodes']->select('tnid, nid, language')->where('tnid != 0')->order('language')->all(PDO::FETCH_GROUP | PDO::FETCH_NUM);
			$keys = array();

			foreach ($groups as $native_id => $group)
			{
				foreach ($group as $row)
				{
					list($tnid, $tlanguage) = $row;

					$keys[$native_id][$tnid] = $tlanguage;
				}
			}

			foreach ($keys as $native_id => $translations)
			{
				$all = array($native_id => $native_language) + $translations;

				foreach ($translations as $tnid => $tlanguage)
				{
					$keys[$tnid] = $all;
					unset($keys[$tnid][$tnid]);
				}
			}

			self::$translations_keys = $keys;
		}

		$nid = $this->nid;

		return isset(self::$translations_keys[$nid]) ? self::$translations_keys[$nid] : null;
	}

	/**
	 * Returns the translation in the specified language for the record, or the record itself if no
	 * translation can be found.
	 *
	 * @param string $language The language for the translation. If the language is empty, the
	 * current language (as defined by the I18n class) is used.
	 *
	 * @return system_nodes_WdActiveRecord The translation for the record, or the record itself if
	 * no translation could be found.
	 */

	public function translation($language=null)
	{
		if (!$language)
		{
			$language = WdI18n::$language;
		}

		$translations = $this->translations_keys;

		if ($translations)
		{
			$translations = array_flip($translations);

			if (isset($translations[$language]))
			{
				return $this->model()->find($translations[$language]);
			}
		}

		return $this;
	}

	protected function __get_translation()
	{
		return $this->translation();
	}

	protected function __get_translations()
	{
//		throw new WdException("reimplement using translations_keys");

		$translations = $this->translations_keys;

		if (!$translations)
		{
			return;
		}

		return $this->model()->find(array_keys($translations));
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

		return $this;
	}
}