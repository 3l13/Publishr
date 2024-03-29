<?php

require_once dirname(dirname(__FILE__)) . '/api.php';

$_home_limit = $core->site->metas->get('site_search.limits.home', 5);
$_list_limit = $core->site->metas->get('site_search.limits.list', 10);

#
#
#

$module = $core->modules['site.search'];

$constructors = $core->site->metas['site_search.scope'];

if (!count($constructors))
{
	throw new WdConfigException($module);
}

$constructors = explode(',', $constructors);

foreach ($constructors as $i => $constructor)
{
	if (isset($core->modules[$constructor]))
	{
		continue;
	}

	unset($constructors[$i]);
}

//$constructors[] = 'google';

$constructors_options = array(null => t('search.option.all'));

foreach ($constructors as $constructor)
{
	if ($constructor == 'google')
	{
		$constructors_options[$constructor] = 'Google';

		continue;
	}

	$constructors_options[$constructor] = t(strtr($constructor, '.', '_'), array(), array('scope' => array('module', 'title'), 'default' => $core->modules->descriptors[$constructor][WdModule::T_TITLE]));
}

echo new Wd2CForm
(
	array
	(
		WdForm::T_VALUES => $_GET,

		WdElement::T_CHILDREN => array
		(
			'search' => new WdElement
			(
				WdElement::E_TEXT, array
				(
					WdForm::T_LABEL => t('search.label.keywords')
				)
			),

			'constructor' => new WdElement
			(
				'select', array
				(
					WdForm::T_LABEL => t('search.label.in'),
					WdElement::T_OPTIONS => $constructors_options
				)
			),

			new WdElement
			(
				WdElement::E_SUBMIT, array
				(
					WdElement::T_INNER_HTML => t('search.label.search')
				)
			)
		),

		'method' => 'get'
	)
);

if (empty($_GET['search']))
{
	return;
}

$document->css->add('../public/page.css');

$search = $_GET['search'];
$position = isset($_GET['page']) ? (int) $_GET['page'] : 0;

if (empty($_GET['constructor']))
{
	$position = 0;
}

if (empty($_GET['constructor']))
{
	foreach ($constructors as $constructor)
	{
		if ($constructor == 'google')
		{
			list($entries, $count) = query_google($search, 0, $_home_limit);
		}
		else
		{
			$model = $core->models[$constructor];

			if ($model instanceof site_pages_WdModel)
			{
				list($entries, $count) = query_pages($search, 0, $_home_limit);
			}
			else
			{
				list($entries, $count) = query_contents($constructor, $search, 0, $_home_limit);
			}
		}

		echo make_set($constructor, $entries, $count, $search);
	}
}
else if (!in_array($_GET['constructor'], $constructors))
{
	echo t("Le constructeur %constructor n'est pas supporté pour la recherche", array('%constructor' => $_GET['constructor']));
}
else
{
	$constructor = $_GET['constructor'];

	if ($constructor == 'google')
	{
		list($entries, $count) = query_google($search, $position, $_list_limit);
	}
	else
	{
		$model = $core->models[$constructor];

		if ($model instanceof site_pages_WdModel)
		{
			list($entries, $count) = query_pages($search, $position, $_list_limit);
		}
		else if ($model instanceof contents_WdModel)
		{
			list($entries, $count) = query_contents($constructor, $search, $position, $_list_limit);
		}
		else
		{
			echo "<p>Don't know how to query: <em>$constructor</em></p>";
		}
	}

	echo make_set($constructor, $entries, $count, $search, true);
}