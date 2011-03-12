<?php

class feedback_forms_WdHooks
{
	static public function event_alter_editor_options(WdEvent $event)
	{
		$event->rc['form'] = t('Form');
	}
}