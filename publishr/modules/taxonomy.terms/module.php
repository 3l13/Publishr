<?php

class taxonomy_terms_WdModule extends WdPModule
{
	protected function block_manage()
	{
		return new taxonomy_terms_WdManager($this);
	}

	protected function block_edit(array $values, $permission)
	{
		global $core;

		$vid_options = array(null => '') + $core->models['taxonomy.vocabulary']->select('vid, vocabulary')->pairs;

		/* beware of the 'weight' property, because vocabulary also define 'weight' and will
		 * override the term's one */

		return array
		(
			WdElement::T_CHILDREN => array
			(
				taxonomy_terms_WdActiveRecord::TERM => new WdTitleSlugComboWidget
				(
					array
					(
						WdForm::T_LABEL => 'Term',
						WdElement::T_REQUIRED => true
					)
				),

				taxonomy_terms_WdActiveRecord::VID => new WdElement
				(
					'select', array
					(
						WdForm::T_LABEL => 'Vocabulary',
						WdElement::T_OPTIONS => $vid_options,
						WdElement::T_REQUIRED => true
					)
				)/*,

				taxonomy_terms_WdActiveRecord::WEIGHT => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => 'Weight'
					)
				)
				*/
			)
		);
	}

	public function event_system_nodes_delete(WdEvent $event)
	{
		$nid = $event->rc;

		$this->model('nodes')->execute
		(
			'DELETE FROM {self} WHERE nid = ?', array
			(
				$nid
			)
		);
	}
}