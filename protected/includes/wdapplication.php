<?php

class WdURLManager
{
	protected $root;
	protected $url;

	public function __construct()
	{
		$this->url = substr($_SERVER['SCRIPT_NAME'], 0, -9);
		$this->root = realpath($_SERVER['DOCUMENT_ROOT'] . $this->url) . DIRECTORY_SEPARATOR;
	}

	public function createURL($route, array $params=array(), $ampersand='&')
	{
		$rc = $this->url . 'index.php/' . $route;

		return $rc;
	}
}

class WdApplication
{
	protected $urlManager;

	public function urlManager()
	{
		if (!$this->urlManager)
		{
			$this->urlManager = new WdURLManager();
		}

		return $this->urlManager;
	}
}

?>