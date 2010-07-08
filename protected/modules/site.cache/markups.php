<?php

class site_cache_WdMarkups
{
	static public function cache(array $args, WdPatron $patron, $template)
	{
		$key = null;
		$scope = $args['scope'];

		switch ($scope)
		{
			case 'global':
			{
				$key = 'g-' . sha1(json_encode($template)) . '.html';
			}
			break;

			default:
			{
				throw new WdException('Unknown scope type: %scope', array('%scope' => $scope));
			}
			break;
		}

		$cache = new WdFileCache
		(
			array
			(
				WdFileCache::T_COMPRESS => false,
				WdFileCache::T_REPOSITORY => WdCore::getConfig('repository.cache') . '/publisher'
			)
		);

		return $cache->load($key, array(__CLASS__, 'cache_construct'), array($patron, $template));
	}

	static public function cache_construct($userdata)
	{
		list($patron, $template) = $userdata;

		return $patron->publish($template);
	}
}