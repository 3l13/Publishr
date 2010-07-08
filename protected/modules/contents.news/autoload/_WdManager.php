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
		return parent::columns() + array
		(
			'is_home_excluded' => array
			(
				self::COLUMN_LABEL => ''
			)
		);
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