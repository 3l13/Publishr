<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Creates a thumbnail of an image managed by the "resource.images" module.
 */
class thumbnailer__thumbnail_WdOperation extends thumbnailer__get_WdOperation
{
	protected function process()
	{
		global $core;

		$params = &$this->params;
		$params['src'] = null;

		// TODO-20101031: support for the 's' shorthand.

		$nid = (int) $params['nid'];

		if (function_exists('glob'))
		{
			$root = $_SERVER['DOCUMENT_ROOT'];
			$files = glob($root . $core->config['repository.files'] . '/*/' . $nid . '-*');

			if ($files)
			{
				$params['src'] = substr(array_shift($files), strlen($root));
			}
		}
		else
		{
			$path = $core->config['repository.files'] . '/image';
			$root = $_SERVER['DOCUMENT_ROOT'] . $path;

			$nid .= '-';
			$nid_length = strlen($nid);

			$previous = getcwd();
			chdir($root);

			$dh = opendir($root);

			while (($file = readdir($dh)) !== false)
			{
				if ($file[0] == '.' || substr($file, 0, $nid_length) != $nid)
				{
					continue;
				}

				$params['src'] = $path . '/' . $file;

				break;
			}

			closedir($dh);

			chdir($previous);
		}

		if (empty($params['src']))
		{
			throw new WdHTTPException('Unable to locate image resource for the given identifier: %nid.', array('%nid' => $nid), 404);
		}

		return parent::process();
	}
}