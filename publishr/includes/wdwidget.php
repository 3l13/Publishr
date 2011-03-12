<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

abstract class WdWidget extends WdElement
{
	/**
	 * Interpolates a css class from the widget class and add it to the class list.
	 *
	 * @param string $type
	 * @param array $tags
	 */
	public function __construct($type, $tags)
	{
		preg_match('#Wd(.+)(Element|Widget)#', get_class($this), $matches);

		$class = 'widget-' . wd_hyphenate($matches[1]);

		parent::__construct($type, $tags);

		$this->addClass($class);
	}

	static public function operation_get(WdOperation $operation)
	{
		global $core, $document;

		if (!$core->user_id)
		{
			throw new WdException('Unauthorized', array(), 401);
		}

		$class = wd_camelize('Wd-' . $operation->params['class'], '-') . 'Widget';

		if (!class_exists($class, true))
		{
			throw new WdException('Uknown widget class: %class', array('%class' => $class));
		}

		$document = $core->document;
		$params = &$operation->params;

		$rc = null;
		$mode = isset($params['mode']) ? $params['mode'] : null;
		$selected = isset($_GET['selected']) ? $_GET['selected'] : null;

		$el = new $class
		(
			array
			(
				'value' => $selected,
				WdAdjustNodeWidget::T_CONSTRUCTOR => isset($params['constructor']) ? $params['constructor'] : null
			)
		);

		if (!$mode)
		{
			$rc = (string) $el;
		}
		else if ($mode == 'popup')
		{
			$label_cancel = t('label.cancel');
			$label_use = t('label.use');
			$label_remove = t('label.remove');

			$rc = <<<EOT
<div class="popup">

$el

<div class="confirm">
<button type="button" class="cancel">$label_cancel</button>
<button type="button" class="none warn">$label_remove</button>
<button type="button" class="continue">$label_use</button>
</div>

<div class="arrow"><div>&nbsp;</div></div>

</div>
EOT;
		}
		else if ($mode == 'results')
		{
			$rc = $el->get_results($_GET);
		}
		else if ($mode)
		{
			throw new WdException('Uknown widget mode: %mode', array('%mode' => $mode));
		}

		$operation->response->assets = $document->get_assets();
		$operation->response->mode = $mode;

		return $rc;
	}

	protected function get_results(array $options=array())
	{
		throw new WdException('The widget class %class does not implement results', array('%class' => get_class($this)));
	}
}