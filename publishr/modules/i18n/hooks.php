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

		if (!$core->working_site->nativeid)
		{
			return;
		}

		if (empty($core->modules['i18n']) || $core->models['site.sites']->count('language') < 2)
		{
			return;
		}

		$tags = &$event->tags;

		$tags[WdElement::T_GROUPS]['i18n'] = array
		(
			'title' => '.i18n',
			'weight' => 100,
			'class' => 'form-section flat'
		);

		$constructor = (string) $event->target;

		if (array_key_exists(Node::LANGUAGE, $event->tags[WdForm::T_HIDDENS]))
		{
			$tags[WdElement::T_CHILDREN][Node::TNID] = new WdI18nLinkElement
			(
				array
				(
					WdI18nElement::T_CONSTRUCTOR => $constructor
				)
			);
		}
		else
		{
			$tags[WdElement::T_CHILDREN]['i18n'] = new WdI18nElement
			(
				array
				(
					WdElement::T_GROUP => 'i18n',
					WdI18nElement::T_CONSTRUCTOR => $constructor
				)
			);
		}
	}
}