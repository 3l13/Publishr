<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class WdPageSelectorElement extends WdElement
{
	public function __toString()
	{
		global $core;

		try
		{
			$model = $core->models['site.pages'];
			$nodes = $model->select('nid, parentid, title')->where('siteid = ?', $core->site_id)->order('weight, created')->all(PDO::FETCH_OBJ);

			$tree = site_pages_WdModel::nestNodes($nodes);
			site_pages_WdModel::setNodesDepth($tree);
			$entries = site_pages_WdModel::levelNodesById($tree);

			$options = array();

			foreach ($entries as $entry)
			{
				$options[$entry->nid] = str_repeat("\xC2\xA0", $entry->depth * 4) . $entry->title;
			}

			$this->set(self::T_OPTIONS, array(null => '') + $options);
		}
		catch (Exception $e)
		{
			return $e->getMessage();
		}

		return parent::__toString();
	}
}