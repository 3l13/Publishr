<?php

class feedback_comments_WdHooks
{
	static public function get_comments(system_nodes_WdActiveRecord $ar)
	{
		global $core;

		return $core->models['feedback.comments']->loadAll
		(
			'WHERE nid = ? AND status = "approved" ORDER by created', array
			(
				$ar->nid
			)
		)
		->fetchAll();
	}

	static public function get_comments_count(system_nodes_WdActiveRecord $ar)
	{
		global $core;

		return $core->models['feedback.comments']->count
		(
			null, null, 'WHERE nid = ? AND status = "approved"', array
			(
				$ar->nid
			)
		);
	}
}