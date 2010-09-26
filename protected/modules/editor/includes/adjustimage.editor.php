<?php

class adjustimage_WdEditorElement extends nodeadjust_WdEditorElement
{
	public function __construct($tags, $dummy=null)
	{
		$tags = wd_array_merge_recursive
		(
			$tags, array
			(
				self::T_CONFIG => array
				(
					'scope' => 'resources.images'
				)
			)
		);

		parent::__construct($tags, $dummy);
	}
}