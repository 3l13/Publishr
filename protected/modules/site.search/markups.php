<?php

class site_search_WdMarkups extends patron_markups_WdHooks
{
	static public function form(WdHook $hook, WdPatron $patron, $template)
	{
		global $core, $registry;

		$pageid = $registry->get('siteSearch.url');

		if (!$pageid)
		{
			$patron->error('Target page is missing, please check <em>site.search</em> configuration');

			return;
		}

		$page = $core->getModule('site.pages')->model()->load($pageid);

		if (!$page)
		{
			$patron->error('Unknown target %nid', array('%nid' => $pageid));

			return;
		}

		$translation = $page->translation;

		if ($translation)
		{
			$page = $translation;
		}

		$tags = array
		(
			WdForm::T_VALUES => $_GET,

			WdElement::T_CHILDREN => array
			(
				'search' => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => 'Search',

						'class' => 'search autofocus'
					)
				),

				new WdElement
				(
					WdElement::E_SUBMIT, array
					(
						WdElement::T_INNER_HTML => 'Search'
					)
				)
			),

			'method' => 'get',
			'action' => $page->url
		);

		return $template ? new WdTemplatedForm($tags, $patron->publish($template)) : (string) new Wd2CForm($tags);
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

	static public function matches(WdHook $hook, WdPatron $patron, $template)
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
		$total = $response->cursor->estimatedResultCount;
		$page = 0;
		$pageIndex = 0;
		$pager = null;

		if (count($response->cursor->pages) > 1)
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