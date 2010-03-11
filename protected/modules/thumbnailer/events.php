<?php

class thumbnailer_WdEvents
{
	static public function alter_block_config(WdEvent $ev)
	{
		$module_id = (string) $ev->module;

		$configs = array();

		foreach (thumbnailer_WdModule::$config as $version_name => $config)
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

		foreach ($params['thumbnailer']['versions'] as $name => &$version)
		{
			$version += array
			(
				'no-upscale' => false,
				'interlace' => false
			);

			$version['no-upscale'] = filter_var($version['no-upscale'], FILTER_VALIDATE_BOOLEAN);
			$version['interlace'] = filter_var($version['interlace'], FILTER_VALIDATE_BOOLEAN);
		}
	}
}