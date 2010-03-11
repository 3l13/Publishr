<?php

class feedback_comments_WdManager extends WdManager
{
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

		$document->addStyleSheet('public/manage.css');
	}

	protected function columns()
	{
		return parent::columns() + array
		(
			Comment::CREATED => array
			(
				WdResume::COLUMN_LABEL => 'Commentaire',
				WdResume::COLUMN_CLASS => 'contents',
				WdResume::COLUMN_SORT => WdResume::ORDER_DESC
			),

			'score' => array
			(
				WdResume::COLUMN_LABEL => 'Score',
				WdResume::COLUMN_CLASS => 'score'
			),

			Comment::AUTHOR => array
			(
				WdResume::COLUMN_LABEL => 'Author',
				WdResume::COLUMN_CLASS => 'author'
			),

			Comment::NID => array
			(
				WdResume::COLUMN_LABEL => 'Pour'
			)
		);
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

	protected function get_cell_created($entry)
	{
		$rc  = '';
		$rc .= $this->get_cell_url($entry);

		/*
		$rc .= new WdElement
		(
			'a', array
			(
				WdElement::T_INNER_HTML => 'Voir le commentaire',

				'href' => $entry->url,
				'class' => 'view'
			)
		);
		$rc .= ' ';
		*/
		$rc .= '<span class="contents">';
		$rc .= parent::modify_code($entry->excerpt(24), $entry->commentid, $this);
		$rc .= '</span><br />';


		$time = strtotime($entry->created);


		$rc .= '<span class="created small">';
		$rc .= ' <span class="date">';
		//$rc .= parent::modify_code(date('Y-m-d', $time), $entry->commentid, $this) . '</span>';
		$rc .= date('Y-m-d', $time) . '</span>';
		$rc .= ' <span class="time small">' . date('H:i', $time) . '</span>';
		$rc .= '</span>';



		/*
		$rc .= '<ul class="actions">';
		$rc .= '<li>'. parent::modify_code('Editer', $entry->commentid, $this) . '</li>';
		$rc .= '<li>Delete</li>';
		$rc .= '</ul>';
		*/

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
		$rc = '';

		$node = $entry->node;
		$nodeId = $entry->$tag;

		if ($node)
		{
			$label = $node->title;

			if (mb_strlen($label) > 52)
			{
				$label = mb_substr($label, 0, 24) . '…' . mb_substr($label, -24, 24);
				$shortened = true;
			}

			$rc .= new WdElement
			(
				'a', array
				(
					WdElement::T_INNER_HTML => 'Aller à l\'article',
					'href' => $node->url,
					'class' => 'view'
				)
			);

			$rc .= ' ';
		}
		else
		{
			$label = 'unknown-node-' . $nodeId;
		}

		return $rc . ' ' . self::select_code($tag, $nodeId, wd_entities($label), $this);
	}
}