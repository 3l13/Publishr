<?php

class view_WdEditorElement extends WdEditorElement
{
	static protected $config = array();

	static public function autoconfig()
	{
		$configs = func_get_args();

		array_unshift($configs, self::$config);

		self::$config = call_user_func_array('array_merge', $configs);
	}

	static public function render($contents)
	{
		global $core, $user, $publisher;

		$view = self::$config[$contents];

		if (isset($view['file']))
		{
			$file = $view['file'];

			if (substr($file, -4, 4) == '.php')
			{
				ob_start();

				require $file;

				$rc = ob_get_contents();

				ob_end_clean();

				return $rc;
			}
			else if (substr($file, -5, 5) == '.html')
			{
				return Patron(file_get_contents($view['file']));
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
			throw new WdException('Unable to render view %view. The description of the view is invalid', array('%view' => $contents));
		}
	}


	public function __construct($tags, $dummy=null)
	{
		parent::__construct($tags);

		global $document;

		$document->addStyleSheet('../public/view.css');
//		$document->addJavascript('../public/view.js');
	}

	public function __toString()
	{
		$value = $this->getTag('value');
		$name = $this->getTag('name');

		$rc  = '';

		$rc .= '<div class="radio-group list view-selector">';

		foreach (self::$config as $id => $view)
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

		$rc .= '</div>';

		//$rc .= '<div class="element-description">Sélectionner la vue à utiliser.</div>';

		return (string) $rc; ///* . '/' . $name . '/' . $value*/;
	}
}