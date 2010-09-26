<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class system_nodes_view_WdMarkup extends patron_WdMarkup
{
	protected $constructor = 'system.nodes';

	/**
	 * Publish a template binded with the entry defined by the `select` parameter.
	 *
	 * If the entry failed to be loaded, a WdHTTPException is thrown with the 404 code.
	 *
	 * If the entry is offline and the user has no permission to access it, a WdHTTPException is
	 * thrown with the 401 code.
	 *
	 * If the entry is offline and the user has permission to acces it, the title of the entry is
	 * marked with '=!='.
	 *
	 * @param array $args
	 * @param WdPatron $patron
	 * @param unknown_type $template
	 */

	public function __invoke(array $args, WdPatron $patron, $template)
	{
		if (isset($args['constructor']))
		{
			if (!is_array($args['select']))
			{
				if (is_numeric($args['select']))
				{
					$args['select'] = array
					(
						'nid' => $args['select']
					);
				}
				else
				{
					$args['select'] = array
					(
						'slug' => $args['select']
					);
				}
			}

			$args['select']['constructor'] = $args['constructor'];
		}

		$entry = $this->load($args['select']);

		if (!$entry)
		{
			throw new WdHTTPException
			(
				'The requested entry was not found.', array
				(

				),

				404
			);
		}
		else if (!$entry->is_online)
		{
			global $app;

			if (!$app->user->has_permission(PERMISSION_ACCESS, $entry->constructor))
			{
				throw new WdHTTPException
				(
					'The requested entry %uri requires authentication.', array
					(
						'%uri' => $entry->constructor . '/' . $entry->nid
					),

					401
				);
			}

			$entry->title .= ' =!=';
		}

		#
		#
		#

		$rc = $this->publish($patron, $template, $entry);

		#
		# set page node
		#

		global $page;

		$body = $page->body;

		if ($body instanceof site_pages_contents_WdActiveRecord && $body->editor == 'view' && $body->contents == $entry->constructor . '/view')
		{
			$page->node = $entry;
			$page->title = $entry->title;

			$rc = '<div id="' . strtr($entry->constructor, '.', '-') . '-view">' . $rc . '</div>';
		}

		/*
		global $app;

		if ($app->user->has_permission(PERMISSION_MAINTAIN, $entry))
		{
			global $document;

			$document->css->add('public/inline-admin.css');

			$url_edit = '/admin/index.php/' . $entry->constructor . '/' . $entry->nid . '/edit';

			$rc = <<<EOT
<div class="wd-inline-admin">
	<div class="title"><strong>Admin.</strong></div>

	<ul>
		<li><a href="$url_edit">Éditer</a></li>
		<li class="selected"><a href="$entry->url">Voir</a></li>
	</ul>
</div>
EOT

			. $rc;
		}
		*/

		return $rc;
	}

	protected function load($select)
	{
		$nid = $this->nid_from_select($select);

		return $this->model()->load($nid);
	}

	protected function parse_conditions($conditions)
	{
		if (is_numeric($conditions))
		{
			return array
			(
				array('`nid` = ?'),
				array($conditions)
			);
		}
		else if (is_string($conditions))
		{
			return array
			(
				array
				(
					'(`slug` = ? OR `title` = ?)',
					'(`language` = ? OR `language` = "")'
				),

				array
				(
					$conditions, $conditions,
					WdLocale::$language
				)
			);
		}

		// TODO-20100630: The whole point of the inherited markups is to get rid of the
		// WdModel::parseConditions() method.

		return $this->model->parseConditions($conditions);
	}

	protected function nid_from_select($select)
	{
		if (is_numeric($select))
		{
			return $select;
		}
		else if (is_string($select))
		{
			list($conditions, $args) = $this->parse_conditions($select);

//			wd_log(__FILE__ . ':: using string: \1\2', array($conditions, $args));

			return $this->model()->select
			(
				'nid', 'WHERE (slug = ? OR title = ?) AND (language = ? OR language = "") ORDER BY language DESC LIMIT 1', array
				(
					$select, $select, WdLocale::$language
				)
			)
			->fetchColumnAndClose();
		}
		else if (isset($select[Node::NID]))
		{
			return $select[Node::NID];
		}

		list($conditions, $args) = $this->parse_conditions($select);

//		wd_log(__FILE__ . ':: nid from: (\3) \1\2', array($conditions, $args, get_class($this)));

		return $this->model()->select
		(
			'nid', ($conditions ? 'WHERE ' . implode(' AND ', $conditions) : '') . 'ORDER BY created DESC LIMIT 1', $args
		)
		->fetchColumnAndClose();
	}
}
