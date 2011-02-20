<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class WdI18nLinkElement extends WdElement
{
	const T_CONSTRUCTOR = '#i18n-constructor';

	public function __construct($tags)
	{
		global $core;

		$site = $core->working_site;
		$native = $site->native->language;

		parent::__construct
		(
			'select', $tags + array
			(
				WdElement::T_LABEL => '.tnid',
				WdElement::T_LABEL_POSITION => 'before',
				WdElement::T_GROUP => 'i18n',

				WdElement::T_DESCRIPTION => t('tnid', array(':native' => $native, ':language' => $site->language), array('scope' => array('element', 'description'))),

				'name' => Node::TNID
			)
		);
	}

	public function __toString()
	{
		global $core;

		$native = $core->working_site->native->language;
		$constructor = $this->get(self::T_CONSTRUCTOR);
		$options = array();

		if ($constructor == 'site.pages')
		{
			$nodes = $core->models['site.pages']
			->select('nid, parentid, title')
			->find_by_language($native)
			->order('weight, created')
			->all(PDO::FETCH_OBJ);

			$tree = site_pages_WdModel::nestNodes($nodes);

			if ($tree)
			{
				site_pages_WdModel::setNodesDepth($tree);
				$records = site_pages_WdModel::levelNodesById($tree);

				foreach ($records as $record)
				{
					$options[$record->nid] = str_repeat("\xC2\xA0", $record->depth * 4) . $record->title;
				}
			}
		}
		else
		{
			$options = $core->models['system.nodes']
			->select('nid, title')
			->find_by_constructor_and_language($constructor, $native)
			->order('title')
			->pairs;

			foreach ($options as &$label)
			{
				$label = wd_shorten($label);
			}

			unset($label);
		}

		$this->set(self::T_OPTIONS, array(null => '.none') + $options);

		return parent::__toString();
	}
}