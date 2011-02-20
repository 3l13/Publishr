<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class editor_WdModule extends WdPModule
{
	const OPERATION_GET_EDITOR = 'getEditor';

	protected function controls_for_operation_getEditor(WdOperation $operation)
	{
		return array
		(
			self::CONTROL_AUTHENTICATION => true
		);
	}

	protected function validate_operation_getEditor(WdOperation $operation)
	{
		//TODO: implement validation

		return true;
	}

	protected function operation_getEditor(WdOperation $operation)
	{
		global $core;

		$params = &$operation->params;

		$editor = (string) new WdMultiEditorElement
		(
			$params['editor'], array
			(
				WdMultiEditorElement::T_SELECTOR_NAME => $params['selectorName'],

				'name' => $params['contentsName'],
				'value' => $params['contents']
			)
		);

		$operation->response->assets = $core->document->get_assets();
		$operation->terminus = true;

		return $editor;
	}
}