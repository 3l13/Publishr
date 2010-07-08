<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class WdPageSelectorElement extends WdElement
{
	public function __toString()
	{
		try
		{
			global $core;

			$model = $core->getModule('site.pages')->model();

			$nodes = $model->select
			(
				array('nid', 'parentid', 'title'), 'ORDER BY weight, created'
			)
			->fetchAll(PDO::FETCH_OBJ);

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