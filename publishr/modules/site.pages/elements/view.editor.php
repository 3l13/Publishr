<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class view_WdEditorElement extends WdEditorElement
{
	static protected $views = array();

	static public function __static_construct()
	{
		global $core;

		self::$views = $core->configs->synthesize('views', array(__CLASS__, '__static_construct_callback'));
	}

	static public function __static_construct_callback($configs)
	{
		$views = array();

		//wd_log('callback configs: \1', array($configs));

		foreach ($configs as $root => $definitions)
		{
			$module_id = basename($root);

			foreach ($definitions as $id => $definition)
			{
				if ($id[0] == '/')
				{
					$id = $module_id . $id;
				}

				#
				# FIXME-20100602: This is a compat fix 'contents.articles.list' => 'contents.articles/list'
				#

				else if ((strpos($id, '/') === false) && ((strrpos($id, '.') !== false)))
				{
					$id[strrpos($id, '.')] = '/';
				}

				$local_module_id = isset($view['module']) ? $view['module'] : $module_id;

				$definition['root'] = $root;

				if (empty($definition['file']) && empty($definition['block']))
				{
					list($name, $type) = explode('/', $id) + array(1 => null);

					$definition['file'] = ($type ? $type : $name);// . '.html';
				}

				if (isset($definition['block']) && empty($definition['module']))
				{
					$definition['module'] = $module_id;
				}

				if ($local_module_id && empty($definition['scope']))
				{
					$definition['scope'] = strtr($local_module_id, '.', '_');
				}

				if (isset($definition['file']) && $definition['file'][0] != '/')
				{
					$file = $root . '/views/' . $definition['file'];

					if (!file_exists($file))
					{
						$file = file_exists($file . '.php') ? $file . '.php' : $file . '.html';
					}

					$definition['file'] = $file;
				}

				$views[$id] = $definition;
			}
		}

		return $views;
	}

	static public function to_content(array $params, $content_id, $page_id)
	{
		global $core;

		$content = parent::to_content($params, $content_id, $page_id);

		if ($content)
		{
			#
			# FIXME-20100602: This is a compat fix 'contents.articles.list' => 'contents.articles/list'
			#

			if (strpos($content, '/') === false)
			{
				$pos = strpos($content, '.');

				if ($pos !== false)
				{
					$content[$pos] = '/';
				}
			}

			if (strpos($content, '/') !== false)
			{
				$view_target_key = 'views.targets.' . strtr($content, '.', '_');

				$core->site->metas[$view_target_key] = $page_id;
			}
		}

		return $content;
	}

	static public function render($id/*, $patron, $template*/)
	{
		global $core, $document, $page;

		$patron = WdPatron::getSingleton();

		if (empty(self::$views[$id]))
		{
			throw new WdException('Unknown view: %id', array('%id' => $id));
		}

		$view = self::$views[$id];

		#
		# access_callback
		#

		if (isset($view['access_callback']))
		{
			$access_callback = $view['access_callback'];

			if (!call_user_func($access_callback))
			{
				throw new WdHTTPException
				(
					'The requested URL %uri requires authentication.', array
					(
						'%uri' => $_SERVER['REQUEST_URI']
					),

					401
				);
			}
		}

		#
		# assets
		#

		$root = $view['root'];

		if (isset($view['assets']['js']))
		{
			$assets = (array) $view['assets']['js'];

			foreach ($assets as $asset)
			{
				list($file, $priority) = (array) $asset + array(1 => 0);

				$document->js->add($file, $priority, $root);
			}
		}

		if (isset($view['assets']['css']))
		{
			$assets = (array) $view['assets']['css'];

			foreach ($assets as $asset)
			{
				list($file, $priority) = (array) $asset + array(1 => 0);

				$document->css->add($file, $priority, $root);
			}
		}

		#
		# provider
		#

		$bind = null;

		if (!empty($view['provider']))
		{
			list($constructor, $name) = explode('/', $id);

			$module = $core->modules[$constructor];
			$bind = $module->provide_view($name, $patron);

			if (!$bind)
			{
				if ($module && $name == 'list')
				{
					$placeholder = $core->site->metas["$module->flat_id.place_holder"];

					if ($placeholder)
					{
						return $placeholder;
					}

					$rc = '<p>' . t('empty_view', array(), array('scope' => array($module->flat_id, $name), 'default' => 'No record found.')) . '</p>';

					if (preg_match('#\.html$#', $page->template))
					{
						$class = 'view constructor-' . wd_normalize($constructor) . ' ' . $name;

						$rc = '<div id="view-' . wd_normalize($id) . '" class="' . $class . ' empty">' . $rc . '</div>';
					}

					return $rc;
				}

				return;
			}

			if ($module instanceof system_nodes_WdModule)
			{
				if ($name == 'view')
				{
					$page->node = $bind;
					$page->title = $bind->title;
				}
				else if ($bind instanceof system_nodes_WdActiveRecord)
				{
					WdEvent::fire('publisher.nodes_loaded', array('nodes' => array($bind)));
				}
				else if (is_array($bind))
				{
					$first = current($bind);

					if ($first instanceof system_nodes_WdActiveRecord)
					{
						WdEvent::fire('publisher.nodes_loaded', array('nodes' => $bind));
					}
				}

			}
		}

		#
		#
		#

		$rc = '';

		if (isset($view['file']))
		{
			$file = $core->site->resolve_path("templates/views/$id.php");

			if (!$file)
			{
				$file = $core->site->resolve_path("templates/views/$id.html");
			}

			if ($file)
			{
				$file = $_SERVER['DOCUMENT_ROOT'] .  $file;
			}
			else
			{
				$file = $view['file'];
			}

			$scope = isset($view['scope']) ? $view['scope'] : null;

			if ($scope)
			{
				WdI18n::push_scope($scope);
			}

			try
			{
				if (preg_match('#\.php$#', $file))
				{
					ob_start();

					wd_isolated_require($file, array('core' => $core, 'document' => $document, 'page' => $page, 'patron' => $patron));

					$rc = ob_get_clean();
				}
				else if (preg_match('#\.html$#', $file))
				{
					$rc = Patron(file_get_contents($file), $bind, array('file' => $file));
				}
				else
				{
					throw new WdException('Unable to process file %file, unsupported type', array('%file' => $file));
				}
			}
			catch (Exception $e)
			{
				if ($scope)
				{
					WdI18n::pop_scope($scope);
				}

				throw $e;
			}

			if ($scope)
			{
				WdI18n::pop_scope($scope);
			}
		}
		else if (isset($view['module']) && isset($view['block']))
		{
			$rc = $core->modules[$view['module']]->getBlock($view['block']);
		}
		else
		{
			throw new WdException('Unable to render view %view. The description of the view is invalid: !descriptor', array('%view' => $id, '!descriptor' => $view));
		}

		if (preg_match('#\.html$#', $page->template))
		{
			$class = 'view';

			if (strpos($id, '/'))
			{
				list($constructor, $type) = explode('/', $id, 2);

				$class .= ' constructor-' . wd_normalize($constructor) . ' ' . $type;
			}

			$rc = '<div id="view-' . wd_normalize($id) . '" class="' . $class . '">' . $rc . '</div>';
		}

		return $rc;
	}

	public function __construct($tags, $dummy=null)
	{
		parent::__construct
		(
			'div', $tags + array
			(
				'class' => 'view-editor'
			)
		);
	}

	public function getInnerHTML()
	{
		global $core;

		$document = $core->document;

		$document->css->add('../public/view.css');
		$document->js->add('../public/view.js');

		$rc = parent::getInnerHTML();

		$value = $this->get('value');
		$name = $this->get('name');

		#
		# FIXME-20100602: This is a compat fix 'contents.articles.list' => 'contents.articles/list'
		#

		if (strpos($value, '/') === false)
		{
			$pos = strpos($value, '.');

			if ($pos !== false)
			{
				$value[$pos] = '/';
			}
		}

		$selected_category = null;
		$selected_subcategory = null;

		$by_category = array();
		$descriptors = $core->modules->descriptors;

//		var_dump(self::$views);

		foreach (self::$views as $id => $view)
		{
			list($module_id, $type) = explode('/', $id) + array(1 => null);

			$category = 'Misc';
			$subcategory = 'Misc';

			if ($type !== null && isset($descriptors[$module_id]))
			{
				$descriptor = $descriptors[$module_id];

				if (isset($descriptor[WdModule::T_CATEGORY]))
				{
					$category = $descriptors[$module_id][WdModule::T_CATEGORY];
					$category = t($category, array(), array('scope' => array('module_category', 'title')));
				}

				$subcategory = $descriptor[WdModule::T_TITLE];
			}

			$by_category[$category][$subcategory][$id] = $view;

			if ($id == $value)
			{
				$selected_category = $category;
				$selected_subcategory = $subcategory;
			}
		}

		uksort($by_category, 'wd_unaccent_compare_ci');

		$rc = '<table>';
		$rc .= '<tr>';

		$rc .= '<td class="view-editor-categories"><ul>';

		foreach ($by_category as $category => $dummy)
		{
			$rc .= '<li' . ($category == $selected_category ? ' class="active selected"' : '') . '><a href="#select">' . wd_entities($category) . '</a></li>';
		}

		$rc .= '</ul></td>';

		#
		#
		#

		$rc .= '<td class="view-editor-subcategories">';

		foreach ($by_category as $category => $subcategories)
		{
			uksort($subcategories, 'wd_unaccent_compare_ci');

			$by_category[$category] = $subcategories;

			$rc .= '<ul' . ($category == $selected_category ? ' class="active selected"' : '') . '>';

			foreach ($subcategories as $subcategory => $views)
			{
				$rc .= '<li' . ($subcategory == $selected_subcategory ? ' class="active selected"' : '') . '><a href="#select">' . wd_entities($subcategory) . '</a></li>';
			}

			$rc .= '</ul>';
		}

		$rc .= '</ul></td>';

		#
		#
		#

		$context = $core->site->path;

		$rc .= '<td class="view-editor-views">';

		foreach ($by_category as $category => $subcategories)
		{
			foreach ($subcategories as $subcategory => $views)
			{
				$active = '';
				$items = array();

				foreach ($views as $id => $view)
				{
					if (empty($view['title']))
					{
						continue;
					}

					$title = $view['title'];
					$description = null;

					if (isset($view['description']))
					{
						$description = $view['description'];

						// FIXME-20101008: finish that ! it this usefull anyway ?

						$description = strtr
						(
							$description, array
							(
								'#{url}' => $context . '/admin/'
							)
						);
					}

					if ($id == $value)
					{
						$active = ' class="active"';
					}

					$items[$title] = new WdElement
					(
						WdElement::E_RADIO, array
						(
							WdElement::T_LABEL => $title,
							WdElement::T_DESCRIPTION => $description,

							'name' => $name,
							'value' => $id,
							'checked' => ($id == $value)
						)
					);
				}

				uksort($items, 'wd_unaccent_compare_ci');

				$rc .= "<ul$active><li>" . implode('</li><li>', $items) . '</li></ul>';
			}


		}

		$rc .= '</td>';

		$rc .= '</tr>';
		$rc .= '</table>';

		return $rc;
	}
}