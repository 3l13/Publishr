<?php

class system_cache_WdModule extends WdPModule
{
	protected function block_manage()
	{
		global $document;

		$document->css->add('public/css/manage.css');
		$document->css->add('public/manage.css');
		$document->js->add('public/manage.js');

		$caches = array
		(
			'core.assets' => array
			(
				'title' => 'CSS et Javascript',
				'description' => "Jeux compilés de sources CSS et Javascript.",
				'group' => 'system',
				'state' => WdCore::$config['cache assets'],
				'size_limit' => false,
				'time_limit' => false
			),

			'core.catalogs' => array
			(
				'title' => 'Traductions',
				'description' => "Traductions par langue pour l'ensemble du framework.",
				'group' => 'system',
				'state' => WdCore::$config['cache catalogs'],
				'size_limit' => false,
				'time_limit' => false
			),

			'core.configs' => array
			(
				'title' => 'Configurations',
				'description' => "Configurations des différents composants du framework.",
				'group' => 'system',
				'state' => WdCore::$config['cache configs'],
				'size_limit' => false,
				'time_limit' => false
			),

			'core.modules' => array
			(
				'title' => 'Modules',
				'description' => "Index des modules disponibles pour le framework.",
				'group' => 'system',
				'state' => WdCore::$config['cache modules'],
				'size_limit' => false,
				'time_limit' => false
			)
		);

		WdEvent::fire
		(
			'alter.block.manage', array
			(
				'target' => $this,
				'caches' => &$caches
			)
		);

		$groups = array();

		asort($caches);

		foreach ($caches as $cache_id => $cache)
		{
			$group = $cache['group'];
			$group = t($group, array(), array('scope' => array('system', 'modules', 'categories'), 'default' => ucfirst($group)));

			$groups[$group][$cache_id] = $cache;
		}

//		uksort($groups, 'wd_unaccent_compare_ci');

		$rows = '';

		foreach ($groups as $group_title => $group)
		{
			$rows .= <<<EOT
<tr class="group-title">
	<td>&nbsp;</td>
	<td>$group_title</td>
	<td colspan="5">&nbsp;</td>
</tr>
EOT;

			foreach ($group as $cache_id => $definition)
			{
				$checked = $definition['state'];

				$checkbox = new WdElement
				(
					'label', array
					(
						WdElement::T_CHILDREN => array
						(
							new WdElement
							(
								WdElement::E_CHECKBOX, array
								(
									'checked' => $checked,
									'disabled' => $definition['state'] === null,
									'name' => $cache_id
								)
							)
						),

						'title' => "Cliquer pour activer ou désactiver le cache",
						'class' => 'checkbox-wrapper circle' . ($checked ? ' checked': '')
					)
				);

				$title = wd_entities($definition['title']);
				$description = $definition['description'];

				$size_limit = null;

				if ($definition['size_limit'])
				{
					list($value, $unit) = $definition['size_limit'];

					$size_limit = new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdElement::T_LABEL => $unit,

							'name' => 'size_limit',
							'size' => 4,
							'value' => $value
						)
					);
				}

				$time_limit = null;

				if ($definition['time_limit'])
				{
					list($value, $unit) = $definition['time_limit'];

					$time_limit = new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdElement::T_LABEL => $unit,

							'name' => 'time_limit',
							'size' => 4,
							'value' => $value
						)
					);
				}

				$rows .= <<<EOT
<tr>
	<td class="state">$checkbox</td>
	<td class="title">$title<div class="element-description">$description</div></td>
	<td class="limit">$size_limit &nbsp; $time_limit</td>
	<td class="usage empty">&nbsp;</td>
	<td class="erase"><button type="button" class="warn" name="clear">Vider</button></td>
</tr>
EOT;
			}
		}

		$rc = <<<EOT
<table class="wdform manage" cellpadding="0" cellspacing="0" border="0" width="100%">
	<thead>
		<tr>
			<th colspan="2">&nbsp;</th>
			<th>Limites <span class="small">(Taille et durée)</span></th>
			<th class="right">Utilisation</th>
			<th>&nbsp;</th>
		</tr>
	</thead>

	<tbody>$rows</tbody>
