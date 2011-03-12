<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

// TODO-20100614: use the new inherited features

class feedback_forms_WdMarkups extends patron_markups_WdHooks
{
	static protected function model($name='feedback.forms')
	{
		return parent::model($name);
	}

	static public function form(array $args, WdPatron $patron, $template)
	{
		$id = $args['select'];

		if (is_numeric($id))
		{
			$form = self::model()->find($id);
		}
		else
		{
			list($conditions, $conditions_args) = self::model()->parseConditions(array('slug' => $id, 'language' => WdI18n::$language));

			$form = self::model()->where(implode(' AND ', $conditions), $conditions_args)->one;
		}

		if (!$form)
		{
			throw new WdException('Unable to retrieve form using supplied conditions: %conditions', array('%conditions' => json_encode($args['select'])));
		}

		WdEvent::fire
		(
			'publisher.nodes_loaded', array
			(
				'nodes' => array($form)
			)
		);

		if (!$form->is_online)
		{
			throw new WdException('The form %title is offline', array('%title' => $form->title));
		}

		return (string) $form;
	}
}