<?php

class taxonomy_terms_descriptions_WdModule extends WdPModule
{
	public function event_alter_block_edit(WdEvent $event)
	{
		$description = $this->model->select('description')->where(array('vtid' => $event->key))->column();

		$event->tags = wd_array_merge_recursive
		(
			$event->tags, array
			(
				WdForm::T_VALUES => array
				(
					'description' => $description
				),

				WdElement::T_CHILDREN => array
				(
					'description' => new moo_WdEditorElement
					(
						array
						(
							WdForm::T_LABEL => 'Description',

							'rows' => 5
						)
					)
				)
			)
		);
	}

	public function event_operation_save(WdEvent $event)
	{
		$vtid = $event->rc['key'];
		$params = &$event->operation->params;

		if (empty($params['description']))
		{
			$this->model->delete($vtid);
		}
		else
		{
			$this->model->insert
			(
				array
				(
					'vtid' => $vtid,
					'description' => $params['description']
				),

				array('on duplicate' => true)
			);
		}
	}

	public function event_ar_property(WdEvent $event)
	{
		if ($event->property != 'description')
		{
			return;
		}

		$event->value = $this->model->select('description')->where(array('vtid' => $event->target->vtid))->column;

		$event->stop();
	}
};