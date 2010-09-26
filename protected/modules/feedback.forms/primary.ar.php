<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class feedback_forms_WdActiveRecord extends system_nodes_WdActiveRecord
{
	const MODEL_ID = 'modelid';
	const CONFIG = 'config';
	const SERIALIZED_CONFIG = 'serializedconfig';
	const BEFORE = 'before';
	const AFTER = 'after';
	const COMPLETE = 'complete';
	const PAGE_ID = 'pageid';

	protected function __get_config()
	{
		$config = array();

		if ($this->serializedconfig)
		{
			$config = unserialize($this->serializedconfig);
		}

		return $config;
	}

	protected function __get_model()
	{
		$models = WdConfig::get_constructed('formmodels', 'merge');

		if (empty($models[$this->modelid]))
		{
			throw new WdException('Unknown model id: %id', array('%id' => $this->modelid), 404);
		}

		return $models[$this->modelid];
	}

	protected function __get_url()
	{
		if (!$this->pageid)
		{
			return '#form-url-not-defined';
		}

		$page = $this->model('site.pages')->load($this->pageid);

		return $page->url;
	}

	protected function __get_form()
	{
		$class = $this->model['class'];

		return new $class
		(
			array
			(
				WdForm::T_VALUES => $_REQUEST,

				WdForm::T_HIDDENS => array
				(
					WdOperation::DESTINATION => 'feedback.forms',
					WdOperation::NAME => feedback_forms_WdModule::OPERATION_SEND,
					feedback_forms_WdModule::OPERATION_SEND_ID => $this->nid
				),

				WdElement::T_CHILDREN => array
				(
					'#submit' => new WdElement
					(
						WdElement::E_SUBMIT, array
						(
							WdElement::T_WEIGHT => 1000,
							WdElement::T_INNER_HTML => t('Send')
						)
					)
				),

				'id' => $this->slug
			)
		);
	}

	public function __toString()
	{
		global $app;

		#
		# if the form was sent successfully, we return the `complete` message instead of the form.
		#

		if (isset($app->session->modules['feedback.forms']['rc'][$this->nid]))
		{
			unset($app->session->modules['feedback.forms']['rc'][$this->nid]);

			return '<div id="' . $this->slug . '">' . $this->complete . '</div>';
		}

		try
		{
			return $this->before . $this->form . $this->after;
		}
		catch (Exception $e)
		{
			return (string) $e;
		}
	}
}