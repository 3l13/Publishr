<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class feedback_comments_WdEvents
{
	static protected function model()
	{
		global $core;

		return $core->getModule('feedback.comments')->model();
	}

	static public function operation_delete(WdEvent $event)
	{
		global $core;

		if (!($event->module instanceof system_nodes_WdModule))
		{
			return;
		}

		if (!$core->hasModule('feedback.comments'))
		{
			return;
		}

		try
		{
			$model = self::model();
		}
		catch (Exception $e)
		{
			return;
		}

		$ids = $model->select
		(
			'{primary}', 'WHERE nid = ?', array($event->operation->key)
		)
		->fetchAll(PDO::FETCH_COLUMN);

		foreach ($ids as $commentid)
		{
			$model->delete($commentid);
		}
	}

	static public function ar_property(WdEvent $event)
	{
		switch ($event->property)
		{
			case 'comments':
			{
				$event->value = self::model()->loadAll
				(
					'WHERE nid = ? AND status != "spam" ORDER by created', array
					(
						$event->ar->nid
					)
				)
				->fetchAll();

				$event->stop();
			}
			break;

			case 'commentsCount':
			{
				$event->value = self::model()->count
				(
					null, null, 'WHERE nid = ? AND status != "spam"', array
					(
						$event->ar->nid
					)
				);

				$event->stop();
			}
			break;
		}
	}
}