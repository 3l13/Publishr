<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class contents_agenda_view_WdMarkup extends contents_view_WdMarkup
{
	protected $constructor = 'contents.agenda';
}

class contents_agenda_WdMarkups extends contents_WdMarkups
{
	static protected function model($name='contents.agenda')
	{
		return parent::model($name);
	}

	static public function home(array $args, WdPatron $patron, $template)
	{
		global $registry;

		list($conditions, $values) = self::model()->parseConditions
		(
			array
			(
				'constructor' => 'contents.agenda',
				'language' => WdLocale::$language,
				'is_online' => true,
				'is_home_excluded' => false
			)
		);

		$conditions[] = 'date >= CURRENT_DATE';

		$entries = self::model()->loadRange
		(
			0, $registry->get('contentsAgenda.homeLimit', 5), 'WHERE ' . implode(' AND ', $conditions) . ' ORDER BY date ASC', $values
		)
		->fetchAll();

		if (!$entries)
		{
			return;
		}

		$by_month = array();

		foreach ($entries as $entry)
		{
			$month = substr($entry->date, 0, 7) . '-01';

			$by_month[$month][] = $entry;
		}

		return $patron->publish($template, $by_month);
	}

	static public function dates(array $args, WdPatron $patron, $template)
	{
		global $app;

		$select = $args['select'];

		if ($select)
		{
			$entry = self::model()->load($select);

			if (!$entry->is_online && $app->user->is_guest())
			{
				return '<p>Cet objet est désactivé</p>';
			}

			return $patron->publish($template, $entry);
		}
		else
		{
			$page = $args['page'];
			$limit = $args['limit'];

			$where = array
			(
				'is_online = 1',
				'constructor = "contents.agenda"',
				'(language = "" OR language = ?)',
				'date >= CURRENT_DATE'
			);

			$params = array
			(
				WdLocale::$language
			);

			$where = 'WHERE ' . implode(' AND ', $where);;

			$count = self::model()->count(null, null, $where, $params);

			$entries = self::model()->loadRange
			(
				$page * $limit, $limit, $where . ' ORDER BY date ASC, title', $params
			)
			->fetchAll();

			if (!$entries)
			{
				return;
			}

			$patron->context['self']['range'] = array
			(
				'count' => $count,
				'page' => $page,
				'limit' => $limit
			);

			return $patron->publish($template, $entries);
		}
	}
}