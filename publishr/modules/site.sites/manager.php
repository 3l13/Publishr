<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class site_sites_WdManager extends WdManager
{
	public function __construct($module, array $tags=array())
	{
		parent::__construct
		(
			$module, $tags + array
			(
				self::T_KEY => 'siteid',
				self::T_ORDER_BY => 'title'
			)
		);
	}

	protected function columns()
	{
		return array
		(
			'title' => array
			(

			),

			'url' => array
			(
				'class' => 'url'
			),

			'status' => array
			(
				'label' => 'Status'
			)
		);
	}

	protected function get_cell_title($entry, $tag)
	{
		return parent::modify_callback($entry, $tag, $this);
	}

	protected function get_cell_url(site_sites_WdActiveRecord $record, $property)
	{
		$parts = explode('.', $_SERVER['HTTP_HOST']);
		$parts = array_reverse($parts);

		if ($record->tld)
		{
			$parts[0] = '<strong>' . $record->tld . '</strong>';
		}

		if ($record->domain)
		{
			$parts[1] = '<strong>' . $record->domain . '</strong>';
		}

		if ($record->subdomain)
		{
			$parts[2] = '<strong>' . $record->subdomain . '</strong>';
		}
		else if (empty($parts[2]))
		{
			unset($parts[2]);
		}

		return 'http://' . implode('.', array_reverse($parts)) . ($record->path ? '<strong>' . $record->path . '</strong>' : '');
	}

	protected function get_cell_language($entry, $tag)
	{
		global $core;

		return $core->locale->conventions['localeDisplayNames']['languages'][$entry->$tag];
	}

	protected function get_cell_status(WdActiveRecord $record, $property)
	{
		static $labels = array
		(
			'<span class="warn">Offline</span>',
			'Online',
			'Under maintenance',
			'Deneid access'
		);

		return $labels[$record->status];
	}
}