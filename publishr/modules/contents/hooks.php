<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class contents_WdHooks
{
	static public function alter_block_manage(WdEvent $event)
	{
		global $core;

		$event->caches['contents.body'] = array
		(
			'title' => 'Corps des contenus',
			'description' => "Rendu HTML du corps des contenus, lorsqu'il diffère de la source.",
			'group' => 'contents',
			'state' => !empty($core->registry['contents.cache_rendered_body']),
			'size_limit' => false,
			'time_limit' => array(7, 'Jours')
		);
	}

	static public function enable_cache(system_cache__enable_WdOperation $operation)
	{
		global $core;

		return $core->registry['contents.cache_rendered_body'] = true;
	}

	static public function disable_cache(system_cache__disable_WdOperation $operation)
	{
		global $core;

		return $core->registry['contents.cache_rendered_body'] = false;
	}

	static public function stat_cache(system_cache__stat_WdOperation $operation)
	{
		global $core;

		$model = $core->models['system.registry/node'];

		list($count, $size) = $model->select('COUNT(targetid) count, SUM(LENGTH(value)) size')->where('name = "rendered_body"')->one(PDO::FETCH_NUM);

		if (!$count)
		{
			return array($count, 'Le cache est vide');
		}

		return array($count, $count . ' enregistrements<br /><span class="small">' . wd_format_size($size) . '</span>');
	}

	static public function clear_cache(system_cache__clear_WdOperation $operation)
	{
		global $core;

		return $core->models['system.registry/node']->where('name = "rendered_body" OR name = "rendered_body.timestamp"')->delete();
	}
}