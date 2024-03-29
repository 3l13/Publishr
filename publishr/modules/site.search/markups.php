<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class site_search_WdMarkups extends patron_markups_WdHooks
{
	static public function form(array $args, WdPatron $patron, $template)
	{
		global $core, $document;

		$pageid = $core->site->metas['views.targets.site_search/search'];

		if (!$pageid)
		{
			throw new WdConfigException($core->modules['site.search']);
		}

		$document->css->add('public/page.css');
		$document->js->add('public/page.js');

		$page = $core->models['site.pages'][$pageid];
		$label = t('search.label.search');

		$tags = array
		(
			WdForm::T_VALUES => $_GET,

			WdElement::T_CHILDREN => array
			(
				'search' => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => $label,
						WdElement::T_DATASET => array
						(
							'placeholder' => $label
						),

						'class' => 'search autofocus'
					)
				),

				new WdElement
				(
					WdElement::E_SUBMIT, array
					(
						WdElement::T_INNER_HTML => $label
					)
				)
			),

			'id' => 'search-quick',
			'method' => 'get',
			'action' => $page->url
		);

		return $template ? new WdTemplatedForm($tags, $patron->publish($template)) : (string) new WdForm($tags);
	}

	// TODO: move to the module and use registry configuration.
	// TODO: user->language ?

	static protected $config = array
	(
		'url' => 'http://ajax.googleapis.com/ajax/services/search/web',
		'options' => array
		(
			'gl' => 'fr',
			'hl' => 'fr',
			'rsz' => 'large'
		)
	);

	static public function search($query, $start=0, array $options=array())
	{
		global $registry;

		$site = $registry->get('siteSearch.host');

		if (!$site)
		{
			$site = $_SERVER['HTTP_HOST'];
			$site = str_replace('www.', '', $site);
		}

		$options += self::$config['options'];



		$query = self::$config['url'] . '?' . http_build_query
		(
			array
			(
				'q' => $query . ' site:' . $site,
				'start' => $start,
				'v' => '1.0'
			)

			+ $options
		);

//		echo "query: $query" . PHP_EOL;

		$rc = file_get_contents($query);

		$response = json_decode($rc)->responseData;

		foreach ($response->results as $result)
		{
			$shortUrl = $result->unescapedUrl;
			$shortUrl = substr($shortUrl, strpos($shortUrl, $site) + strlen($site));

			$result->shortUrl = $shortUrl;
		}

		return $response;
	}

	static public function matches(array $args, WdPatron $patron, $template)
	{
		$_GET += array
		(
			'search' => null,
			'start' => 0
		);

		$search = $_GET['search'];
		$start = $_GET['start'];

		if (!$search)
		{
			return;
		}

		$response = self::search($search, $start);
		$count = count($response->results);
		$total = isset($response->cursor->estimatedResultCount) ? $response->cursor->estimatedResultCount : 0;
		$page = 0;
		$pageIndex = 0;
		$pager = null;

		if ($total && count($response->cursor->pages) > 1)
		{
			$pageIndex = $response->cursor->currentPageIndex;
			$pages = array();

			foreach ($response->cursor->pages as $i => $page)
			{
				$pages[] = ($pageIndex == $i) ? '<strong>' . $page->label . '</strong>' : '<a href="?start=' . $page->start . '&amp;search=' . wd_entities(urlencode($search)) . '">' . $page->label . '</a>';
			}

			$pager = '<div class="pager">' . implode('<span class="separator">, </span>', $pages) . '</div>';
		}

		$patron->context['self']['search'] = $search;
		$patron->context['self']['response'] = $response;
		$patron->context['self']['pager'] = $pager;
		$patron->context['self']['range'] = array
		(
			'lower' => $start + 1,
			'upper' => $start + $count,
			'start' => $start,
			'page' => $pageIndex,
			'count' => $total
		);

		return $patron->publish($template, $response->results);
	}
}