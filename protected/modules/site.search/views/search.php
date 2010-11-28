<?php

require_once dirname(dirname(__FILE__)) . '/api.php';

$_home_limit = 5;
$_list_limit = 5;

WdI18n::store_translation
(
	'en', array
	(
		'search' => array
		(
			'found' => array
			(
				'none' => 'Aucun résultat trouvé.',
				'one' => 'Un résultat trouvé.',
				'other' => ':count résultats trouvés.'
			),

			'more' => array
			(
				'one' => 'Voir le résultat trouvé pour %search',
				'other' => 'Voir les :count résultats trouvés pour %search'
			)
		),

		'module.site_pages.search' => array
		(
			'found' => array
			(
				'none' => 'Aucun résulat trouvé dans les pages.',
				'one' => 'Un résultat trouvé dans les pages.',
				'other' => ':count résultats trouvés dans les pages.'
			),

			'more' => array
			(
				'one' => 'Voir le résultat trouvé pour %search dans les pages',
				'other' => 'Voir les :count résultats trouvés pour %search dans les pages'
			)
		)
	)
);


#
#
#

$constructors = array
(
	'site.pages',
	'contents.news',
	'contents.articles'
);

foreach ($constructors as $i => $constructor)
{
	if ($core->hasModule($constructor))
	{
		continue;
	}

	unset($constructors[$i]);
}

//$constructors[] = 'google';

$constructors_options = array(null => '<tout>');

foreach ($constructors as $constructor)
{
	if ($constructor == 'google')
	{
		$constructors_options[$constructor] = 'Google';

		continue;
	}

	$constructors_options[$constructor] = $core->descriptors[$constructor][WdModule::T_TITLE];
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
					WdForm::T_LABEL => 'Mots clé'
				)
			),

			'constructor' => new WdElement
			(
				'select', array
				(
					WdForm::T_LABEL => 'Rechercher dans',
					WdElement::T_OPTIONS => $constructors_options
				)
			),

			new WdElement
			(
				WdElement::E_SUBMIT, array
				(
					WdElement::T_INNER_HTML => 'Rechercher'
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

$document->css->add('../public/search.css');

$search = $_GET['search'];
$position = isset($_GET['page']) ? (int) $_GET['page'] : 0;

if (empty($_GET['constructor']))
{
	$position = 0;
}

echo '<div id="search-matches">';

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
		else
		{
			list($entries, $count) = query_contents($constructor, $search, $position, $_list_limit);
		}
	}

	echo make_set($constructor, $entries, $count, $search, true);
}

echo '</div>';