<?php

class taxonomy_vocabulary_WdManager extends WdManager
{
	public function __construct($module, array $tags=array())
	{
		parent::__construct
		(
			$module, $tags += array
			(
				self::T_KEY => 'vid'
			)
		);
	}

	protected function columns()
	{
		return array
		(
			taxonomy_vocabulary_WdActiveRecord::VOCABULARY => array
			(
				WdResume::COLUMN_LABEL => 'Name',
				WdResume::COLUMN_HOOK => array('WdResume', 'modify_callback')
			),

			taxonomy_vocabulary_WdActiveRecord::VOCABULARYSLUG => array
			(
				WdResume::COLUMN_LABEL => 'Slug',
				WdResume::COLUMN_HOOK => array('WdResume', 'select_callback')
			),

			taxonomy_vocabulary_WdActiveRecord::IS_TAGS => array
			(
				WdResume::COLUMN_LABEL => 'Tags',
				WdResume::COLUMN_HOOK => array('WdResume', 'bool_callback')
			),

			taxonomy_vocabulary_WdActiveRecord::IS_MULTIPLE => array
			(
				WdResume::COLUMN_LABEL => 'Multiple',
				WdResume::COLUMN_HOOK => array('WdResume', 'bool_callback')
			)
		);
	}

	protected function get_cell_vocabulary($entry, $tag)
	{
		$label = self::modify_code($entry->vocabulary, $entry->vid, $this);

		$scopes = $this->module->model('scope')->select
		(
			'scope', 'WHERE vid = ?', array($entry->vid)
		)
		->fetchAll(PDO::FETCH_COLUMN);

		if ($scopes)
		{
			$label .= '<br /><span class="small">scope: ' . implode(', ', $scopes) . '</span>';
		}

		return $label;
	}
}