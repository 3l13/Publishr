<?php

class taxonomy_terms_WdModule extends WdPModule
{
	protected function block_manage()
	{
		return new taxonomy_terms_WdManager($this);
	}

	protected function block_edit(array $values, $permission)
	{
		#
		# load vocabularies
		#

		global $core;

		$module = $core->getModule('taxonomy.vocabulary');

		$vid_options = array(null => '') + $module->model()->select
		(
			array('vid', 'vocabulary')
		)
		->fetchPairs();

		return array
		(
			WdElement::T_CHILDREN => array
			(
				taxonomy_terms_WdActiveRecord::TERM => new WdTitleSlugComboElement
				(
					array
					(
						WdForm::T_LABEL => 'Term',
						WdElement::T_MANDATORY => true
					)
				),

				taxonomy_terms_WdActiveRecord::VID => new WdElement
				(
					'select', array
					(
						WdForm::T_LABEL => 'Vocabulary',
						WdElement::T_OPTIONS => $vid_options,
						WdElement::T_MANDATORY => true
					)
				)
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
		if (!($event->ar instanceof system_nodes_WdActiveRecord))
		{
			return;
		}

		#
		#
		#

		$key = $event->ar->constructor . '-' . $event->property;

		if (!isset($this->cache_ar_vocabularies[$key]))
		{
			#
			# we check if the property is a vocabulary defined in the same scope.
			#

			global $core;

			$this->cache_ar_vocabularies[$key] = $core->getModule('taxonomy.vocabulary')->model()->loadRange
			(
				0, 1, 'INNER JOIN {self}_scope USING(vid) WHERE scope = ? AND vocabularyslug = ?', array
				(
					$event->ar->constructor,
					$event->property
				)
			)
			->fetchAndClose();
		}

		$vocabulary = $this->cache_ar_vocabularies[$key];

		if (!$vocabulary)
		{
			return;
		}

		$key = $vocabulary->vid . '-' . $event->ar->nid;

		if (!isset($this->cache_ar_terms[$key]))
		{
			$statement = $this->model()->loadAll
			(
				'INNER JOIN {self}_nodes USING(vtid) WHERE vid = ? AND nid = ? ORDER BY term', array
				(
					$vocabulary->vid,
					$event->ar->nid
				)
			);

			$this->cache_ar_terms[$key] = $vocabulary->is_multiple ? $statement->fetchAll() : $statement->fetchAndClose();
		}

		$event->value = $this->cache_ar_terms[$key];
		$event->stop();
	}
}