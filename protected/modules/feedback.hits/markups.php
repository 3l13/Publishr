<?php

class feedback_hits_WdMarkups  extends patron_markups_WdHooks
{
	static protected function model($name='feedback.hits')
	{
		return parent::model($name);
	}

	public static function hit(WdHook $hook, WdPatron $patron, $template)
	{
		$html = file_get_contents('hit.html', true);
		$key = uniqid();

		$_SESSION['feedback.hits.hit.uniqid'] = $key;
		
		$select = $hook->args['select'];
		$nid = is_object($select) ? $select->nid : $select;

		$html = strtr
		(
			$html, array
			(
				'#{params}' => json_encode
				(
					array
					(
						WdOperation::DESTINATION => 'feedback.hits',
						WdOperation::NAME => feedback_hits_WdModule::OPERATION_HIT,

						'nid' => $nid,
						'uniqid' => $key
					)
				)
			)
		);

		return $html;
	}

	static public function hits(WdHook $hook, WdPatron $patron, $template)
	{
		$limit = $hook->args['limit'];
		$scope = $hook->args['scope'];

		$hits = self::model()->query
		(
			'SELECT hit.*, (hits / (TO_DAYS(CURRENT_DATE) - TO_DAYS(first))) AS perday
			FROM {self} as hit
			INNER JOIN {prefix}system_nodes USING(nid)
			WHERE is_online = 1 AND constructor = ?
			ORDER BY hits DESC LIMIT ' . $limit, array
			(
				$scope
			)
		)
		->fetchAll(PDO::FETCH_OBJ);

		$nids = array();

		foreach ($hits as $hit)
		{
			$nids[$hit->nid] = $hit;
		}

		$entries = self::model($scope)->loadAll
		(
			'WHERE nid IN (' . implode(',', array_keys($nids)) . ')'
		)
		->fetchAll();

		foreach ($entries as $entry)
		{
			$nids[$entry->nid]->node = $entry;
		}

		return $patron->publish($template, array_values($nids));
	}
}