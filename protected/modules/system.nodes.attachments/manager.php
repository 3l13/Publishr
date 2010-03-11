<?php

class system_nodes_attachments_WdManager extends WdManager
{
	public function __construct($module, $tags)
	{
		parent::__construct
		(
			$module, $tags + array
			(
				self::T_KEY => 'attachmentid'
			)
		);
	}

	protected function columns()
	{
		return array
		(
			'title' => array
			(
				self::COLUMN_LABEL => 'Titre'
			),

			'scope' => array
			(
				self::COLUMN_LABEL => 'Source'
			),

			'target' => array
			(
				self::COLUMN_LABEL => 'Destination'
			),

			'is_mandatory' => array
			(
				self::COLUMN_LABEL => 'Obligatoire',
				self::COLUMN_HOOK => array(__CLASS__, 'bool_callback')
			)
		);
	}

	protected function get_cell_title($entry, $tag)
	{
		$label = $entry->$tag;

		if ($entry->id != $label)
		{
			$label .= ' <span class="small">(' . $entry->id . ')</span>';
		}

		return parent::modify_code($label, $entry->attachmentid, $this);
	}
}