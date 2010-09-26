<?php

class site_sites_WdModel extends WdModel
{
	public function findByRequest($request)
	{
		//var_dump($request);

		$request_uri = $request['REQUEST_URI'];

		$sites = $this->loadAll('ORDER BY path DESC')->fetchAll();

		foreach ($sites as $site)
		{
			$score = 0;

			if ($site->path && preg_match('#^' . $site->path . '/?#', $request_uri))
			{
				$score += 10;

				return $site;
			}

			//var_dump($site, $score);
		}
	}
}