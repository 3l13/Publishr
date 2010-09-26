<?php

class thumbnailer_WdEvents
{
	static public function alter_block_config(WdEvent $ev)
	{
		$module_id = (string) $ev->module;

		$c = WdConfig::get_constructed('thumbnailer', array(__CLASS__, 'config_construct'));

		$configs = array();

		foreach ($c as $version_name => $config)
		{
			if (empty($config['module']) || $config['module'] != $module_id)
			{
				continue;
			}

			$configs[$version_name] = $config;
		}

		if (!$configs)
		{
			return;
		}

		$children = array();

		foreach ($configs as $version_name => $config)
		{
			list($defaults) = $config;

			$config += array
			(
				'description' => null
			);

			$children['thumbnailer[versions][' . $version_name . ']'] = new WdThumbnailerConfigElement
			(
				array
				(
					WdForm::T_LABEL => $config['title'] . ' <small>(' . $version_name . ')</small>',
					WdElement::T_DEFAULT => $defaults,
					WdElement::T_GROUP => 'thumbnailer',
					WdElement::T_DESCRIPTION => $config['description']
				)
			);
		}

		$ev->tags = wd_array_merge_recursive
		(
			$ev->tags, array
			(
				WdElement::T_GROUPS => array
				(
					'thumbnailer' => array
					(
						'title' => 'Miniatures'
					)
				),

				WdElement::T_CHILDREN => $children
			)
		);
	}

	static public function config_before(WdEvent $ev)
	{
		$params = &$ev->operation->params;

		if (empty($params['thumbnailer']['versions']))
		{
			return;
		}

		$c = WdConfig::get_constructed('thumbnailer', array(__CLASS__, 'config_construct'));

		//wd_log('c: \1', array($c));

		foreach ($params['thumbnailer']['versions'] as $name => &$version)
		{
			$version += $c[$name][0] + array
			(
				'no-upscale' => false,
				'interlace' => false
			);

			$version['no-upscale'] = filter_var($version['no-upscale'], FILTER_VALIDATE_BOOLEAN);
			$version['interlace'] = filter_var($version['interlace'], FILTER_VALIDATE_BOOLEAN);

//			wd_log('version: \1', array($version));
		}
	}

	static public function config_construct($configs)
	{
		return call_user_func_array('array_merge', $configs);
	}
}