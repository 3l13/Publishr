<?php

class i18n_WdEvents
{
	static public function alter_block_edit(WdEvent $event)
	{
		global $core;

		if (!($event->module instanceof system_nodes_WdModule) || count(WdLocale::$languages) < 2 || !$core->hasModule('i18n'))
		{
			return;
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
							WdI18nElement::T_CONSTRUCTOR => (string) $event->module
						)
					)
				)
			)
		);
	}
}