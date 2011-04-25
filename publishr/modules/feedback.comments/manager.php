<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class feedback_comments_WdManager extends WdManager
{
	const T_LIST_SPAM = '#manager-list-spam';

	public function __construct($module, array $tags=array())
	{
		global $core;

		parent::__construct
		(
			$module, $tags + array
			(
				self::T_KEY => 'commentid'
			)
		);

		$core->document->css->add('public/manage.css');
	}

	protected function columns()
	{
		return parent::columns() + array
		(
			Comment::CREATED => array
			(
				'class' => 'contents'
			),

			'score' => array
			(
				'class' => 'score',
				'orderable' => false
			),

			Comment::AUTHOR => array
			(
				'class' => 'author'
			),

			Comment::NID => array
			(
				'orderable' => false
			)
		);
	}

	protected function alter_query(WdActiveRecordQuery $query, array $filters)
	{
		$query = parent::alter_query($query, $filters);

		$query->where($this->get(self::T_LIST_SPAM) ? 'status = "spam"' : 'status != "spam"');

		return $query;
	}

	protected function render_cell_url($record)
	{
		return new WdElement
		(
			'a', array
			(
				WdElement::T_INNER_HTML => 'Voir le commentaire',

				'href' => $record->url,
				'class' => 'view'
			)
		);
	}

	protected function render_cell_created($record, $property)
	{
		$rc  = $this->render_cell_url($record);

		$rc .= '<span class="contents">';
		$rc .= parent::modify_code(strip_tags($record->excerpt(24)), $record->commentid, $this);
		$rc .= '</span><br />';

		$rc .= '<span class="datetime small">';
		$rc .= $this->render_cell_datetime($record, $property);
		$rc .= '</span>';

		return $rc;
	}

	protected $last_rendered_author;

	protected function get_cell_author($record, $property)
	{
		if ($this->last_rendered_author == $record->author_email)
		{
			return self::REPEAT_PLACEHOLDER;
		}

		$this->last_rendered_author = $record->author_email;


		$rc = '';

		if ($record->author_email)
		{
			$rc .= '<img src="' . wd_entities($record->author_icon . '&s=32') . '" alt="' . wd_entities($record->author) . '" width="32" height="32" />';
		}

		$rc .= '<div class="details">';

		$rc .= $this->render_filter_cell($record, $property);

		$email = $record->author_email;

		if ($email)
		{
			$rc .= '<br /><span class="small">';
			$rc .= '<a href="mailto:' . wd_entities($email) . '">' . wd_entities($email) . '</a>';
			$rc .= '</span>';
		}

		$url = $record->author_url;

		if ($url)
		{
			$rc .= '<br /><span class="small">';
			$rc .= '<a href="' . wd_entities($url) . '" target="_blank">' . wd_entities($url) . '</a>';
			$rc .= '</span>';
		}

		$rc .= '</div>';

		return $rc;
	}

	protected function get_cell_score($record)
	{
		return feedback_comments_WdModule::score_spam($record->contents, $record->author_url, $record->author);
	}

	protected function get_cell_nid($record, $property)
	{
		$node = $record->node;

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
			$label = '<em class="warn">unknown-node-' . $record->$property . '</em>';
		}

		return $rc . $this->render_filter_cell($record, $property, $label);
	}
}