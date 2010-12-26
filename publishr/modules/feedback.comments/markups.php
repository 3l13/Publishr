<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class feedback_comments_WdMarkups
{
	static public function comments(array $args, WdPatron $patron, $template)
	{
		global $core;

		if (array_key_exists('by', $args))
		{
			throw new WdException('"by" is no longer supported, use "order": \1', array($args));
		}

		extract($args);

		#
		# build sql query
		#

		$arr = $core->models['feedback.comments']->where('status = "approved"');

		if ($node)
		{
			$arr->where(array('nid' => $node));
		}

		if ($noauthor)
		{
			$arr->where('(SELECT uid FROM {prefix}system_nodes WHERE nid = comment.nid) != IFNULL(uid, 0)');
		}

		if ($order)
		{
			$arr->order($order);
		}

		if ($limit)
		{
			$arr->limit($limit * $page, $limit);
		}

		$entries = $arr->all();

		if (!$entries && !$parseempty)
		{
			return;
		}

		return $patron->publish($template, $entries);
	}

	static public function form(array $args, WdPatron $patron, $template)
	{
		global $core;

		#
		# Obtain the form to use to add a comment from the 'feedback.forms' module.
		#

		$form_id = $core->site->metas['feedback_comments.form_id'];

		if (!$form_id)
		{
			throw new WdException
			(
				'The module %module is not configured, %name is missing from the registry', array
				(
					'%module' => 'feedback.comments',
					'%name' => 'formId'
				)
			);
		}

		$form = $core->models['feedback.forms'][$form_id];

		if (!$form)
		{
			throw new WdException
			(
				'Uknown form with Id %nid', array
				(
					'%nid' => $form_id
				)
			);
		}

		WdEvent::fire
		(
			'publisher.nodes_loaded', array
			(
				'nodes' => array($form)
			)
		);

		#
		# Traget Id for the comment
		#

		$select = $args['select'];

		$nid = is_object($select) ? $select->nid : $select;

		$form->form->setHidden(Comment::NID, $nid);
		$form->form->addClass('wd-feedback-comments');

		return $template ? $patron->publish($template, $form) : $form;
	}
}