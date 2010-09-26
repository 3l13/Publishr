<?php

class site_sites_WdHooks
{
	static private $model;

	static public function get_site($obj)
	{
		if (empty(self::$model))
		{
			global $core;

			self::$model = $core->models['site.sites'];
		}

		if ($obj instanceof system_nodes_WdActiveRecord)
		{
			return self::$model->load($obj->siteid);
		}

		return self::$model->findByRequest($_SERVER);
	}

	static public function get_working_site_id()
	{
		global $app;

		if (isset($_POST['change_working_site']))
		{
			$wsid = (int) $_POST['change_working_site'];

			$app->session->application['working_site'] = $wsid;

			header('Location: ' . $_SERVER['REQUEST_URI']);

			exit;
		}
		else if (isset($app->session->application['working_site']))
		{
			$wsid = $app->session->application['working_site'];
		}
		else
		{
			// FIXME: should search for the first site in the list

			$wsid = 1;
		}

		return $wsid;
	}

	static public function get_working_site()
	{
		global $app, $core;

		$wsid = $app->working_site_id;

		return $core->models['site.sites']->load($wsid);
	}
}