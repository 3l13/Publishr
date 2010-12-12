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
				taxonomy_terms_WdActiveRecord::TERM => new WdTitleSlugComboElement
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

	protected $cache_ar_vocabularies = array();
	protected $cache_ar_terms = array();

	public function event_ar_property(WdEvent $event)
	{
		global $core;

		$target = $event->target;
		$constructor = $target->constructor;
		$property = $vocabularyslug = $event->property;
		$siteid = $target->siteid;

		$use_slug = false;

		if (substr($property, -4, 4) == 'slug')
		{
			$use_slug = true;
			$vocabularyslug = substr($property, 0, -4);
		}

		$key = $siteid . '-' . $constructor . '-' . $vocabularyslug;

		if (!isset($this->cache_ar_vocabularies[$key]))
		{
			$this->cache_ar_vocabularies[$key] = $core->models['taxonomy.vocabulary']
			->where('? IN (scope)', $constructor)
			->where(array('vocabularyslug' => $vocabularyslug, 'siteid' => $target->siteid))
			->one();
		}

		$vocabulary = $this->cache_ar_vocabularies[$key];

		if (!$vocabulary)
		{
			return;
		}

		if ($vocabulary->is_required)
		{
			$event->value = 'uncategorized';
		}

		if (!isset($this->cache_ar_terms[$key]))
		{
			$terms = $this->model->query
			(
				'SELECT term.*, (SELECT GROUP_CONCAT(nid) FROM {self}_nodes tnode WHERE tnode.vtid = term.vtid) AS nodes_ids
				FROM {self} term WHERE vid = ?', array
				(
					$vocabulary->vid
				)
			)
			->fetchAll(PDO::FETCH_CLASS, 'taxonomy_terms_WdActiveRecord');

			foreach ($terms as $term)
			{
				$term->nodes_ids = array_flip(explode(',', $term->nodes_ids));
			}

			$this->cache_ar_terms[$key] = $terms;
		}

		$nid = $target->nid;

		foreach ($this->cache_ar_terms[$key] as $term)
		{
			if (!isset($term->nodes_ids[$nid]))
			{
				continue;
			}

			$event->value = $use_slug ? $term->termslug : $term;
			$event->stop();

			return;
		}
	}
}