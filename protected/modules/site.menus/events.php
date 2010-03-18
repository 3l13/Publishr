<?php

class site_menus_WdEvents
{
	static public function alter_block_edit(WdEvent $event)
	{
		global $core;

		if (!$event->module instanceof site_pages_WdModule)
		{
			return;
		}

		if (!$core->hasModule('site.menus'))
		{
			return;
		}

		$description_el = $event->tags[WdElement::T_CHILDREN]['parentid'];
		$description = $description_el->getTag(WdElement::T_DESCRIPTION);

		$description .= ' Les pages peuvent également être orgnisées en <a href="' .
		WdRoute::encode('/site.menus') . '">menus</a>.';

		$description_el->setTag(WdElement::T_DESCRIPTION, $description);
	}
}