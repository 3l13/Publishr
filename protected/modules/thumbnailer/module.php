<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class thumbnailer_WdModule extends WdModule
{
	const VERSION = '1.1.5';

	const OPERATION_GET = 'get';
	const REGISTRY_NEXT_CLEANUP = 'thumbnailer.nextCleanup';

	static protected $defaults = array
	(
		'background' => 'transparent',
		'default' => null,
		'format' => 'jpeg',
		'h' => null,
		'interlace' => false,
		'method' => 'fill',
		'no-upscale' => false,
		'overlay' => null,
		'path' => null,
		'quality' => 85,
		'src' => null,
		'w' => null
	);

	static public $background;

	/**
	 * Returns the path to the thumbnails repository
	 */

	protected function __get_repository()
	{
		return WdCore::getConfig('repository.cache') . '/thumbnailer';
	}

	/**
	 * Returns the WdFileCache object used to manage the thumbnails cache.
	 */

	protected function __get_cache()
	{
		return new WdFileCache
		(
			array
			(
				WdFileCache::T_REPOSITORY => $this->repository,
				WdFileCache::T_REPOSITORY_SIZE => 8 * 1024
			)
		);
	}

	/**
	 * Cleanup the thumbnails cache.
	 *
	 * There is a delay of 15 minutes between each cleanup.
	 */

	protected function cleanup()
	{
		global $registry;

		#
		# periodic cleanup
		#

		$nextCleanup = $registry->get(self::REGISTRY_NEXT_CLEANUP);
		$nextCleanupTime = strtotime($nextCleanup);

		$now = time();

		if ($now <= $nextCleanupTime)
		{
			return;
		}
		else
		{
			$registry->set(self::REGISTRY_NEXT_CLEANUP, date('Y-m-d H:i:s', ($nextCleanupTime ? $nextCleanupTime : time()) + 15 * 60));

			//wd_log('next cleanup: ' . date('Y-m-d H:i:s', ($nextCleanupTime ? $nextCleanupTime : time()) + 15 * 60));
		}

		$this->cache->clean();
	}

	/**
	 * Creates the repository folder where generated thumbnails are saved.
	 *
	 * @see wd/wdcore/WdModule#install()
	 */

	public function install()
	{
		$repository = $this->repository;

		// TODO: use is_writable() to know if we can create the repository folder

		$rc = mkdir($_SERVER['DOCUMENT_ROOT'] . $repository, 0777, true);

		if (!$rc)
		{
			wd_log_error('Unable to create folder %path', array('%path' => $repository));
		}

		return $rc;
	}

	/**
	 * Check if the repository folder has been created
	 *
	 * @see support/wdcore/WdModule#isInstalled()
	 */

	public function isInstalled()
	{
		return is_dir($_SERVER['DOCUMENT_ROOT'] . $this->repository);
	}

	protected function getOperationsAccessControls()
	{
		return array
		(
			self::OPERATION_GET => array
			(
				self::CONTROL_VALIDATOR => false
			)
		);
	}

	public function get(array $options=array())
	{
		$options = $this->parseOptions($options);

		#
		# We check if the source file exists
		#

		$file = $options['src'];

		if (!$file)
		{
			throw new WdHTTPException('Missing thumbnail source.', array(), 404);
		}

		$file = $options['path'] . $file;

		$path = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . $file;

		if (!is_file($path))
		{
			#
			# use default file instead
			#

			if ($options['default'])
			{
				$file = $options['path'] . $options['default'];
				$path = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . $file;

				if (!is_file($path))
				{
					throw new WdHTTPException('Thumbnail source (default) not found: %src', array('%src' => $file), 404);
				}
			}
			else
			{
				throw new WdHTTPException('Thumbnail source not found: %src', array('%src' => $file), 404);
			}
		}

		#
		# We create a unique key for the thumbnail, using the image information
		# and the options used to create the thumbnail.
		#

		$key = filemtime($path) . '#' . filesize($path) . '#' . json_encode($options);
		$key = sha1($key) . '.' . $options['format'];

		#
		# Use the cache object to get the file
		#

		return $this->cache->get($key, array($this, 'get_construct'), array($path, $options));
	}

	public function get_construct($cache, $destination, $userdata)
	{
		list($path, $options) = $userdata;

		$callback = null;

		if ($options['background'] != 'transparent')
		{
			self::$background = self::decodeBackground($options['background']);

			$callback = array(__CLASS__, 'fill_callback');
		}

        $image = WdImage::load($path, $info);

		if (!$image)
		{
			throw new WdException('Unable to allocate image for file %path', array('%file' => $path), 500);
		}

		#
		# resize image
		#

		$w = $options['w'];
		$h = $options['h'];

		list($ow, $oh) = $info;

		$method = $options['method'];

		if ($options['no-upscale'] && (($method == WdImage::RESIZE_SURFACE && $w * $h > $ow * $oh) || ($w > $ow && $h > $oh)))
		{
			$w = $ow;
			$h = $oh;
		}

        $image = WdImage::resize($image, $w, $h, $method, $callback);

		if (!$image)
		{
			throw new WdException
			(
				'Unable to resize image for file %path with options: !options', array
				(
					'%path' => $path,
					'!options' => $options
				),

				500
			);
		}

		#
		# apply overlay
		#

		if ($options['overlay'])
		{
			// TODO: use WdImage::load() instead of imagecreatefrompng(), because
			// the overlay is not necessary a PNG file

			$overlay_file = $_SERVER['DOCUMENT_ROOT'] . $options['overlay'];

			list($o_w, $o_h) = getimagesize($overlay_file);

			$overlay_source = imagecreatefrompng($overlay_file);

			imagecopyresampled($image, $overlay_source, 0, 0, 0, 0, $w, $h, $o_w, $o_h);
		}

		#
		# interlace
		#

		if ($options['interlace'])
		{
			imageinterlace($image, true);
		}

        #
        # choose export format
        #

		$format = $options['format'];

		static $functions = array
		(
	        'gif' => 'imagegif',
	        'jpeg' => 'imagejpeg',
	        'png' => 'imagepng'
        );

        $function = $functions[$format];
        $args = array($image, $destination);

        if ($format == 'jpeg')
        {
        	#
        	# add quality option for the 'jpeg' format
        	#

        	$args[] = $options['quality'];
        }
        else if ($format == 'png' && !$callback)
        {
        	// Désactive l'Alpha blending et définit le drapeau Alpha

        	imagealphablending($image, false);
        	imagesavealpha($image, true);
        }

        $rc = call_user_func_array($function, $args);

        imagedestroy($image);

        if (!$rc)
        {
        	throw new WdException('Unable to save thumbnail', array(), 500);
        }

        return $destination;
	}

	/**
	 * Operation interface to the @get() method.
	 *
	 * The function uses the @get() method to obtain the location of the versioned image.
	 * A HTTP redirection is made to the location of the image.
	 *
	 * If the function fails to obtain the location of the image, a HTTP 404 error is
	 * issued.
	 *
	 * @param $params
	 */

	protected function operation_get(WdOperation $operation)
	{
		$this->cleanup();

		$operation->handle_booleans
		(
			array('interlace', 'no-upscale')
		);

		$params = &$operation->params;

		$location = $this->get($params);

		if ($location)
		{
			$stat = stat($_SERVER['DOCUMENT_ROOT'] . $location);

			$pos = strrpos($location, '.');
			$type = substr($location, $pos + 1);
			$etag = basename($location, '.' . $type);

			$expires = 60 * 60 * 24 * 7;

			session_cache_limiter('public');
			session_cache_expire($expires / 60);

			header('Date: ' . gmdate('D, d M Y H:i:s', $stat['ctime']) . ' GMT');
			header('X-Generated-By: WdThumbnailer/' . self::VERSION);
			header('Etag: ' . $etag);
			header('Cache-Control: public');

			if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && isset($_SERVER['HTTP_IF_NONE_MATCH']) &&
			(strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $stat['mtime'] || trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag))
			{
    			header('HTTP/1.1 304 Not Modified');

    			#
    			# WARNING: do *not* send any data after that
    			#
			}
			else
			{
				header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $stat['mtime']) . ' GMT');
			    header('Content-Type: image/' . $type);

			    $fh = fopen($_SERVER['DOCUMENT_ROOT'] . $location, 'rb');

				fpassthru($fh);
		    }
		}
		else
		{
			throw new WdHTTPException('Unable to create thumbnail for: %src', array('%src' => $operation->params['src']), 404);
		}

		$operation->terminus = true;

		return $location;
	}

	static public function operation_thumbnail($operation)
	{
		$params = &$operation->params;
		$params['src'] = null;

		$nid = (int) $params['nid'];

		if (function_exists('glob'))
		{
			$root = $_SERVER['DOCUMENT_ROOT'];
			$files = glob($root . WdCore::getConfig('repository.files') . '/*/' . $nid . '-*');

			if ($files)
			{
				$params['src'] = substr(array_shift($files), strlen($root));
			}
		}
		else
		{
			$path = WdCore::getConfig('repository.files') . '/image';
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

		$op = new WdOperation('thumbnailer', 'get', $params);

		$op->dispatch();

		exit;
	}

	protected function parseOptions($options)
	{
		#
		# handle the 'version' option
		#

		if (isset($options['version']))
		{
			$options += $this->getVersion($options['version']);

			unset($options['version']);
		}

		#
		# add defaults so that all options are defined
		#

		$options += self::$defaults;

		if (empty($options['background']))
		{
			$options['background'] = 'transparent';
		}

		if ($options['format'] == 'jpeg' && $options['background'] == 'transparent')
		{
			$options['background'] = 'white';
		}

		#
		# options are filtered out and sorted
		#

		$options = array_intersect_key($options, self::$defaults);

		ksort($options);

		#
		# check options
		#

		$method = $options['method'];
		$w = $options['w'];
		$h = $options['h'];

		switch ($method)
		{
			case WdImage::RESIZE_CONSTRAINED:
			case WdImage::RESIZE_FILL:
			case WdImage::RESIZE_FIT:
			case WdImage::RESIZE_SURFACE:
			{
				if (!$w || !$h)
				{
					throw new WdException
					(
						'Missing width or height for the %method method: %width × %height', array
						(
							'%method' => $method,
							'%width' => $w,
							'%height' => $h
						)
					);
				}
			}
			break;
		}

		return $options;
	}

	static private function decodeBackground($background)
	{
		$parts = explode(',', $background);

		$parts[0] = WdImage::decodeColor($parts[0]);

		if (count($parts) == 1)
		{
			return array($parts[0], null, 0);
		}

		$parts[1] = WdImage::decodeColor($parts[1]);

		return $parts;
	}

	static public function fill_callback($image, $w, $h)
	{
		#
		# We create WdImage::drawGrid() arguments from the dimensions of the image
		# and the values passed using the 'background' parameter.
		#

		$args = (array) self::$background;

		array_unshift($args, $image, 0, 0, $w - 1, $h - 1);

		call_user_func_array(array('WdImage', 'drawGrid'), $args);
	}

	protected function getVersion($name)
	{
		global $registry;

		$version = (array) $registry->get('thumbnailer.versions.' . $name . '.');

		if (!$version)
		{
			throw new WdException('Unknown version %version', array('%version' => $name), 404);
		}

		return $version;
	}

	protected function nid_to_path($nid)
	{
		$path = WdCore::getConfig('repository.files') . '/image';
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

			break;
		}

		closedir($dh);

		chdir($previous);

		if ($file)
		{
			return $path . '/' . $file;
		}
	}
}

/*
 *
 * parameters:
 * ***********
 *
 * src: path to the image, relative to the document root
 *
 * w: width of the thumbnail
 *
 * h: height of the thumbnail
 *
 * method: method to use to resize the image
 *
 * format: image format of the thumbnail [gif|jpeg|png]
 *
 * overlay: path to the overlay. Different overlays can be defined to be used depending on
 * the orientation of the image : path/to/horizontal/overlay,path/to/vertical/overlay.
 * They are separated by a coma
 *
 * path: source path for the image. path + src giving the complete path
 *
 * version: name of a version. A version define all the default options to be used while
 * creating the thumbnail. Version parameters are stored using the system.registry module e.g.
 * {this}.versions.nameOfTheVersion
 *
 */