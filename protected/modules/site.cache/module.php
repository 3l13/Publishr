<?php

class site_cache_WdModule extends WdPModule
{
	const SIZE = 'size';
	const CLEAR_CONFIRM = 'clear-confirm';
	const OPERATION_CLEAR = 'clear';

	protected function __get_cache()
	{
		return new WdFileCache
		(
			array
			(
				WdFileCache::T_COMPRESS => false,
				WdFileCache::T_REPOSITORY => WdCore::getConfig('repository.cache') . '/publisher'
			)
		);
	}

	public function clear()
	{
		$this->cache->clear();

		wd_log_done('The publisher cache has been cleared');
	}

	public function get(WdEvent $event)
	{
		$constructor = $event->constructor;
		$data = $event->constructor_data;

		if ($_POST)
		{
			return call_user_func($constructor, $data);
		}

		global $app;

		$key = sha1($event->uri) . '-' . (int) $app->user_id;

		wd_log('from cache baby, key: \1', array($key));

		$event->rc = $this->cache->load($key, $constructor, $data);
	}

	protected function validate_operation_clear(WdOperation $operation)
	{
		return !empty($operation->params[self::CLEAR_CONFIRM]);
	}

	protected function operation_clear(WdOperation $operation)
	{
		$this->clear();

		return true;
	}
}