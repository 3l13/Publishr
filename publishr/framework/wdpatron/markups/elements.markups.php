<?php

/**
 * This file is part of the WdPatron software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.weirdog.com/wdpatron/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.weirdog.com/wdpatron/license/
 */

class patron_elements_WdMarkups
{
	public static function pager(array $args, WdPatron $patron, $template)
	{
		extract($args);

		if ($range)
		{
			$count = $range['count'];
			$limit = $range['limit'];
			$page = isset($range['page']) ? $range['page'] : 0;

			if (isset($range['with']))
			{
				$with = $range['with'];
			}
		}

		$pager = new WdPager
		(
			'div', array
			(
				WdPager::T_COUNT => $count,
				WdPager::T_LIMIT => $limit,
				WdPager::T_POSITION => $page,
				WdPager::T_NO_ARROWS => $noarrows,
				WdPager::T_WITH => $with,

				'class' => 'pager'
			)
		);

		return $template ? $patron->publish($template, $pager) : (string) $pager;
	}

	static public function document_css(array $args, WdPatron $patron, $template)
	{
		global $document;

		if (isset($args['add']))
		{
			$file = $patron->get_file();

			wd_log(__FILE__ . '::' . __FUNCTION__ . '::file: \1', array($file));

			$document->css->add($args['add'], dirname($file));

			return;
		}

		return $template ? $patron->publish($template, $document->css) : (string) $document->css;
	}

	static public function document_js(array $args, WdPatron $patron, $template)
	{
		global $document;

		return $template ? $patron->publish($template, $document->js) : (string) $document->js;
	}
}