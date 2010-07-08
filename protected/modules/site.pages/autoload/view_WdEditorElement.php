<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class view_WdEditorElement extends WdEditorElement
{
	static protected $views = array();

	static public function __static_construct()
	{
		self::$views = WdCore::getConstructedConfig('views', array(__CLASS__, '__static_construct_callback'));
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

				$definition['root'] = $root;

				if (empty($definition['file']) && empty($definition['block']))
				{
					list($name, $type) = explode('/', $id) + array(1 => null);

					$definition['file'] = ($type ? $type : $name) . '.html';
				}

				if (isset($definition['block']) && empty($definition['module']))
				{
					$definition['module'] = $module_id;
				}

				if (isset($definition['file']) && $definition['file'][0] != '/')
				{
					$definition['file'] = $root . '/views/' . $definition['file'];
				}

				$views[$id] = $definition;
			}
		}

		return $views;
	}

	public function __construct($tags, $dummy=null)
	{
		parent::__construct
		(
			'div', $tags + array
			(
				'class' => 1 ? 'radio-group list view-selector' : 'view-selector'
			)
		);
	}

	static public function toContents($contents, $page_id=null)
	{
		global $registry;

		$contents = parent::toContents($contents);

//		wd_log('contents: \1', array($contents));

		if ($contents)
		{
			#
			# FIXME-20100602: This is a compat fix 'contents.articles.list' => 'contents.articles/list'
			#

			if (strpos($contents, '/') === false)
			{
				$pos = strpos($contents, '.');

				if ($pos !== false)
				{
					$contents[$pos] = '/';
				}
			}

			/*
			$pos = strrpos($contents, '.');

			$module = substr($contents, 0, $pos);
			$url_type = substr($contents, $pos + 1);

			$key = wd_camelCase($module, '.') . '.url.' . $url_type;

			$registry->set($key, $page_id);
			*/
		}

		return $contents;
	}

	static public function render($id)
	{
		global $core, $app, $document, $publisher;

		#
		# FIXME-20100602: This is a compat fix 'contents.articles.list' => 'contents.articles/list'
		#

		if (strpos($id, '/') === false)
		{
			$pos = strpos($id, '.');

			if ($pos !== false)
			{
				$id[$pos] = '/';
			}
		}

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
					'The requested URL %uri requires authentification.', array
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
		#
		#

		if (isset($view['file']))
		{
			$file = $view['file'];

			if (substr($file, -4, 4) == '.php')
			{
				ob_start();

				require $file;

				return ob_get_clean();
			}
			else if (substr($file, -5, 5) == '.html')
			{
				return Patron(file_get_contents($file), null, array('file' => $file));
			}
			else
			{
				throw new WdException('Unable to process file %file, unsupported type', array('%file' => $file));
			}
		}
		else if (isset($view['module']) && isset($view['block']))
		{
			return $core->getModule($view['module'])->getBlock($view['block']);
		}
		else
		{
			throw new WdException('Unable to render view %view. The description of the view is invalid: !descriptor', array('%view' => $id, '!descriptor' => $view));
		}
	}

	public function getInnerHTML()
	{
		global $document;

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

		if (0)
		{
			// TODO-20100531: use module's category whenever available

			$tree = array();

			foreach (self::$views as $id => $view)
			{
				list($module_start, $module_finish, $view_type) = explode('.', $id);

				$view['id'] = $id;

				$tree[$module_start][$module_start . '.' . $module_finish][$view_type] = $view;
			}

			$rc .= '<ul class="categories">';

			foreach ($tree as $category_id => $category)
			{
				$rc .= '<li>';
				$rc .= ucfirst($category_id);
				$rc .= '<ul class="modules">';

				foreach ($category as $module_id => $types)
				{
					$rc .= '<li>';
					$rc .= ucfirst($module_id);
					$rc .= '<ul class="types">';

					foreach ($types as $type_id => $view)
					{
						$id = $view['id'];
						$description = null;

						if (isset($view['description']))
						{
							$description = $view['description'];
							$description = strtr
							(
								$description, array
								(
									'#{url}' => WdRoute::encode('')
								)
							);
						}

						$rc .= '<li>';
						$rc .= new WdElement
						(
							WdElement::E_RADIO, array
							(
								WdElement::T_LABEL => $view['title'],
								WdElement::T_DESCRIPTION => $description,

								'name' => $name,
								'value' => $id,
								'checked' => ($id == $value)
							)
						);
						$rc .= '</li>';
					}

					$rc .= '</ul>';
					$rc .= '</li>';
				}

				$rc .= '</ul>';


				$rc .= '</li>';
			}

			$rc .= '</ul>';
		}
		else if (0)
		{
			$by_category = array();

			foreach (self::$config as $id => $view)
			{
				$category = substr($id, 0, strpos($id, '.'));

				$by_category[$category][$id] = $view;
			}

			ksort($by_category);

			$rc .= '<ul>';

			foreach ($by_category as $category => $group)
			{
				$rc .= '<li>';
				$rc .= '<strong>' . ucfirst($category) . '</strong>';
				$rc .= '<div>';

				foreach ($group as $id => $view)
				{
					$description = null;

					if (isset($view['description']))
					{
						$description = $view['description'];
						$description = strtr
						(
							$description, array
							(
								'#{url}' => WdRoute::encode('')
							)
						);
					}

					$rc .= '<div class="view-item">';

					$rc .= new WdElement
					(
						WdElement::E_RADIO, array
						(
							WdElement::T_LABEL => $view['title'],
							WdElement::T_DESCRIPTION => $description,

							'name' => $name,
							'value' => $id,
							'checked' => ($id == $value)
						)
					);

					$rc .= '</div>';
				}

				$rc .= '</li>';
			}

			$rc .= '</ul>';
		}
		else
		{
			$items = array();

			foreach (self::$views as $id => $view)
			{
				$title = $view['title'];
				$description = null;

				if (isset($view['description']))
				{
					$description = $view['description'];
					$description = strtr
					(
						$description, array
						(
							'#{url}' => WdRoute::encode('')
						)
					);
				}

				$items[$title] = '<div class="view-item">'

				. new WdElement
				(
					WdElement::E_RADIO, array
					(
						WdElement::T_LABEL => $title,
						WdElement::T_DESCRIPTION => $description,

						'name' => $name,
						'value' => $id,
						'checked' => ($id == $value)
					)
				)

				. '</div>';
			}

			uksort($items, 'wd_unaccent_compare_ci');

			$rc .= '<div>' . implode('', $items) . '</div>';
		}

		return $rc;
	}
}