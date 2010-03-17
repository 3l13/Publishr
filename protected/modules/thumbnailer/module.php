<?php

class thumbnailer_WdModule extends WdModule
{
	const _VERSION = '1.1.2';

	const OPERATION_GET = 'get';
	const REGISTRY_NEXT_CLEANUP = 'thumbnailer.nextCleanup';

	static protected $defaults = array
	(
		'background' => 'white',
		'default' => null,
		'format' => 'jpeg',
		'h' => null,
		'interlace' => false,
		'method' => 'fill',
		'no-upscale' => false,
		'overlay' => null,
		'path' => null,
		'quality' => 70,
		'src' => null,
		'w' => null
	);

	static public $background;

	static public $config = array();

	static public function autoconfig()
	{
		$configs = func_get_args();

		array_unshift($configs, self::$config);

		self::$config = call_user_func_array('array_merge', $configs);
	}

	static protected $repository;

	static protected function repository()
	{
		if (!self::$repository)
		{
			self::$repository = WdCore::getConfig('repository.cache') . '/thumbnailer';
		}

		return self::$repository;
	}

	/**
	 * Override the run() method to clean the repository using the
	 * WdFileCache::cleanRepository method.
	 * @see wd/wdcore/WdModule#run()
	 */

	public function run()
	{
		#
		# now would be a good time to clear the repository
		#

		$this->cache = new WdFileCache
		(
			array
			(
				WdFileCache::T_REPOSITORY => self::repository(),
				WdFileCache::T_REPOSITORY_SIZE => 8 * 1024
			)
		);
	}

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
		$repository = self::repository();

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
		$repository = self::repository();

		return is_dir($_SERVER['DOCUMENT_ROOT'] . $repository);
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

		$file = $options['path'] . $options['src'];
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
					return false;
				}
			}
			else
			{
				return false;
			}
		}

		#
		# We create a unique key for the thumbnail, using the image information
		# and the options used to create the thumbnail.
		#

		$key = filemtime($path) . '#' . filesize($path) . '#' . json_encode($options);
		$key = sha1($key);

		#
		# Use the cache object to get the file
		#

		$format = $options['format'];

		return $this->cache->get($key . '.' . $format, array($this, 'get_construct'), array($path, $options));
	}

	public function get_construct($cache, $destination, $userdata)
	{
		list($path, $options) = $userdata;

		self::$background = self::decodeBackground($options['background']);

		$callback = array(__CLASS__, 'fill_callback');

        $image = WdImage::load($path, $info);

		if (!$image)
		{
			throw new WdException('Unable to allocate image for file %file', array('%file' => $file), 500);

			return false;
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
			throw new WdException('Unable to resize image for file %file with options: !options', array('%file' => $file, '!options' => $options), 500);
		}

		#
		# apply overlay
		#

		if ($options['overlay'])
		{
			// FIXME: use WdImage::load() instead of imagecreatefrompng(), because
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

        $rc = call_user_func_array($function, $args);

        imagedestroy($image);

        if (!$rc)
        {
        	return false;
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

		$location = $this->get($operation->params);

		if ($location)
		{
			$stat = stat($_SERVER['DOCUMENT_ROOT'] . $location);

			$pos = strrpos($location, '.');
			$type = substr($location, $pos + 1);
			$etag = basename($location, '.' . $type);

			header('Date: ' . gmdate('D, d M Y H:i:s', $stat['ctime']) . ' GMT');
			header('X-Generated-By: WdThumbnailer/' . self::_VERSION);
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
			    header('Expires: ' . gmdate('D, d M Y H:i:s', strtotime('+1 month')) . ' GMT');
			    header('Etag: ' . $etag);
			    header('Content-Lenght: ' . $stat['size'], true);
			    header('Content-Type: image/' . $type);

			    $fh = fopen($_SERVER['DOCUMENT_ROOT'] . $location, 'rb');

			    fpassthru($fh);
		    }
		}
		else
		{
			header('HTTP/1.1 404 Thumbnail source not found');
		}

		$operation->terminus = true;

		return $location;
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

		#
		# options are filtered out and sorted
		#

		$options = array_intersect_key($options, self::$defaults);

		ksort($options);

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