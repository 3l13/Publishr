<?php

class site_cache_WdManager extends WdManager
{
	protected function columns()
	{
		return array
		(
			'uid' => array
			(

			),

			'created' => array
			(
				WdResume::COLUMN_CLASS => 'date',
				WdResume::COLUMN_SORT => WdResume::ORDER_DESC
			)
		);
	}
}