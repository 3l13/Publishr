<?php

class system_nodes_WdManager extends WdManager
{
	public function __construct($module, array $tags=array())
	{
		parent::__construct
		(
			$module, $tags + array
			(
				self::T_KEY => Node::NID,
				self::T_ORDER_BY => array(Node::MODIFIED, 'desc')
			)
		);

		global $document;

		$document->addStyleSheet('public/manage.css');
		$document->addJavaScript('public/manage.js');
	}

	protected function columns()
	{
		return array
		(
			'url' => array
			(
				self::COLUMN_LABEL => null,
				self::COLUMN_CLASS => 'url',
			),

			Node::TITLE => array
			(

			),

			Node::UID => array
			(
				self::COLUMN_HOOK => array(__CLASS__, 'user_callback'),
			),

			Node::CONSTRUCTOR => array
			(
				self::COLUMN_HOOK => array(__CLASS__, 'select_callback')
			),

			Node::CREATED => array
			(
				self::COLUMN_CLASS => 'date'
			),

			Node::MODIFIED => array
			(
				self::COLUMN_CLASS => 'date'
			),

			Node::IS_ONLINE => array
			(
				self::COLUMN_CLASS => 'is_online'
			)
		);
	}

	protected function jobs()
	{
		return parent::jobs() + array
		(
			'online' => 'En ligne',
			'offline' => 'Hors ligne'
		);
	}

	protected function get_cell_url($entry)
	{
		$url = $entry->url;

		if (!$url || $url{0} == '#')
		{
			return '&nbsp;';
		}

		return new WdElement
		(
			'a', array
			(
				WdElement::T_INNER_HTML => 'Afficher l\'entrée',

				'href' => $url,
				'title' => t('View this entry on the website'),
				'class' => 'view'
			)
		);
	}

	protected function get_cell_title($entry, $tag)
	{
		$title = $entry->$tag;
		$shortened = false;

		if (mb_strlen($title) > 52)
		{
			$title = wd_entities(mb_substr($title, 0, 24)) . '<span class="light">…</span>' . wd_entities(mb_substr($title, -24, 24));
			$shortened = true;
		}
		else
		{
			$title = wd_entities($title);
		}

		$title = $title ? $title : t('<em>no title</em>');

		$rc = '';

		$url = $entry->url;

		if ($url && $url{0} != '#')
		{
			$rc .= new WdElement
			(
				'a', array
				(
					WdElement::T_INNER_HTML => 'Afficher l\'entrée',

					'href' => $url,
					'title' => t('View this entry on the website'),
					'class' => 'view'
				)
			);

			$rc .= ' ';
		}

		$rc .= new WdElement
		(
			'a', array
			(
				WdElement::T_INNER_HTML => $title,

				'class' => 'edit',
				'title' => $shortened ? t('Edit the entry: !title', array('!title' => $entry->$tag ? $entry->$tag : 'unnamed')) : t('Edit the entry'),
				'href' => WdRoute::encode('/' . $entry->constructor . '/' . $entry->nid . '/edit')
			)
		);

		if ($entry->language)
		{
			$rc .= '<span class="language">:' . $entry->language . '</span>';

			$translations = $this->get_cell_i18n($entry);

			if ($translations != '&nbsp;')
			{
				$rc .= ' <span class="translations">[' . $translations . ']</span>';
			}
		}

		return $rc;
	}

	protected function get_cell_uid($entry, $tag)
	{
		$label = $this->get_cell_user($entry, $tag);

		return parent::select_code($tag, $entry->$tag, $label, $this);
	}

	protected function get_cell_created($entry, $tag)
	{
		return $this->get_cell_datetime($entry, $tag);
	}

	protected function get_cell_modified($entry, $tag)
	{
		return $this->get_cell_datetime($entry, $tag);
	}

	protected function get_cell_is_online($entry, $tag)
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
							'checked' => ($entry->$tag != 0)
						)
					)
				),

				'class' => 'checkbox-wrapper circle'
			)
		);
	}

	protected function get_cell_i18n($entry)
	{
		$entries = $this->model->select
		(
			array('nid', 'language'), 'WHERE tnid = ? ORDER BY language', array
			(
				$entry->nid
			)
		)
		->fetchPairs();

		if (!$entries)
		{
			return '&nbsp;';
		}

		$rc = array();

		foreach ($entries as $nid => $language)
		{
			$rc[] = '<a href="' . WdRoute::encode('/' . $this->module . '/' . $nid . '/edit') . '">' . $language . '</a>';
		}

		return implode(', ', $rc);
	}
}