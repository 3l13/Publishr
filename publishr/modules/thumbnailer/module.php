<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class thumbnailer_WdModule extends WdModule
{
	const VERSION = '1.2.0';
	const OPERATION_GET = 'get';

	/**
	 * Configuration for the module.
	 *
	 * - cleanup_interval: The interval between cleanups, in minutes.
	 *
	 * - repository_size: The size of the repository, in Mo.
	 */

	static public $config = array
	(
		'cleanup_interval' => 15,
		'repository_size' => 8
	);

	static protected $defaults = array
	(
		'background' => 'transparent',
		'default' => null,
		'format' => 'jpeg',
		'height' => null,
		'interlace' => false,
		'method' => 'fill',
		'no-upscale' => false,
		'overlay' => null,
		'path' => null,
		'quality' => 85,
		'src' => null,
		'width' => null
	);

	static protected $shorthands = array
	(
		'b' => 'background',
		'd' => 'default',
		'f' => 'format',
		'h' => 'height',
		'i' => 'interlace',
		'm' => 'method',
		'nu' => 'no-upscale',
		'o' => 'overlay',
		'p' => 'path',
		'q' => 'quality',
		's' => 'src',
		'v' => 'version',
		'w' => 'width'
	);

	static public $background;

	/**
	 * Returns the path to the thumbnails repository
	 */

	protected function __get_repository()
	{
		return WdCore::$config['repository.cache'] . '/thumbnailer';
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
				WdFileCache::T_REPOSITORY_SIZE => self::$config['repository_size'] * 1024
			)
		);
	}

	/**
	 * Periodically cleans up the thumbnails cache.
	 */

	protected function cleanup()
	{
		$marker = $_SERVER['DOCUMENT_ROOT'] . $this->repository . '/.cleanup';

		$time = file_exists($marker) ? filemtime($marker) : 0;
		$interval = self::$config['cleanup_interval'] * 60;
		$now = time();

		if ($time + $interval > $now)
		{
			return;
		}

		$this->cache->clean();

		touch($marker);
	}

	/**
	 * Creates the repository folder where generated thumbnails are saved.
	 *
	 * @see WdModule::install()
	 */

	public function install()
	{
		$repository = $this->repository;

		// TODO: use is_writable() to know if we can create the repository folder
		// FIXME: 0777 ? really ?

		$rc = mkdir($_SERVER['DOCUMENT_ROOT'] . $repository, 0777, true);

		if (!$rc)
		{
			wd_log_error('Unable to create folder %path', array('%path' => $repository));
		}

		return $rc;
	}

	/**
	 * Check if the repository folder has been created.
	 *
	 * @see WdModule::isInstalled()
	 */

	public function isInstalled()
	{
		return is_dir($_SERVER['DOCUMENT_ROOT'] . $this->repository);
	}

	/**
	 * Returns the location of the thumbnail on the server, relative to the document root.
	 *
	 * The thumbnail is created using the parameters supplied, if it is not already available in
	 * the cache.
	 *
	 * @param array $params
	 * @throws WdHTTPException
	 */

	public function get(array $params=array())
	{
		$params = $this->parse_params($params);

		#
		# We check if the source file exists
		#

		$src = $params['src'];
		$path = $params['path'];

		if (!$src)
		{
			throw new WdHTTPException('Missing thumbnail source.', array(), 404);
		}

		$src = $path . $src;
		$location = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . $src;

		if (!is_file($location))
		{
			$default = $params['default'];

			#
			# use the provided default file is defined
			#

			if (!$default)
			{
				throw new WdHTTPException('Thumbnail source not found: %src', array('%src' => $src), 404);
			}

			$src = $path . $default;
			$location = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . $src;

			if (!is_file($location))
			{
				throw new WdHTTPException('Thumbnail source (default) not found: %src', array('%src' => $src), 404);
			}
		}

		#
		# We create a unique key for the thumbnail, using the image information
		# and the options used to create the thumbnail.
		#

		$key = filemtime($location) . '#' . filesize($location) . '#' . json_encode($params);
		$key = sha1($key) . '.' . $params['format'];

		#
		# Use the cache object to get the file
		#

		return $this->cache->get($key, array($this, 'get_construct'), array($location, $params));
	}

	/**
	 * Constructor for the cache entry.
	 *
	 * @param WdFileCache $cache The cache object.
	 * @param string $destination The file to create.
	 * @param array $userdata An array with the path of the original image and the options to use
	 * to create the thumbnail.
	 * @throws WdException
	 */

	public function get_construct(WdFileCache $cache, $destination, $userdata)
	{
		list($path, $options) = $userdata;

		$callback = null;

		if ($options['background'] != 'transparent')
		{
			self::$background = self::decode_background($options['background']);

			$callback = array(__CLASS__, 'fill_callback');
		}

        $image = WdImage::load($path, $info);

		if (!$image)
		{
			throw new WdException('Unable to load image from file %path', array('%path' => $path));
		}

		#
		# resize image
		#

		$w = $options['width'];
		$h = $options['height'];

		list($ow, $oh) = $info;

		$method = $options['method'];

		if ($options['no-upscale'])
		{
			if ($method == WdImage::RESIZE_SURFACE)
			{
				if ($w * $h > $ow * $oh)
				{
					$w = $ow;
					$h = $oh;
				}
			}
			else
			{
				if ($w > $ow)
				{
					$w = $ow;
				}

				if ($h > $oh)
				{
					$h = $ow;
				}
			}
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
				)
			);
		}

		#
		# apply the overlay
		#

		if ($options['overlay'])
		{
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
        	#
        	# If there is no background callback defined, the image is defined as transparent in
        	# order to obtain a transparent thumbnail when the resulting image is centered.
        	#

        	imagealphablending($image, false);
        	imagesavealpha($image, true);
        }

        $rc = call_user_func_array($function, $args);

        imagedestroy($image);

        if (!$rc)
        {
        	throw new WdException('Unable to save thumbnail');
        }

        return $destination;
	}

	/**
	 * Returns the controls for the `get` operation.
	 *
	 * @param WdOperation $operation
	 * @return array Controls for the operation.
	 */

	protected function controls_for_operation_get(WdOperation $operation)
	{
		return array
		(
			self::CONTROL_VALIDATOR => false
		);
	}

	/**
	 * Operation interface to the @get() method.
	 *
	 * The function uses the @get() method to obtain the location of the image version.
	 * A HTTP redirection is made to the location of the image.
	 *
	 * A WdHTTPException exception with code 404 is thrown if the function fails to obtain the
	 * location of the image version.
	 *
	 * @param WdOperation $operation
	 * @throws WdHTTPException
	 */

	protected function operation_get(WdOperation $operation)
	{
		$this->cleanup();

		self::rescue_uri($operation);

		$location = $this->get($operation->params);

		if (!$location)
		{
			throw new WdHTTPException('Unable to create thumbnail for: %src', array('%src' => $operation->params['src']), 404);
		}

		$server_location = $_SERVER['DOCUMENT_ROOT'] . $location;

		$stat = stat($server_location);
		$etag = md5($location);

		#
		# The expiration date is set to seven days.
		#

		session_cache_limiter('public');
		session_cache_expire(60 * 24 * 7);

		header('Date: ' . gmdate('D, d M Y H:i:s', $stat['ctime']) . ' GMT');
		header('X-Generated-By: WdThumbnailer/' . self::VERSION);
		header('Etag: ' . $etag);
		header('Cache-Control: public');

		if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && isset($_SERVER['HTTP_IF_NONE_MATCH'])
		&& (strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $stat['mtime'] || trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag))
		{
			header('HTTP/1.1 304 Not Modified');

			#
			# WARNING: do *not* send any data after that
			#
		}
		else
		{
			$pos = strrpos($location, '.');
			$type = substr($location, $pos + 1);

			header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $stat['mtime']) . ' GMT');
		    header('Content-Type: image/' . $type);

		    $fh = fopen($server_location, 'rb');

			fpassthru($fh);
	    }

		$operation->terminus = true;

		return $location;
	}

	/**
	 * Create a thumbnail of an image managed by the "resource.images" module.
	 *
	 * @param WdOperation $operation
	 * @throws WdHTTPException
	 */

	static public function operation_thumbnail(WdOperation $operation)
	{
		$params = &$operation->params;
		$params['src'] = null;

		// TODO-20101031: support for the 's' shorthand.

		$nid = (int) $params['nid'];

		if (function_exists('glob'))
		{
			$root = $_SERVER['DOCUMENT_ROOT'];
			$files = glob($root . WdCore::$config['repository.files'] . '/*/' . $nid . '-*');

			if ($files)
			{
				$params['src'] = substr(array_shift($files), strlen($root));
			}
		}
		else
		{
			$path = WdCore::$config['repository.files'] . '/image';
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

	/**
	 * Parse, filter and sort options.
	 *
	 * @param unknown_type $options
	 * @throws WdException
	 */

	protected function parse_params($params)
	{
		global $core;

		#
		# handle the 'version' parameter
		#

		if (isset($params['v']))
		{
			$params['version'] = $params['v'];
		}

		if (isset($params['version']))
		{
			$version = $params['version'];
			$version_params = (array) $core->registry['thumbnailer.versions.' . $version . '.'];

			if (!$version_params)
			{
				throw new WdException('Unknown version %version', array('%version' => $version), 404);
			}

			$params += $version_params;

			unset($params['version']);
		}

		#
		# transform shorthands
		#

		foreach (self::$shorthands as $shorthand => $full)
		{
			if (isset($params[$shorthand]))
			{
				$params[$full] = $params[$shorthand];
			}
		}

		#
		# add defaults so that all options are defined
		#

		$params += self::$defaults;

		if (empty($params['background']))
		{
			$params['background'] = 'transparent';
		}

		if ($params['format'] == 'jpeg' && $params['background'] == 'transparent')
		{
			$params['background'] = 'white';
		}

		#
		# The parameters are filtered and sorted, making extraneous parameters and parameters order
		# non important.
		#

		$params = array_intersect_key($params, self::$defaults);

		ksort($params);

		#
		# check options
		#

		$m = $params['method'];
		$w = $params['width'];
		$h = $params['height'];

		switch ($m)
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
							'%method' => $m,
							'%width' => $w,
							'%height' => $h
						)
					);
				}
			}
			break;
		}

		return $params;
	}

	static private function decode_background($background)
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

	/**
	 * Under some strange circumstances, IE6 uses URL with encoded entities. This function tries
	 * to rescue the bullied URIs.
	 *
	 * The decoded parameters are set in the operation's params property.
	 *
	 * @param WdOperation $operation
	 */

	static private function rescue_uri(WdOperation $operation)
	{
		$query = $_SERVER['QUERY_STRING'];

		if (strpos($query, '&amp;') === false)
		{
			return;
		}

		$query = html_entity_decode($query);

		$rc = parse_str($query, $operation->params);
	}
}