</table>
EOT;

		return $rc;
	}

	private function change_config_cache($name, $value)
	{
		$path = $_SERVER['DOCUMENT_ROOT'] . '/protected/all/config/core.php';

		$old = $value ? 'false' : 'true';
		$value = $value ? 'true' : 'false';

		$content = file_get_contents($path);
		$new_content = str_replace("'cache $name' => $old", "'cache $name' => $value", $content);

		if ($content == $new_content)
		{
			return false;
		}

		file_put_contents($path, $new_content);

		return true;
	}

	/*
	 * OPERATION_CLEAR
	 */

	static private $internal = array('core.assets', 'core.catalogs', 'core.configs', 'core.modules');

	protected function handle_operation_control(WdOperation $operation)
	{
		$operation_name = $operation->name;

		if (in_array($operation_name, array('activate', 'clear', 'deactivate', 'usage')))
		{
			if (!$this->control_permission_for_operation($operation, self::PERMISSION_ADMINISTER))
			{
				return false;
			}

			$cache_id = $operation->key;

			if ($operation_name != 'activate' && $operation_name != 'deactivate'
			|| (($operation_name == 'activate' || $operation_name == 'deactivate') && !in_array($cache_id, self::$internal)))
			{
				$operation->callback = $callback = 'operation_' . $operation_name . '_for_' . wd_normalize($operation->key, '_');

				if (!$this->has_method($callback))
				{
					throw new WdException
					(
						"Unable to perform the %operation operation on the %name cache, the %callback callback is missing.", array
						(
							'%callback' => $callback,
							'%operation' => $operation_name,
							'%name' => $operation->key
						),

						404
					);
				}
			}

			return true;
		}

		return parent::handle_operation_control($operation, $controls);
	}

	protected function operation_activate(WdOperation $operation)
	{
		$cache_id = $operation->key;

		if (in_array($cache_id, self::$internal))
		{
			$operation->response->pouic = "activate $cache_id";

			return $this->change_config_cache(substr($cache_id, 5), true);
		}

		return $this->{$operation->callback}($operation);
	}

	protected function operation_clear(WdOperation $operation)
	{
		return $this->{$operation->callback}($operation);
	}

	public function clear_files($path, $pattern=null)
	{
		$root = $_SERVER['DOCUMENT_ROOT'];

		if (!is_dir($root . $path))
		{
			return false;
		}

		$n = 0;
		$dh = opendir($root . $path);

		while (($file = readdir($dh)) !== false)
		{
			if ($file{0} == '.' || ($pattern && !preg_match($pattern, $file)))
			{
				continue;
			}

			$n++;
			unlink($root . $path . '/' . $file);
		}

		return $n;
	}

	protected function operation_clear_for_core_catalogs(WdOperation $operation)
	{
		$path = WdCore::$config['repository.cache'] . '/core';

		$files = glob($_SERVER['DOCUMENT_ROOT'] . $path . '/i18n_*');

		foreach ($files as $file)
		{
			unlink($file);
		}

		return count($files);
	}

	protected function operation_clear_for_core_assets(WdOperation $operation)
	{
		$path = WdCore::$config['repository.files'] . '/assets';

		$files = glob($_SERVER['DOCUMENT_ROOT'] . $path . '/*');

		foreach ($files as $file)
		{
			unlink($file);
		}

		return count($files);
	}

	protected function operation_clear_for_core_configs(WdOperation $operation)
	{
		$path = WdCore::$config['repository.cache'] . '/core';
		$files = glob($_SERVER['DOCUMENT_ROOT'] . $path . '/config_*');

		foreach ($files as $file)
		{
			unlink($file);
		}

		return count($files);
	}

	protected function operation_clear_for_core_modules(WdOperation $operation)
	{
		$path = WdCore::$config['repository.cache'] . '/core';
		$files = glob($_SERVER['DOCUMENT_ROOT'] . $path . '/modules_*');

		foreach ($files as $file)
		{
			unlink($file);
		}

		return count($files);
	}

	protected function operation_deactivate(WdOperation $operation)
	{
		$cache_id = $operation->key;

		if (in_array($cache_id, self::$internal))
		{
			return $this->change_config_cache(substr($cache_id, 5), false);
		}

		return $this->{$operation->callback}($operation);
	}

	/*
	 * USAGE
	 */

	public function get_files_usage($path, $pattern=null)
	{
		$root = $_SERVER['DOCUMENT_ROOT'];

		if (!file_exists($root . $path))
		{
			return array
			(
				0, '<span class="warn">Dossier manquant&nbsp: <em>' . $path . '</em></span>'
			);
		}
		else if (!is_writable($root . $path))
		{
			return array
			(
				0, '<span class="warn">Dossier vérouillé en écriture&nbsp: <em>' . $path . '</em></span>'
			);
		}

		$n = 0;
		$size = 0;

		$dh = opendir($root . $path);

		while (($file = readdir($dh)) !== false)
		{
			if ($file{0} == '.' || ($pattern && !preg_match($pattern, $file)))
			{
				continue;
			}

			$n++;
			$size += filesize($root . $path . '/' . $file);
		}

		if (!$n)
		{
			return array(0, 'Le cache est vide');
		}

		return array
		(
			$n, $n . ' fichiers<br /><span class="small">' . wd_format_size($size) . '</span>'
		);
	}

	protected function operation_usage(WdOperation $operation)
	{
		$usage = $this->{$operation->callback}($operation);

		$operation->response->count = (int) $usage[0];

		return $usage[1];
	}

	protected function operation_usage_for_core_assets(WdOperation $operation)
	{
		$path = WdCore::$config['repository.files'] . '/assets';

		return $this->get_files_usage($path);
	}

	protected function operation_usage_for_core_catalogs(WdOperation $operation)
	{
		$path = WdCore::$config['repository.cache'] . '/core';

		return $this->get_files_usage($path, '#^i18n_#');
	}

	protected function operation_usage_for_core_configs(WdOperation $operation)
	{
		$path = WdCore::$config['repository.cache'] . '/core';

		return $this->get_files_usage($path, '#^config_#');
	}

	protected function operation_usage_for_core_modules(WdOperation $operation)
	{
		$path = WdCore::$config['repository.cache'] . '/core';

		return $this->get_files_usage($path, '#^modules_#');
	}
}