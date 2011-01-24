<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

// http://www.google.com/webmasters/docs/search-engine-optimization-starter-guide.pdf

class site_firstposition_WdModule extends WdPModule
{
	static public function event_alter_block_edit(WdEvent $event)
	{
		global $core;

		if ($event->target instanceof site_sites_WdModule)
		{
			$event->tags = wd_array_merge_recursive
			(
				$event->tags, array
				(
					WdElement::T_GROUPS => array
					(
						'firstposition' => array
						(
							'title' => 'Référencement',
							'class' => 'form-section flat',
							'weight' => 40
						)
					),

					WdElement::T_CHILDREN => array
					(
						'metas[google_analytics_ua]' => new WdElement
						(
							WdElement::E_TEXT, array
							(
								WdForm::T_LABEL => 'Google Analytics UA',
								WdElement::T_GROUP => 'firstposition'
							)
						),

						'metas[google_site_verification]' => new WdElement
						(
							WdElement::E_TEXT, array
							(
								WdForm::T_LABEL => 'Google Site Verification',
								WdElement::T_GROUP => 'firstposition'
							)
						)
					)
				)
			);

			return;
		}
		else if (!$event->target instanceof site_pages_WdModule || empty($core->modules['site.firstposition']))
		{
			return;
		}

		#
		# http://www.google.com/support/webmasters/bin/answer.py?answer=35264&hl=fr
		# http://googlewebmastercentral.blogspot.com/2009/09/google-does-not-use-keywords-meta-tag.html
		# http://www.google.com/support/webmasters/bin/answer.py?answer=79812
		#

		$event->tags = wd_array_merge_recursive
		(
			$event->tags, array
			(
				WdElement::T_GROUPS => array
				(
					'firstposition' => array
					(
						'title' => 'Référencement',
						'class' => 'form-section flat',
						'weight' => 40
					)
				),

				WdElement::T_CHILDREN => array
				(
					'metas[document_title]' => new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'Title',
							WdElement::T_GROUP => 'firstposition',
							WdElement::T_DESCRIPTION => "Généralement affiché comme titre dans les
							résultats de recherche des moteurs tels que Google (et bien sûr dans le navigateur des internautes).
							Si le champ est vide, le titre général de la page est utilisé."
						)
					),

					'metas[description]' => new WdElement
					(
						'textarea', array
						(
							WdForm::T_LABEL => 'Description',
							WdElement::T_GROUP => 'firstposition',
							WdElement::T_DESCRIPTION => "Brève description de la page. Dans certains
							cas, cette description est incluse dans l'extrait qui s'affiche avec les
							résultats de recherche.",

							'rows' => 3
						)
					)
				)
			)
		);
	}

	public function event_publisher_publish(WdEvent $event)
	{
		global $core, $page;

		if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || $core->user_id == 1 || !$page->is_online || ($page->node && !$page->node->is_online))
		{
			return;
		}

		$ua = $core->site->metas['google_analytics_ua'];

		if (!$ua)
		{
			return;
		}

		// http://googlecode.blogspot.com/2009/12/google-analytics-launches-asynchronous.html
		// http://code.google.com/intl/fr/apis/analytics/docs/tracking/asyncUsageGuide.html
		// http://www.google.com/support/googleanalytics/bin/answer.py?answer=174090&cbid=-yb2wwt7lxo0o&src=cb&lev=%20index
		// http://developer.yahoo.com/blogs/ydn/posts/2007/07/high_performanc_5/

		$insert = <<<EOT


<script type="text/javascript">

	var _gaq = _gaq || [];
	_gaq.push(['_setAccount', '$ua']);
	_gaq.push(['_trackPageview']);

	(function() {
		var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
		ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
		var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
	})();

</script>


EOT;

		$event->rc = str_replace('</body>', $insert . '</body>', $event->rc);
	}

	static public function markup_document_title(array $args, WdPatron $patron, $template)
	{
		global $page;

		$title = $page->document_title;

		if ($template)
		{
			return $patron->publish($template, $title);
		}

		$site_title = $page->site->title;

		return '<title>' . wd_entities($title . ' | ' . $site_title) . '</title>';
	}

	static public function markup_document_metas(array $args, WdPatron $patron, $template)
	{
		global $page;

		$node = isset($page->node) ? $page->node : null;

		$rc = '<meta http-equiv="Content-Type" content="text/html; charset=' . WDCORE_CHARSET . '" />' . PHP_EOL;

		#
		#
		#

		$description = $page->description;

		if ($node instanceof contents_WdActiveRecord)
		{
			$description = $page->node->excerpt;
		}

		if ($description)
		{
			$description = html_entity_decode($description, ENT_COMPAT, WDCORE_CHARSET);
			$description = trim(strip_tags($description));

			$rc .= '<meta name="Description" content="' . wd_entities($description) . '" />' . PHP_EOL;
		}

		#
		#
		#

		if ($page->is_home)
		{
			$value = $page->site->metas['google_site_verification'];

			if ($value)
			{
				$rc .= '<meta name="google-site-verification" content="' . $value . '" />' . PHP_EOL;
			}
		}

		#
		# canonical
		#

//		http://yoast.com/articles/duplicate-content/

		if ($node && $node->has_property('absolute_url'))
		{
			$rc .= '<link rel="canonical" href="' . $node->absolute_url . '" />' . PHP_EOL;
		}

		return $rc;
	}
}