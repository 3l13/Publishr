<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class editor_WdModule extends WdPModule
{
	const OPERATION_GET_EDITOR = 'getEditor';

	protected function getOperationsAccessControls()
	{
		return array
		(
			self::OPERATION_GET_EDITOR => array
			(
				self::CONTROL_AUTHENTICATED => true
			)
		)

		+ parent::getOperationsAccessControls();
	}

	protected function validate_operation_getEditor(WdOperation $operation)
	{
		//TODO: implement validation

		return true;
	}

	protected function operation_getEditor(WdOperation $operation)
	{
		global $document;

		$document = new WdDummyDocument();

		#
		#
		#

		$params = &$operation->params;

		$editor = new WdMultiEditorElement
		(
			$params['editor'], array
			(
				/*
				WdMultiEditorElement::T_BINDABLE => !empty($params['is_binded']),
				WdMultiEditorElement::T_BIND_TARGET => isset($params['bindtarget']) ? $params['bindtarget'] : null,
				*/

				'name' => $params['name'],
				'value' => $params['contents']
			)
		);

		$operation->terminus = true;

		return (array) $editor->export() + array
		(
			'editor' => (string) $editor,
			'css' => $document->getStyleSheets(),
			'javascript' => $document->getJavascripts()
		);
	}
}

class WdDummyDocument extends WdDocument
{
	public function getStyleSheets()
	{
		if (empty($this->stylesheets))
		{
			return;
		}

		return self::prisort($this->stylesheets);
	}

	public function getJavascripts()
	{
		if (empty($this->javascripts))
		{
			return;
		}

		return self::prisort($this->javascripts);
	}
}