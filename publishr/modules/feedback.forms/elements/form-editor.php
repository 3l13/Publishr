<?php

class form_WdEditorElement extends WdEditorElement
{
	protected $selector;

	public function __construct($tags, $dummy=null)
	{
		parent::__construct
		(
			'div', array
			(
				WdElement::T_CHILDREN => array
				(
					$this->selector = new WdFormSelectorElement
					(
						'select', array
						(
							/*
							WdElement::T_LABEL => 'Formulaire',
							WdElement::T_LABEL_POSITION => 'before',
							*/
							WdElement::T_DESCRIPTION => 'Sélectionner le formulaire à afficher sur la page'
						)
					)
				),

//				'class' => 'combo'
			)

			+ $tags
		);
	}

	public function set($name, $value=null)
	{
		if (is_string($name))
		{
			if ($name == 'name')
			{
				$this->selector->set('name', $value);
			}
			else if ($name == 'value')
			{
				$this->selector->set('value', $value);
			}
		}

		return parent::set($name, $value);
	}

	static public function render($data)
	{
		global $core;

		if (!$data)
		{
			return;
		}

		$form = $core->models['feedback.forms'][$data];

		return (string) $form;
	}
}