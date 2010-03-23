<?php

class resources_images_WdModule extends resources_files_WdModule
{
	const ICON_WIDTH = 24;
	const ICON_HEIGHT = 24;
	const THUMBNAIL_WIDTH = 200;
	const THUMBNAIL_HEIGHT = 200;

	protected $accept = array
	(
		'image/gif', 'image/png', 'image/jpeg'
	);

	protected $uploader_class = 'WdImageUploadElement';

	public function install()
	{
		global $registry;

		#
		# we use 'resources.images' instead of 'this' to avoid problems with inheritence
		#

		$registry->set
		(
			'thumbnailer.versions.$icon', array
			(
				'w' => self::ICON_WIDTH,
				'h' => self::ICON_HEIGHT,
				'format' => 'png'
			)
		);

		$registry->set
		(
			'thumbnailer.versions.$popup', array
			(
				'w' => self::THUMBNAIL_WIDTH,
				'h' => self::THUMBNAIL_HEIGHT,
				'method' => WdImage::RESIZE_SURFACE,
				'no-upscale' => true,
				'quality' => 90
			)
		);

		return parent::install();
	}

	protected function operation_config(WdOperation $operation)
	{
		$params = &$operation->params;

		#
		# handle booleans
		#

		foreach ($params['thumbnailer']['versions'] as $version => &$options)
		{
			$options += array
			(
				'no-upscale' => false,
				'interlace' => false
			);

			$options['no-upscale'] = filter_var($options['no-upscale'], FILTER_VALIDATE_BOOLEAN);
			$options['interlace'] = filter_var($options['interlace'], FILTER_VALIDATE_BOOLEAN);
		}

		return parent::operation_config($operation);
	}

	protected function block_manage()
	{
		return new resources_images_WdManager
		(
			$this, array
			(
				WdManager::T_COLUMNS_ORDER => array
				(
					'title', 'surface', 'uid', 'modified', 'is_online'
				)
			)
		);
	}

	protected function block_gallery()
	{
		return new resources_images_WdManagerGallery
		(
			$this, array
			(
				WdManager::T_COLUMNS_ORDER => array(File::TITLE, 'surface', File::SIZE, File::MODIFIED),
				WdManager::T_ORDER_BY => File::TITLE
			)
		);
	}

	protected function block_adjust($params)
	{
		return new WdAdjustImageElement
		(
			array
			(
				WdElement::T_DESCRIPTION => null,

				'value' => isset($params['value']) ? $params['value'] : null
			)
		);
	}

	protected function obs_block_adjustResults(array $options=array())
	{
		global $core, $registry;

		$options += array
		(
			'page' => 0,
			'limit' => 10,
			'search' => null,
			'selected' => null
		);

		$page = $options['page'];
		$limit = $options['limit'];
		$search = $options['search'];
		$selected = $options['selected'];

		$model = $this->model();

		$w = $registry->get('thumbnailer.versions.$icon.w');
		$h = $registry->get('thumbnailer.versions.$icon.h');

		$where = array
		(
			'is_online = 1',
			'constructor = "resources.images"'
		);

		$params = array();

		if ($search)
		{
			$words = explode(' ', $search);
			$words = array_map('trim', $words);

			foreach ($words as $word)
			{
				$where[] = 'title LIKE ?';
				$params[] = '%' . $word . '%';
			}
		}

		$where = ' WHERE ' . implode(' AND ', $where);

		$count = $model->count(null, null, $where, $params);

		$rc = '<div class="results">';

		if ($count)
		{
			$entries = $model->loadRange
			(
				$page * $limit, $limit, $where . ' ORDER BY title', $params
			);

			$rc .= '<ul class="results">';

			foreach ($entries as $entry)
			{
				$rc .= ($entry->nid == $selected) ? '<li class="selected">' : '<li>';

				$img = new WdElement
				(
					'img', array
					(
						'src' => WdOperation::encode
						(
							'thumbnailer', 'get', array
							(
								'src' => $entry->path,
								'version' => '$icon'
							)
						),

						'width' => $w,
						'height' => $h,

						'alt' => ''
					)
				);

				$title = wd_shorten($entry->title, 32, $shortened);

				$rc .= ' ' . new WdElement
				(
					'a', array
					(
						WdElement::T_INNER_HTML => $img . ' ' . $title,

						'href' => $entry->path . '#' . $entry->nid,
						'title' => $shortened ? wd_entities($entry->title) : null
					)
				);

				$rc .= '</li>';
			}

			$rc .= '</ul>';

			$rc .= new resources_images_adjustimage_WdPager
			(
				'div', array
				(
					WdPager::T_COUNT => $count,
					WdPager::T_LIMIT => $limit,
					WdPager::T_POSITION => $page,

					'class' => 'pager'
				)
			);
		}
		else
		{
			$rc .= '<p>' . t('Aucun résultat pour %search', array('%search' => $search)) . '</p>';
		}

		$rc .= '</div>';

		return $rc;
	}

	public function adjust_createEntry($entry)
	{
		global $registry;

		$w = $registry->get('thumbnailer.versions.$icon.w');
		$h = $registry->get('thumbnailer.versions.$icon.h');

		$img = new WdElement
		(
			'img', array
			(
				'src' => WdOperation::encode
				(
					'thumbnailer', 'get', array
					(
						'src' => $entry->path,
						'version' => '$icon'
					)
				),

				'width' => $w,
				'height' => $h,

				'alt' => ''
			)
		);

		$rc = $img . ' ' . parent::adjust_createEntry($entry);

		$rc .= '<input type="hidden" class="path" value="' . wd_entities($entry->path) . '" />';

		return $rc;
	}
}

class resources_images_adjustimage_WdPager extends WdPager
{
	protected function getURL($n)
	{
		return '#' . $n;
	}
}