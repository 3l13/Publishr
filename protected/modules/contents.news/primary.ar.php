<?php

class contents_news_WdActiveRecord extends contents_WdActiveRecord
{
	protected function __get_image()
	{
		$imageid = $this->imageid;

		if (!$imageid)
		{
			global $registry;

			$imageid = $registry->get('contentsNews.default_image');
		}

		return $imageid ? self::model('resources.images')->load($imageid) : null;
	}

	/*
	protected function __get_formatedDate()
	{
		return wd_ftime($this->date, WdLocale::$language == 'fr' ? '%d %b %Y' : '%b %d, %Y');
	}
	*/
}