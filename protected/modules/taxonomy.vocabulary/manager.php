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

		$terms = $core->getModule('taxonomy.terms')->model()->select
		(
			'term', 'WHERE vid = ? ORDER BY term', array
			(
				$entry->vid
			)
		)
		->fetchAll(PDO::FETCH_COLUMN);

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

	protected function get_cell_scope($entry, $tag)
	{
		global $core;
		/*DIRTY:SCOPE
		$scopes = $this->module->model('scope')->select
		(
			'scope', 'WHERE vid = ? ORDER BY scope', array($entry->vid)
		)
		->fetchAll(PDO::FETCH_COLUMN);

		if ($scopes)
		{
			$last = array_pop($scopes);

			$includes = $scopes
				? t('Portée&nbsp;: !list et !last', array('!list' => wd_shorten(implode(', ', $scopes), 128, 1), '!last' => $last))
				: t('Portée&nbsp;: !entry', array('!entry' => $last));
		}
		else
		{
			$includes = '<em>Aucune portée</em>';
		}
		*/

		if ($entry->scope)
		{
			$scope = explode(',', $entry->scope);

			foreach ($scope as &$a)
			{
				$a = t($core->descriptors[$a][WdModule::T_TITLE]);
			}

			$last = array_pop($scope);

			$includes = $scope
				? t('!list et !last', array('!list' => wd_shorten(implode(', ', $scope), 128, 1), '!last' => $last))
				: t('!entry', array('!entry' => $last));
		}
		else
		{
			$includes = '<em>Aucune portée</em>';
		}

		return '<span class="small">' . $includes . '</span>';
	}
}