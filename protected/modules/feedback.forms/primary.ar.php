<?php

class feedback_forms_WdActiveRecord extends system_nodes_WdActiveRecord
{
	public static $formModels = array
	(
		1 => 'contact',
		2 => 'contact-press',

		10 => 'newsletter',
		11 => 'newsletter-quick',

		50 => 'contact-quick',
		51 => 'contact-atalian',

		100 => 'book',

		200 => 'poll-exhibitors',
		201 => 'poll-participants',

		300 => 'presspack-download'
	);

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
		global $core, $user;

		if (empty(self::$formModels[$this->modelid]))
		{
			throw new WdException('Unknown model Id %modelid', array('%modelid' => $this->modelid));
		}

		return (object) require 'models' . DIRECTORY_SEPARATOR . self::$formModels[$this->modelid] . '.php';
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
		$id = $this->slug;
		$tags = $this->model->tags;
		$name = isset($tags['id']) ? $tags['id'] : $id;

		$tags = wd_array_merge_recursive
		(
			array
			(
				WdForm::T_VALUES => $_POST,

				WdForm::T_HIDDENS => array
				(
					WdOperation::DESTINATION => 'feedback.forms',
					WdOperation::NAME => feedback_forms_WdModule::OPERATION_SEND,
					feedback_forms_WdModule::OPERATION_SEND_ID => $this->nid
				),

				WdElement::T_CHILDREN => array
				(
					new WdElement
					(
						WdElement::E_SUBMIT, array
						(
							WdElement::T_WEIGHT => 1000,
							WdElement::T_INNER_HTML => 'Envoyer'
						)
					)
				),

				'name' => $id
			),

			$tags
		);

		$class = 'Wd2CForm';

		if (isset($this->model->class))
		{
			$class = $this->model->class;
		}

		return new $class($tags);
	}

	public function __toString()
	{
		return $this->before . $this->form . $this->after;
	}
}