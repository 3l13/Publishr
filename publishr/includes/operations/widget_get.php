<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class widget_get_WdOperation extends WdOperation
{
	private $widget_class;

	protected function __get_controls()
	{
		return array
		(
			self::CONTROL_AUTHENTICATION => true
		)

		+ parent::__get_controls();
	}

	protected function validate()
	{
		$this->widget_class = $class = wd_camelize('Wd-' . $this->params['class'], '-') . 'Widget';

		if (!class_exists($class, true))
		{
			throw new WdException('Unknown widget class: %class', array('%class' => $class));
		}

		return true;
	}

	protected function process()
	{
		global $core, $document;

		if (!$core->user_id)
		{
			throw new WdException('Unauthorized', array(), 401);
		}

		$params = &$this->params;

		$document = $core->document;

		$rc = null;
		$mode = isset($params['mode']) ? $params['mode'] : null;
		$selected = isset($_GET['selected']) ? $_GET['selected'] : null;
		$class = $this->widget_class;

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

		$this->response->assets = $document->get_assets();
		$this->response->mode = $mode;

		return $rc;
	}
}