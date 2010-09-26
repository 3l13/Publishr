<?php

class site_WdModule extends WdPModule
{
	protected function block_config($base)
	{
		return array
		(
			WdElement::T_GROUPS => array
			(
				'primary' => array
				(
					'title' => 'Général',
					'class' => 'form-section flat'
				)
			),

			WdElement::T_CHILDREN => array
			(
				'site[title]' => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => 'Title du site'
					)
				),

				/*
				'site[base]' => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => 'URL de base du site <span class="small">(site.base)</span>'
					)
				),
				*/

				'site[analytics][ua]' => new WdElement
				(
					WdElement::E_TEXT, array
					(
 						WdForm::T_LABEL => 'Google Analytics UA'
					)
				)
			)
		);
	}

	public function event_publisher_publish(WdEvent $event)
	{
		global $registry;

		if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false)
		{
			return;
		}

		$ua = $registry['site.analytics.ua'];

		if (!$ua)
		{
			return;
		}

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
}
