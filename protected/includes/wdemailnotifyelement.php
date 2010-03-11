<?php

class WdEMailNotifyElement extends WdFormSectionElement
{
	protected $elements;

	public function __construct($tags)
	{
		parent::__construct
		(
			'div', $tags + array
			(
				WdElement::T_CHILDREN => array
				(
					'subject' => $this->elements['subject'] = new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'Sujet du message',
							WdElement::T_MANDATORY => true
						)
					),

					'from' => $this->elements['from'] = new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'Adresse d\'expédition',
							//WdElement::T_VALIDATOR => array(array('WdForm', 'validate_email')),
							WdElement::T_MANDATORY => true
						)
					),

					'bcc' => $this->elements['bcc'] = new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'Copie cachée',
						)
					),

					'template' => $this->elements['template'] = new WdElement
					(
						'textarea', array
						(
							WdForm::T_LABEL => 'Patron du message',
							WdElement::T_MANDATORY => true,
							'rows' => 8
						)
					)
				)
			)
		);

		$group = $this->getTag(self::T_GROUP);

		if ($group)
		{
			$this->setTag(self::T_GROUP, $group);
		}
	}

	public function setTag($name, $value=null)
	{
		switch ($name)
		{
			case self::T_GROUP:
			{
				foreach ($this->elements as $el)
				{
					$el->setTag($name, $value);
				}
			}
			break;

			case self::T_DEFAULT:
			{
				foreach ($value as $identifier => $default)
				{
					$this->elements[$identifier]->setTag(self::T_DEFAULT, $default);
				}
			}
			break;

			case 'name':
			{
				foreach ($this->elements as $identifier => $el)
				{
					$el->setTag($name, $value . '[' . $identifier . ']');
				}

				return;
			}
			break;

			case 'value':
			{
				// FIXME-20091204: should handle value

				//wd_log(__CLASS__ . '# set value: \1', array($value));

				return;
			}
			break;
		}

		parent::setTag($name, $value);
	}
}