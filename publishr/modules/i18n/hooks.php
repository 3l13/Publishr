<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class i18n_WdHooks
{
	static public function alter_block_edit(WdEvent $event)
	{
		global $core;

		if (empty($core->modules['i18n']) || $core->models['site.sites']->count('language') < 2 || !$core->working_site->nativeid)
		{
			return;
		}

		$siteid = $event->properties[Node::SITEID];

		if ($event->target instanceof site_pages_WdModule && $siteid)
		{
			$site = $core->models['site.sites'][$siteid];

			if (!$site->sourceid)
			{
				$event->tags[WdForm::T_HIDDENS][Node::LANGUAGE] = $site->language;

				return;
			}
		}

		$event->tags = wd_array_merge_recursive
		(
			$event->tags, array
			(
				WdElement::T_GROUPS => array
				(
					'i18n' => array
					(
						'title' => 'Internationalisation',
						'weight' => 100,
						'class' => 'form-section flat'
					)
				),

				WdElement::T_CHILDREN => array
				(
					'i18n' => new WdI18nElement
					(
						array
						(
							WdElement::T_GROUP => 'i18n',
							WdI18nElement::T_CONSTRUCTOR => (string) $event->target
						)
					)
				)
			)
		);
	}
}