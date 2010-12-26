<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class feedback_comments_WdManager extends WdManager
{
	const T_LIST_SPAM = '#manager-list-spam';

	public function __construct($module, array $tags=array())
	{
		parent::__construct
		(
			$module, $tags + array
			(
				self::T_KEY => 'commentid'
			)
		);

		global $document;

		$document->css->add('public/manage.css');
	}

	protected function columns()
	{
		return parent::columns() + array
		(
			Comment::CREATED => array
			(
				self::COLUMN_CLASS => 'contents'
			),

			'score' => array
			(
				self::COLUMN_CLASS => 'score'
			),

			Comment::AUTHOR => array
			(
				self::COLUMN_CLASS => 'author'
			),

			Comment::NID => array
			(

			)
		);
	}

	protected function loadRange($offset, $limit, array $where, $order, array $params)
	{
		if ($this->get(self::T_LIST_SPAM))
		{
			$where[] = 'status = "spam"';
		}
		else
		{
			$where[] = 'status != "spam"';
		}

		return parent::loadRange($offset, $limit, $where, $order, $params);
	}

	protected function get_cell_url($entry)
	{
		return new WdElement
		(
			'a', array
			(
				WdElement::T_INNER_HTML => 'Voir le commentaire',

				'href' => $entry->url,
				'class' => 'view'
			)
		);
	}

	protected function get_cell_created($entry, $tag)
	{
		$rc  = $this->get_cell_url($entry);

		$rc .= '<span class="contents">';
		$rc .= parent::modify_code(strip_tags($entry->excerpt(24)), $entry->commentid, $this);
		$rc .= '</span><br />';

		$rc .= '<span class="datetime small">';
		$rc .= $this->get_cell_datetime($entry, $tag);
		$rc .= '</span>';

		return $rc;
	}

	protected function get_cell_author($entry, $tag)
	{
		$rc = '';

		if ($entry->author_email)
		{
			$rc .= '<img src="' . wd_entities($entry->author_icon . '&s=32') . '" alt="' . wd_entities($entry->author) . '" />';
		}

		$rc .= '<div class="details">';

		$rc .= parent::select_code($tag, $entry->$tag, $entry->$tag, $this);

		$email = $entry->author_email;

		if ($email)
		{
			$rc .= '<br /><span class="small">';
			$rc .= '<a href="mailto:' . wd_entities($email) . '">' . wd_entities($email) . '</a>';
			$rc .= '</span>';
		}

		$url = $entry->author_url;

		if ($url)
		{
			$rc .= '<br /><span class="small">';
			$rc .= '<a href="' . wd_entities($url) . '" target="_blank">' . wd_entities($url) . '</a>';
			$rc .= '</span>';
		}

		$rc .= '</div>';

		return $rc;
	}

	protected function get_cell_score($entry)
	{
		return $this->module->spamScore($entry->contents, $entry->author_url, $entry->author);
	}

	protected function get_cell_nid($entry, $tag)
	{
		$node = $entry->node;
		$nodeId = $entry->$tag;

		$rc = '';

		if ($node)
		{
			$title = $node->title;
			$label = wd_entities(wd_shorten($title, 48, .75, $shortened));

			$rc .= new WdElement
			(
				'a', array
				(
					WdElement::T_INNER_HTML => 'Aller à l\'article',
					'href' => $node->url,
					'title' => $title,
					'class' => 'view'
				)
			);
		}
		else
		{
			$label = '<em class="warn">unknown-node-' . $nodeId . '</em>';
		}

		return $rc . self::select_code($tag, $nodeId, $label, $this);
	}
}