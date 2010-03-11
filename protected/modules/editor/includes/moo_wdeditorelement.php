<?php

class moo_WdEditorElement extends WdEditorElement
{
	public function __construct($tags, $dummy=null)
	{
		parent::__construct($tags);

		$this->addClass('moo');

		global $document;

		$document->addStyleSheet('public/support/mooeditable/assets/MooEditable.css');
		$document->addStyleSheet('public/support/mooeditable/assets/MooEditable.Image.css');
		$document->addStyleSheet('public/support/mooeditable/assets/MooEditable.Extras.css');
		$document->addStyleSheet('public/support/mooeditable/assets/MooEditable.SilkTheme.css');

		$document->addJavascript('public/support/mooeditable/source/MooEditable.js');
		$document->addJavascript('public/support/mooeditable/source/MooEditable.Image.js');
		$document->addJavascript('public/support/mooeditable/source/MooEditable.UI.MenuList.js');
		$document->addJavascript('public/support/mooeditable/source/MooEditable.Extras.js');
		$document->addJavascript('public/support/mooeditable/auto.js');

		new WdAdjustImageElement();
	}

	public function export()
	{
		$id = $this->getTag('id');

		//wd_log('id: \1', array($id));

		#
		# TODO: remove the DRY with /public/support/mooeditable/auto.js
		#

		return array
		(
			'initialize' => <<<EOT
$('$id').mooEditable
(
	{
		actions: 'bold italic underline strikethrough | formatBlock justifyleft justifyright justifycenter justifyfull | insertunorderedlist insertorderedlist indent outdent | undo redo | createlink unlink | image | removeformat toggleview',
		externalCSS:
		[
			'/\$wd/wdpublisher/public/css/reset.css',
			'/\$wd/wdpublisher/public/support/mooeditable/body.css',
			'/public/styles.css'
		]
	}
);
EOT
		);
	}

	public function __toString()
	{
		$rc = parent::__toString();

		$rc .= new WdElement
		(
			WdElement::E_HIDDEN, array
			(
				'value' => json_encode
				(
					array
					(
						'actions' => 'bold italic underline strikethrough | formatBlock justifyleft justifyright justifycenter justifyfull | insertunorderedlist insertorderedlist indent outdent | undo redo | createlink unlink | image | removeformat toggleview',
						'externalCSS' => array_merge
						(
							array
							(
								WdDocument::getURLFromPath('public/css/reset.css')
							),

							$this->getTag(self::T_STYLESHEETS, array()),

							array
							(
								WdDocument::getURLFromPath('public/support/mooeditable/body.css')
							)
						)
					)
				),

				'class' => 'wd-editor-config'
			)
		);

		return $rc;
	}
}