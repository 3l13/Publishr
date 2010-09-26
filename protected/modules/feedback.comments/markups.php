<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class feedback_comments_WdMarkups extends patron_markups_WdHooks
{
	static protected function model($name="feedback.comments")
	{
		return parent::model($name);
	}

	static public function comments(array $args, WdPatron $patron, $template)
	{
		extract($args, EXTR_PREFIX_ALL, 'p');

		#
		# build sql query
		#

		$where = array
		(
			'status = "approved"'
		);

		$params = array();

		$node = $args['node'];

		if ($node)
		{
			$where[] = '`nid` = ?';
			$params[] = $node;
		}

		if ($p_noauthor)
		{
			$where[] = '(SELECT uid FROM {prefix}system_nodes WHERE nid = comment.nid) != IFNULL(uid, 0)';
		}

		$query = $where ? ' WHERE ' . implode(' AND ', $where) : '';

		if ($p_by)
		{
			$query .= ' ORDER BY `' . $p_by . '` ' . ($p_order == 'desc' ? 'DESC' : 'ASC');
		}

		if ($p_limit)
		{
			$entries = self::model()->loadRange($p_limit * $p_page, $p_limit, $query, $params);
		}
		else
		{
			$entries = self::model()->loadAll($query, $params);
		}

		$entries = $entries->fetchAll();

		if (!$entries && !$p_parseempty)
		{
			return;
		}

		#
		#
		#

		return $patron->publish($template, $entries);
	}

	static public function form(array $args, WdPatron $patron, $template)
	{
		#
		# Obtain the form to use to add a comment from the 'feedback.forms' module.
		#

		global $registry;

		$formId = $registry['feedbackComments.formId'];

		if (!$formId)
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

		$form = self::model('feedback.forms')->load($formId)->translation;

		if (!$form)
		{
			throw new WdException
			(
				'Uknown form with Id %nid', array
				(
					'%nid' => $formId
				)
			);
		}

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