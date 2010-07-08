<?php

class WdThumbnailerConfigElement extends WdElement
{
	private $elements = array();

	public function __construct($tags, $dummy=null)
	{
		parent::__construct
		(
			'div', $tags + array
			(
				self::T_CHILDREN => array
				(
					'w' => $this->elements['w'] = new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdElement::T_LABEL => 'Dimensions',
							WdElement::T_LABEL_POSITION => 'left',

							'size' => 5
						)
					),

					' × ',

					'h' => $this->elements['h'] = new WdElement
					(
						WdElement::E_TEXT, array
						(
							'size' => 5
						)
					),

					'&nbsp;px<br /><br />',

					'method' => $this->elements['method'] = new WdElement
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

					'no-upscale' => $this->elements['no-upscale'] = new WdElement
					(
						WdElement::E_CHECKBOX, array
						(
							WdElement::T_LABEL => 'Redimensionner, mais ne pas agrandir'
						)
					),

					'<br /><br />',

					'format' => $this->elements['format'] = new WdElement
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

					'quality' => $this->elements['quality'] = new WdElement
					(
						WdElement::E_TEXT, array
						(
							self::T_LABEL => 'Qualité',
							self::T_LABEL_POSITION => 'left',
							self::T_DEFAULT => 80,

							'size' => 3
						)
					),

					'interlace' => $this->elements['interlace'] = new WdElement
					(
						WdElement::E_CHECKBOX, array
						(
							self::T_LABEL => 'Affichage progressif'
						)
					),

					'<br /><br />',

					'background' => $this->elements['background'] = new WdElement
					(
						WdElement::E_TEXT, array
						(
							self::T_LABEL => 'Remplissage',
							self::T_LABEL_POSITION => 'left'
						)
					)
				),

				'class' => 'wd-thumbnailer-config'
			)
		);

		global $document;

		$document->css->add('wdthumbnailerconfig.css');
		$document->js->add('wdthumbnailerconfig.js');
	}

	public function set($name, $value=null)
	{
		if (is_string($name))
		{
			switch ($name)
			{
				case self::T_DEFAULT:
				{
					foreach ($this->elements as $identifier => $element)
					{
						if (!array_key_exists($identifier, $value))
						{
							continue;
						}

						$element->set($name, $value[$identifier]);
					}
				}
				break;

				case 'name':
				{
					foreach ($this->elements as $identifier => $element)
					{
						$element->set($name, $value . '[' . $identifier . ']');
					}
				}
				break;
			}
		}

		parent::set($name, $value);
	}
}