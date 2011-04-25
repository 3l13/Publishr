<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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

	/**
	 * Uses the record constructor as model name.
	 *
	 * @see WdActiveRecord::model()
	 */
	protected function model($name=null)
	{
		return parent::model($name ? $name : ($this->constructor ? $this->constructor : 'system.nodes'));
	}

	/**
	 * Creates a system_nodes_WdActiveRecord instance.
	 *
	 * The `slug` property is unset if it is empty but the `title` property is defined. The slug
	 * will be created on the fly when accessed throught the `slug` property.
	 */
	public function __construct()
	{
		if (!$this->slug && $this->title)
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
	 * Return the next online sibling for the node.
	 *
	 * @return system_nodes_WdActiveRecord|bool The next sibling for the node or false if there is none.
	 */
	protected function __get_next()
	{
		return $this->model()->own->visible->where('created > ?', $this->created)->order('created')->one;
	}

	/**
	 * Return the previous online sibling for the node.
	 *
	 * @return system_nodes_WdActiveRecord|bool The previous sibling for the node or false if there is none.
	 */
	protected function __get_previous()
	{
		return $this->model()->own->visible->where('created < ?', $this->created)->order('created DESC')->one;
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
		global $core;

		if (!$language)
		{
			$language = $core->language;
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

	// TODO-20100629: Maybe we should rename the `tnid` property to `nativeid`

	protected function __get_native()
	{
		return $this->tnid ? $this->model()->find($this->tnid) : $this;
	}

	protected function __get_css_class()
	{
		return "node node-{$this->nid} node-slug-{$this->slug} constructor-" . wd_normalize($this->constructor);
	}
}