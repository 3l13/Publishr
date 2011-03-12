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
	/**
	 * Alters system.nodes module and submodules edit block with I18n options, allowing the user
	 * to select a language for the node and a native source target.
	 *
	 * Only the native target selector is added if the `language` property is defined in the
	 * T_HIDDENS array, indicating that the language is already set and cannot be modified by the
	 * user.
	 *
	 * The I18n options are not added if the following conditions are met:
	 *
	 * - The working site has no native target
	 * - The "i18n" module is disabled
	 * - Only one language is used by all the sites available.
	 * - The `language` property is defined in the T_CHILDREN array but is empty, indicating that
	 * the language is irrelevant for the node.
	 *
	 * @param WdEvent $event
	 */
	static public function alter_block_edit(WdEvent $event)
	{
		global $core;

		if (!$core->working_site->nativeid || empty($core->modules['i18n']))
		{
			return;
		}

		$languages = $event->target->model->where('language != ""')->count('language');

		if (count($languages) < 2)
		{
			return;
		}

		$tags = &$event->tags;
		$children = &$tags[WdElement::T_CHILDREN];

		if (array_key_exists(Node::LANGUAGE, $children) && empty($children[Node::LANGUAGE]))
		{
			return;
		}

		$tags[WdElement::T_GROUPS]['i18n'] = array
		(
			'title' => '.i18n',
			'weight' => 100,
			'class' => 'form-section flat'
		);

		$constructor = (string) $event->target;

		if (array_key_exists(Node::LANGUAGE, $event->tags[WdForm::T_HIDDENS]))
		{
			$children[Node::TNID] = new WdI18nLinkElement
			(
				array
				(
					WdI18nElement::T_CONSTRUCTOR => $constructor
				)
			);
		}
		else
		{
			$children['i18n'] = new WdI18nElement
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