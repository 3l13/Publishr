<?php

class WdThumbnailerConfigElement extends WdElement
{
	public function __construct($tags, $dummy=null)
	{
		parent::__construct
		(
			'div', $tags + array
			(
				self::T_CHILDREN => array
				(
					'w' => $this->el_w = new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdElement::T_LABEL => 'Dimensions',
							WdElement::T_LABEL_POSITION => 'left',

							'size' => 5
						)
					),

					' &times; ',

					'h' => $this->el_h = new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdElement::T_LABEL => 'px',
							WdElement::T_LABEL_POSITION => 'right',

							'size' => 5
						)
					),

					'<br /><br />',

					'method' => $this->el_method = new WdElement
					(
						'select', array
						(
							WdElement::T_LABEL => 'Méthode',
							WdElement::T_LABEL_POSITION => 'left',
							WdElement::T_OPTIONS => array
							(
								WdImage::RESIZE_FILL => 'Remplir',
								WdImage::RESIZE_FIT => 'Ajuster',
								WdImage::RESIZE_SURFACE => 'Surface',
								WdImage::RESIZE_FIXED_HEIGHT => 'Hauteur fixe',
								WdImage::RESIZE_FIXED_HEIGHT_CROPPED => 'Hauteur fixe, largeur ajustée',
								WdImage::RESIZE_FIXED_WIDTH => 'Largeur fixe',
								WdImage::RESIZE_FIXED_WIDTH_CROPPED => 'Largeur fixe, hauteur ajustée',
								WdImage::RESIZE_CONSTRAINED => 'Contrainte'
							)
						)
					),

					' &nbsp; ',

					'no-upscale' => $this->el_no_upscale = new WdElement
					(
						WdElement::E_CHECKBOX, array
						(
							WdElement::T_LABEL => 'Redimensionner, mais ne pas agrandir'
						)
					),

					'<br /><br />',

					'format' => $this->el_format = new WdElement
					(
						'select', array
						(
							self::T_LABEL => 'Format de la miniature',
							self::T_LABEL_POSITION => 'left',
							self::T_OPTIONS => array
							(
								'jpeg' => 'JPEG',
								'png' => 'PNG',
								'gif' => 'GIF'
							),

							self::T_DEFAULT => 'jpeg',

							'style' => 'display: inline-block'
						)
					),

					' &nbsp; ',

					'quality' => $this->el_quality = new WdElement
					(
						WdElement::E_TEXT, array
						(
							self::T_LABEL => 'Qualité',
							self::T_LABEL_POSITION => 'left',
							self::T_DEFAULT => 80,

							'size' => 3
						)
					),

					' &nbsp; ',

					'interlace' => $this->el_interlace = new WdElement
					(
						WdElement::E_CHECKBOX, array
						(
							self::T_LABEL => 'Affichage progressif'
						)
					)
				)
			)
		);

		if (isset($tags[self::T_DEFAULT]))
		{
			$this->setTag(self::T_DEFAULT, $tags[self::T_DEFAULT]);
		}
	}

	public function setTag($name, $value=null)
	{
		switch ($name)
		{
			case self::T_DEFAULT:
			{
				foreach ($value as $identifier => $default)
				{
					$el = 'el_' . str_replace('-', '_', $identifier);

					$this->$el->setTag(self::T_DEFAULT, $default);
				}

				return;
			}
			break;

			case 'name':
			{
				$this->el_w->setTag('name', $value . '[w]');
				$this->el_h->setTag('name', $value . '[h]');
				$this->el_method->setTag('name', $value . '[method]');
				$this->el_no_upscale->setTag('name', $value . '[no-upscale]');
				$this->el_format->setTag('name', $value . '[format]');
				$this->el_quality->setTag('name', $value . '[quality]');
				$this->el_interlace->setTag('name', $value . '[interlace]');

				return;
			}
			break;

			case 'value':
			{
				#
				# TODO: Maybe set the value if it's an array. We don't have to do anything
				# if the value is set from WdForm as it will dive to our children to set their
				# values.
				#

				return;
			}
			break;
		}

		parent::setTag($name, $value);
	}
}