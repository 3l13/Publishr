<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class feedback_hits_WdMarkups  extends patron_markups_WdHooks
{
	static protected function model($name='feedback.hits')
	{
		return parent::model($name);
	}

	public static function hit(array $args, WdPatron $patron, $template)
	{
		global $core, $document;

		$document->js->add('public/hit.js');

		$key = uniqid();

		$core->session->modules['feedback.hits']['uniqid'] = $key;

		$select = $args['select'];
		$nid = is_object($select) ? $select->nid : $select;

		return <<<EOT
<script type="text/javascript">

var feedback_hits_nid = $nid;

</script>
EOT;
	}

	static public function hits(array $args, WdPatron $patron, $template)
	{
		$limit = $args['limit'];
		$scope = $args['scope'];

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