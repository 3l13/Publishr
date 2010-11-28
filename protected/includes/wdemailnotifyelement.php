<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

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
							WdElement::T_REQUIRED => true
						)
					),

					'from' => $this->elements['from'] = new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'Adresse d\'expédition',
							//WdElement::T_VALIDATOR => array(array('WdForm', 'validate_email')),
							WdElement::T_REQUIRED => true
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
							WdElement::T_REQUIRED => true,
							'rows' => 8
						)
					)
				)
			)
		);

		$group = $this->get(self::T_GROUP);

		if ($group)
		{
			$this->set(self::T_GROUP, $group);
		}
	}

	public function set($name, $value=null)
	{
		switch ($name)
		{
			case self::T_GROUP:
			{
				foreach ($this->elements as $el)
				{
					$el->set($name, $value);
				}
			}
			break;

			case self::T_DEFAULT:
			{
				foreach ($value as $identifier => $default)
				{
					$this->elements[$identifier]->set(self::T_DEFAULT, $default);
				}
			}
			break;

			case 'name':
			{
				foreach ($this->elements as $identifier => $el)
				{
					$el->set($name, $value . '[' . $identifier . ']');
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

		parent::set($name, $value);
	}
}