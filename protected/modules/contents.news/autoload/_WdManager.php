<?php

class contents_news_WdManager extends contents_WdManager
{
	public function __construct($module, $tags)
	{
		parent::__construct($module, $tags);

		global $document;

		$document->css->add('../public/manage.css');
		$document->js->add('../public/manage.js');
	}

	protected function columns()
	{
		$columns = parent::columns() + array
		(
			'is_home_excluded' => array
			(
				self::COLUMN_LABEL => ''
			)
		);

		$columns['date'][self::COLUMN_HOOK] = array($this, 'get_cell_date');

		return $columns;
	}

	protected function get_cell_is_home_excluded($entry, $tag)
	{
		return new WdElement
		(
			'label', array
			(
				WdElement::T_CHILDREN => array
				(
					new WdElement
					(
						WdElement::E_CHECKBOX, array
						(
							'value' => $entry->nid,
							'checked' => ($entry->$tag != 0),
							'class' => 'is_home_excluded'
						)
					)
				),

				'class' => 'checkbox-wrapper home'
			)
		);
	}
}