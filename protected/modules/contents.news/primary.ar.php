<?php

class contents_news_WdActiveRecord extends contents_WdActiveRecord
{
	protected function __get_image()
	{
		return $this->imageid ? self::model('resources.images')->load($this->imageid) : null;
	}

	/*
	protected function __get_formatedDate()
	{
		return wd_ftime($this->date, WdLocale::$language == 'fr' ? '%d %b %Y' : '%b %d, %Y');
	}
	*/
}