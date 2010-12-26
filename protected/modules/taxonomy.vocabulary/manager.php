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
				WdResume::COLUMN_LABEL => 'Vocabulary'
			),

			taxonomy_vocabulary_WdActiveRecord::SCOPE => array
			(
				self::COLUMN_LABEL => 'Portée',
				self::COLUMN_HOOK => array($this, 'get_cell_scope')
			)/*

			TODO-20100324: create a single cell to display options

			,

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
			*/
		);
	}

	protected function get_cell_vocabulary($entry, $tag)
	{
		global $core;

		$terms = $core->models['taxonomy.terms']->select('term')->where('vid = ?', $entry->vid)->order('term')->all(PDO::FETCH_COLUMN);

		if ($terms)
		{
			$last = array_pop($terms);

			$includes = $terms
				? t('Comprenant&nbsp;: !list et !last', array('!list' => wd_shorten(implode(', ', $terms), 128, 1), '!last' => $last))
				: t('Comprenant&nbsp;: !entry', array('!entry' => $last));
		}
		else
		{
			$includes = '<em>La liste est vide</em>';
		}

		$title  = parent::modify_code($entry->vocabulary, $entry->vid, $this);
		$title .= '<span class="small"> &ndash; <a href="/admin/taxonomy.vocabulary/' . $entry->vid . '/order">Ordonner les termes du vocabulaire</a></span>';
		$title .= '<br />';
		$title .= '<span class="small">';
		$title .= $includes;
		$title .= '</span>';

		return $title;
	}

	protected function get_cell_scope($record, $tag)
	{
		global $core;

		$scope = $this->module->model('scopes')->select('constructor')->where('vid = ?', $record->vid)->all(PDO::FETCH_COLUMN);

		if ($scope)
		{
			foreach ($scope as &$constructor)
			{
				$constructor = '<a href="/admin/' . $constructor . '">' . t($core->descriptors[$constructor][WdModule::T_TITLE]) . '</a>';
			}

			$last = array_pop($scope);

			$includes = $scope
				? t(':list and :last', array(':list' => wd_shorten(implode(', ', $scope), 128, 1), ':last' => $last))
				: t(':one', array(':one' => $last));
		}
		else
		{
			$includes = '<em>Aucune portée</em>';
		}

		return '<span class="small">' . $includes . '</span>';
	}
}