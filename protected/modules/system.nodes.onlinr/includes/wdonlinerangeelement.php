<?php

class WdOnlineRangeElement extends WdElement
{
	protected $publicize_el;
	protected $privatize_el;

	public function __construct($type='div', $tags=array())
	{
		$publicize_name = 'publicize';
		$privatize_name = 'privatize';

		$tags += array
		(
			WdForm::T_LABEL => 'Visibilité',
			WdElement::T_DESCRIPTION => 'Les dates de <em>publication</em> et de <em>dépublication</em> permettent de définir une intervale
			pendant laquelle l\'entrée est visible. Si la date de publication est définie, l\'entrée sera visible à partir
			de la date définie. Si la date de dépublication est définie, l\'entrée ne sera plus visible à partir de la date
			définie.',

			WdElement::T_CHILDREN => array
			(
				$publicize_name => $this->publicize_el = new WdDateElement
				(
					array
					(
						WdElement::T_LABEL => 'Publication',
						WdElement::T_LABEL_POSITION => 'left'
					)
				),

				' &nbsp; ',

				$privatize_name => $this->privatize_el = new WdDateElement
				(
					array
					(
						WdElement::T_LABEL => 'Dépublication',
						WdElement::T_LABEL_POSITION => 'left'
					)
				)
			)
		);

		parent::__construct($type, $tags);
	}

	public function setTag($name, $value=null)
	{
		if ($name == 'name')
		{
			$this->publicize_el->setTag('name', $value . '[publicize]');
			$this->privatize_el->setTag('name', $value . '[privatize]');

			return;
		}
	}
}