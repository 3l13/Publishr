<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class WdResume extends WdElement
{
	const T_BLOCK = '#manager-block';
	const T_COLUMNS = '#manager-columns';
	const T_COLUMNS_ORDER = '#manager-columns-order';
	const T_KEY = '#manager-key';
	const T_JOBS = '#manager-jobs';
	const T_ORDER_BY = '#manager-order-by';

	#
	# session options constants
	#

	const OPTIONS = 'resume-options';

	#
	# display constants
	#

	const BY = 'by';
	const ORDER = 'order'; // TODO-20100128: remove this and use T_ORDER_BY instead
	const LIMIT = 'limit';
	const START = 'start';
	const WHERE = 'where';
	const IS = 'is';
	const SEARCH = 'search';
	const SEARCH_CLEAR = 'search-clear';

	#
	# column constants
	#

	const COLUMN_LABEL = 'label';
	const COLUMN_HOOK = 'hook';
	const COLUMN_CLASS = 'class';
	const COLUMN_SORT = 'sort';

	#
	# sort constants
	#

	const ORDER_ASC = 'asc';
	const ORDER_DESC = 'desc';

	#
	# variables
	#

	public $module;
	public $model;

	protected $columns;
	protected $entries;
	protected $tags;
	protected $count;

	protected $idtag;
	protected $jobs = array();

	#
	# checkboxes count is used to determine wheter or not we should
	# add the 'mastercheckbox'
	#

	protected $checkboxes = 0;

	public function __construct($module, $model, array $tags)
	{
		global $app;

		parent::__construct(null, $tags);

		if (!($module instanceof WdModule))
		{
			throw new WdException('Module must be an instance of WdModule: \1', array($module));
		}

		$this->module = $module;

		if (!($model instanceof WdModel))
		{
			throw new WdException('Model must be an instance of WdModel: \1', array($model));
		}

		$this->model = $model;

		if (empty($tags[self::T_COLUMNS]))
		{
			throw new WdException('The %tag tag is mandatory', array('%tag' => 'T_COLUMNS'));
		}

		#
		#
		#

		foreach ($tags as $tag => $value)
		{
			switch ($tag)
			{
				case self::T_COLUMNS:
				{
					foreach ($value as $identifier => &$column)
					{
						if (!$identifier)
						{
							continue;
						}

						$column += array
						(
							self::COLUMN_LABEL => /*DIRTY '@manager.th.' . */$identifier
						);
					}

					$this->columns = $value;
				}
				break;

				case self::T_KEY:
				{
					$this->idtag = $value;

					#
					# now that entries have a primary key, we can add the 'delete' job
					#

					$this->addJob(WdModule::OPERATION_DELETE, 'Supprimer');
				}
				break;

				case self::T_JOBS:
				{
					foreach ($value as $operation => $label)
					{
						$this->addJob($operation, $label);
					}
				}
				break;
			}
		}

		$name = (string) $this->module;

		$request = $this->parseOptions($name);

		// FIXME-20100203: this is quite dangerous !!!
		// 20100322: which part ??

		$this->tags = $request + $this->tags;

		#
		# load entries
		#

		$query = null;

		$where = array();
		$params = array();

		$display_search = $request[self::SEARCH];
		$display_where = $request[self::WHERE];
		$display_is = $request[self::IS];

		$schema = $this->model->getExtendedSchema();

		if ($display_search)
		{
			global $core;

			$words = explode(' ', $display_search);
			$words = array_map('trim', $words);

			$queries = array();

			foreach ($words as $word)
			{
				$concats = array();

				// FIXME-20081223: special cases form dates 2008, 2008-12, 2008-12-23

				foreach ($schema['fields'] as $identifier => $definition)
				{
					$type = $definition['type'];

					if (($type != 'varchar') && ($type != 'text'))
					{
						continue;
					}

					$concats[] = '`' . $identifier . '`';
				}

				$where[] = 'CONCAT(' . implode(', ', $concats) . ') LIKE ?';
				$params[] = '%' . $word . '%';
			}
		}

		if ($display_where && $display_is !== '')
		{
			$type = $schema['fields'][$display_where]['type'];

			if ($type == 'timestamp' || $type == 'date' || $type == 'datetime')
			{
				list($year, $month, $day) = explode('-', $display_is) + array(0, 0, 0);

				if ($year)
				{
					$where[] = "YEAR(`$display_where`) = ?";
					$params[] = (int) $year;
				}

				if ($month)
				{
					$where[] = "MONTH(`$display_where`) = ?";
					$params[] = (int) $month;
				}

				if ($day)
				{
					$where[] = "DAY(`$display_where`) = ?";
					$params[] = (int) $day;
				}
			}
			else
			{
				$where[] = '`' . $display_where . '` = ?';
				$params[] = $display_is;
			}
		}

		#
		# site
		#

		if (isset($schema['fields']['siteid']))
		{
			$where['siteid'] = '(siteid = 0 OR siteid = ' . (int) $app->working_site_id . ')';
		}

		#
		#
		#

		// TODO: move this to their respective manager

		if ($module instanceof system_nodes_WdModule || $module instanceof user_users_WdModule)
		{
			#
			# we load only the entries that where created by the module
			#

			$where[] = 'constructor = ?';
			$params[] = (string) $module;
		}

		#
		# count
		#

		$this->count = $this->count($where, $params);

		#
		# load range
		#

		$order = null;

		$display_by = $request[self::BY];

		if ($display_by)
		{
			$order = ' ORDER BY `' . $display_by . '` ' . ($request[self::ORDER] == 'desc' ? 'DESC' : 'ASC');
		}

		$display_start = $request[self::START];
		$display_limit = $request[self::LIMIT];

		$this->entries = $this->loadRange($display_start - 1, $display_limit, $where, $order, $params);
	}

	protected function count(array $where, array $params)
	{
		$query = $where ? ' WHERE ' . implode(' AND ', $where) : '';

		return $this->model->count(null, null, $query, $params);
	}

	protected function loadRange($offset, $limit, array $where, $order, array $params)
	{
		$query = $where ? ' WHERE ' . implode(' AND ', $where) : '';
		$query .= ' ' . $order;

		return $this->model->loadRange
		(
			$offset, $limit, $query, $params
		)
		->fetchAll();
	}

	protected function parseOptions($name)
	{
		global $app;

		$request = $_GET;

		// FIXME: use search-clear because 'search' is sent too when 'start' is changed, reseting 'start' to 1 if 'search' is empty

		if (isset($request[self::WHERE]) || isset($request[self::BY]) ||
			isset($request[self::ORDER]) || isset($request[self::LIMIT]) || isset($request[self::SEARCH]))
		{
			$request[self::START] = 1;
		}

		if (isset($app->session->wdmanager['options'][$name]))
		{
			$request += $app->session->wdmanager['options'][$name];
		}

		# defaults

		$request += array
		(
			self::BY => null,
			self::ORDER => self::ORDER_ASC,
			self::START => 1,
			self::LIMIT => 10,
			self::SEARCH => null,
			self::WHERE => null,
			self::IS => null
		);

		#
		# check display values
		#

		$request[self::START] = max($request[self::START], 1);



		$schema = $this->model->getExtendedSchema();

		/*
		if (isset($request[self::BY]) && empty($schema['fields'][$request[self::BY]]))
		{
			WdDebug::trigger('Unknown column %column used by display-by', array('%column' => $request[self::BY]));

			$request[self::BY] = null;
		}
		*/

		if (empty($request[self::BY]))
		{
			$order = $this->get(self::T_ORDER_BY);

			if ($order)
			{
				$order = ((array) $order) + array(1 => 'asc');

				$request[self::BY] = $order[0];
				$request[self::ORDER] = $order[1];
			}
			else
			{
				foreach ($this->columns as $by => $col)
				{
					if (empty($col[self::COLUMN_SORT]))
					{
						continue;
					}

					$request[self::BY] = $by;
					$request[self::ORDER] = $col[self::COLUMN_SORT];

					break;
				}
			}
		}

		$app->session->wdmanager['options'][$name] = $request;

		return $request;
	}

	public function addJob($job, $label)
	{
		$this->jobs[$job] = $label;
	}

	protected function getURL(array $modifier=array(), $fragment=null)
	{
		$url = '?' . http_build_query($modifier);

		if ($fragment)
		{
			$url .= '#' . $fragment;
		}

		$url = strtr($url, array('+' => '%20'));

		return wd_entities($url);
	}

	protected function getHeader()
	{
		if (empty($this->columns))
		{
			WdDebug::trigger('no columns here: \1', array($this));

			return;
		}

		$display_by = $this->tags[self::BY];
		$display_order = $this->tags[self::ORDER];
		$display_where = $this->tags[self::WHERE];

		$rc  = '<thead>';
		$rc .= '<tr>';

		$n = 0;

		if ($this->idtag)
		{
			$rc .= '<th class="first key">&nbsp;</th>';

			$n++;
		}

		foreach ($this->columns as $by => $col)
		{
			$n++;

			$class = isset($col[self::COLUMN_CLASS]) ? $col[self::COLUMN_CLASS] : NULL;

			if ($n == 1)
			{
				$class = "first $class";
			}

			//
			// start markup
			//

			if ($class)
			{
				$rc .= '<th class="' . $class . '">';
			}
			else
			{
				$rc .= '<th>';
			}

			//
			// contents
			//

			$label = isset($col[self::COLUMN_LABEL]) ? $col[self::COLUMN_LABEL] : null;

			if ($label)
			{
				$label = t($label, array(), array('scope' => 'manager.th'));

				//
				// the column is not sortable
				//
				/*
				if (isset($col[self::COLUMN_SORT]) && ($col[self::COLUMN_SORT] == RESUME_SORT_NONE))
				{
					$rc .= $label;
				}


				//
				// display entries are restricted from this column
				//

				else*/ if (($by) && ($display_where == $by))
				{
					$rc .= '<a title="' . t('Display everything') .'" href="';
					$rc .= $this->getURL
					(
						array
						(
							self::WHERE => '',
							self::IS => ''
						)
					);
					$rc .= '" class="filter">';

					$rc .= $label;

					$rc .= '</a>';
				}
				else
				{
					if (($by == $display_by)
					 && ($display_order == self::ORDER_ASC))
					{
						$order = self::ORDER_DESC;
					}
					else
					{
						$order = self::ORDER_ASC;
					}

					if ($by)
					{
						$rc .= '<a title="' . t('Sort by: :identifier', array(':identifier' => $label)) . '" href="';

						$rc .= $this->getURL
						(
							array
							(
								self::BY => $by,
								self::ORDER => $order
							)
						);
						$rc .= '"' ;

						if ($by == $display_by)
						{
							if ($display_order == self::ORDER_ASC)
							{
								$rc .= 'class="asc" ';
							}
							else
							{
								$rc .= 'class="desc" ';
							}
						}

						$rc .= '>';

						$rc .= $label;

						$rc .= '</a>';
					}
					else
					{
						$rc .= $label;
					}
				}
			}
			else
			{
				$rc .= '&nbsp;';
			}

			//
			// end markup
			//

			$rc .= '</th>';
		}

		#
		# operations
		#

		$rc .= '<th class="operations">&nbsp;</th>';

		#
		# end header row
		#

		$rc .= '</tr>';
		$rc .= '</thead>';

		return $rc;
	}

	protected function get_cell($entry, $tag, $opt)
	{
		$rc = '<td';

		if (isset($opt[self::COLUMN_CLASS]))
		{
			$rc .= ' class="' . $opt[self::COLUMN_CLASS] . '"';
		}

		$rc .= '>';

		$rc .= call_user_func($opt[self::COLUMN_HOOK], $entry, $tag, $this);

		$rc .= '</td>';

		return $rc;
	}

	protected function get_cell_key($entry, $value)
	{
		global $app;

		$disabled = true;

		if ($app->user->has_ownership($this->module, $entry))
		{
			$disabled = false;

			$this->checkboxes++;
		}

		return new WdElement
		(
			'label', array
			(
				WdElement::T_CHILDREN => array
				(
					new WdElement
					(
						WdElement::E_CHECKBOX, array
						(
							'value' => $value,
							'checked' => $disabled
						)
					)
				),

				'title' => t('Toggle selection for entry #\1', array($value)),
				'class' => 'checkbox-wrapper rectangle'
			)
		);
	}

	protected function getContents()
	{
		global $app;

		$user = $app->user;
		$module = $this->module;
		$idtag = $this->idtag;
		$count = count($this->entries);

		$rc = '';

		foreach ($this->entries as $i => $entry)
		{
			$ownership = $idtag ? $user->has_ownership($module, $entry) : null;
			$class = 'entry';

			if ($ownership === false)
			{
				$class .= ' no-ownership';
			}

			$rc .= '<tr class="' . $class . '">';

			#
			# if the id tag was provided, we had a column for the ids
			#

			if ($idtag)
			{
				$rc .= '<td class="key">';
				$rc .= $this->get_cell_key($entry, $entry->$idtag);
				$rc .= '</td>' . PHP_EOL;
			}

			#
			# create user defined columns
			#

			foreach ($this->columns as $tag => $opt)
			{
				$rc .= $this->get_cell($entry, $tag, $opt) . PHP_EOL;
			}

			#
			# operations
			#

			$rc .= '<td class="operations">';
			$rc .= '<a href="#operations">&nbsp;</a>';
			$rc .= '</td>';

			$rc .= '</tr>';
		}

		return $rc;
	}

	protected function getEmptyContents()
	{
		//
		// begin row
		//

		$rc = '<tr>';

		//
		// message
		//

		$rc .= '<td colspan="' . (count($this->columns) + 1) . '" class="create-new">';

		$search = isset($this->tags[self::SEARCH]) ? $this->tags[self::SEARCH] : NULL;
		$select = isset($this->tags[self::IS]) ? $this->tags[self::IS] : NULL;

		if ($search)
		{
			$rc .= t('Your search - <strong>!search</strong> - did not match any entry.', array('!search' => $search));
		}
		else if ($select)
		{
			$rc .= t('Your selection - <strong>!selection</strong> - dit not match any entry.', array('!selection' => $select));
		}
		else
		{
			$rc .= t('@manager.emptyCreateNew', array('!url' => WdRoute::encode('/' . $this->module . '/create')));
		}

		$rc .= '</td>' . "\n";

		//
		// end row
		//

		$rc .= '</tr>';

		return $rc;
	}

	protected function getSearch()
	{
		$search = $this->get(self::SEARCH);

		return new WdForm
		(
			array
			(
				WdElement::T_CHILDREN => array
				(
					self::SEARCH => new WdElement
					(
						WdElement::E_TEXT, array
						(
							'title' => t('Search in the entries'),
							'value' => $search,
							'size' => '16',
							'class' => 'search' . ($search ? '' : ' empty'),
							'tabindex' => 0
						)
					),

					new WdElement
					(
						'button', array
						(
							WdElement::T_INNER_HTML => '✖',
							'type' => 'button'
						)
					)
				),

				'class' => 'search' . ($search ? ' active' : ''),
				'method' => 'get'
			)
		);
	}

	protected function addSearch()
	{
		global $document;

		$options  = '<div class="manage">';
		$options .=	$this->getSearch();
		$options .= $this->browse;
		$options .= '</div>';

		$document->addToBlock($options, 'menu-options');
	}

	protected function getLimiter()
	{
		$count = $this->count;
		$start = $this->tags[self::START];
		$limit = $this->tags[self::LIMIT];

		$rc = '<div class="limiter">';

		$ranger = new WdRanger
		(
			'span', array
			(
				WdRanger::T_START => $start,
				WdRanger::T_LIMIT => $limit,
				WdRanger::T_COUNT => $count,
				WdRanger::T_EDITABLE => true,
				WdRanger::T_NO_ARROWS => true
			)
		);

		$rc .= $ranger;

		if (($limit >= 20) || ($count >= $limit))
		{
			#
			# select max items per page
			#

			$rc .= ' &nbsp; ';

			$rc .= new WdElement
			(
				'select', array
				(
					WdElement::T_OPTIONS => array(10 => 10, 20 => 20, 50 => 50, 100 => 100),

					'title' => t('Number of item to display by page'),
					'name' => self::LIMIT,
					'onchange' => 'this.form.submit()',

					'value' => $limit
				)
			);

			$rc .= ' par page';
		}

		#
		# browse
		#

		$browse = null;

		if ($count > $limit)
		{
			$url = '?start=';

			// ◄► ◀▶ ◁▷ ➜ ▷▸▹▻ ❮❯❰❱

			$browse  = '<span class="browse">';
			$browse .= '<a href="' . $url . ($start - $limit < 1 ? $count - $limit + 1 + ($count % $limit ? $limit - ($count % $limit) : 0) : $start - $limit) . '" class="browse previous">◀</a>';
			$browse .= '<a href="' . $url . ($start + $limit >= $count ? 1 : $start + $limit) . '" class="browse next">▶</a>';
			$browse .= '</span>';
		}

		$rc .= $browse;

		$rc .= '</div>';

		$this->browse = $browse;

		return $rc;
	}

	protected function getJobs()
	{
		if (!$this->jobs)
		{
			return;
		}

		$options = array(null => 'Pour la sélection…');

		foreach ($this->jobs as $operation => $label)
		{
			$options[$operation] = $label;
		}

		return new WdElement
		(
			'div', array
			(
				WdElement::T_CHILDREN => array
				(
					'jobs' => new WdElement
					(
						'select', array
						(
							WdElement::T_OPTIONS => $options
						)
					)
				),

				'class' => 'jobs'
			)
		);
	}

	protected function getFooter()
	{
		$display_search = null;

		extract($this->tags, EXTR_PREFIX_ALL, 'display');

		//
		// begin row
		//

		$rc  = '<tfoot>';
		$rc .= '<tr>';

		if ($this->idtag)
		{
			$rc .= '<td class="key">';
			/*$rc .= $this->checkboxes ? '<input type="checkbox" />' : '&nbsp;';*/

			if ($this->checkboxes)
			{
				$rc .= new WdElement
				(
					'label', array
					(
						WdElement::T_CHILDREN => array
						(
							new WdElement
							(
								WdElement::E_CHECKBOX, array
								(
									'title' => t('Toggle selection for the entries ([alt] to toggle selection)')
								)
							)
						),

						'class' => 'checkbox-wrapper rectangle'
					)
				);
			}
			else
			{
				$rc .= '&nbsp;';
			}

			$rc .= '</td>';
		}

		$ncolumns = count($this->columns);

		#
		# operations
		#

		$rc .= '<td colspan="2">';
		$rc .= $this->entries ? $this->getJobs() : '&nbsp;';
		$rc .= '</td>';

		$rc .= '<td colspan="' . ($ncolumns - 1) . '">';
		$rc .= $this->count ? $this->getLimiter() : '&nbsp;';
		$rc .= '</td>';

		$rc .= '</tr>';
		$rc .= '</tfoot>';
		$rc .= PHP_EOL;

		return $rc;
	}

	public function __toString()
	{
		global $document;

		$document->js->add('resume.js', -170);
		$document->css->add('public/css/manage.css', -170);

		$this->browse = null;

		$rc  = PHP_EOL;
		$rc .= '<form id="manager" method="get" action="">' . PHP_EOL;

		$rc .= new WdElement
		(
			WdElement::E_HIDDEN, array
			(
				'name' => WdOperation::DESTINATION,
				'value' => (string) $this->module
			)
		);

		$rc .= new WdElement
		(
			WdElement::E_HIDDEN, array
			(
				'name' => self::T_BLOCK,
				'value' => $this->get(self::T_BLOCK, 'manage')
			)
		);

		$body = '<tbody>';

		if (empty($this->entries))
		{
			$body .= $this->getEmptyContents();
		}
		else
		{
			$body .= $this->getContents();
		}

		$body .= '</tbody>';

		$head = $this->getHeader();
		$foot = $this->getFooter();

		$rc .= '<table class="group manage" cellpadding="4" cellspacing="0">';

		$rc .= $head . $foot . $body;

		$rc .= '</table>' . PHP_EOL;
		$rc .= '</form>' . PHP_EOL;

		$this->addSearch();

		return $rc;
	}

	/*
	**

	CALLBACKS

	**
	*/

	const MODIFY_MAX_LENGTH = 48;

	static public function modify_callback($entry, $tag, $resume)
	{
		global $app;

		$label = $entry->$tag;

		if (mb_strlen($label, 'utf-8') > self::MODIFY_MAX_LENGTH)
		{
			$label = wd_entities(trim(mb_substr($label, 0, self::MODIFY_MAX_LENGTH, 'utf-8'))) . '&hellip;';
		}
		else
		{
			$label = wd_entities($entry->$tag);
		}

		$title = $app->user->has_ownership($resume->module, $entry) ? 'Edit this item' : 'View this item';
		$key = $resume->idtag;
		$path = $resume->module;

		return new WdElement
		(
			'a', array
			(
				WdElement::T_INNER_HTML => $label,

				'class' => 'edit',
				'title' => t($title),
				'href' => WdRoute::encode('/' . $path . '/' . $entry->$key . '/edit')
			)
		);
	}

	static public function modify_code($label, $key, $resume)
	{
		return new WdElement
		(
			'a', array
			(
				WdElement::T_INNER_HTML => $label,

				'class' => 'edit',
				'title' => t('Edit this item'),
				'href' => WdRoute::encode('/' . $resume->module . '/' . $key . '/edit')
			)
		);
	}

	static public function select_code($tag, $which, $label, $resume)
	{
		if ($label)
		{
			$display_where = NULL;

			extract($resume->tags, EXTR_PREFIX_ALL, 'display');

			if ($display_where == $tag)
			{
				$rc = $label;
			}
			else
			{
				$ttl = t('Display only: :identifier', array(':identifier' => strip_tags($label)));

				$url = $resume->getURL
				(
					array
					(
						self::WHERE => $tag,
						self::IS => $which
					)
				);

				$rc = '<a class="filter" href="' . $url . '" title="' . $ttl . '">' . $label . '</a>';
			}
		}
		else
		{
			$rc = '&nbsp;';
		}

		return $rc;
	}

	static public function select_callback($entry, $tag, $resume)
	{
		$which = $entry->$tag;
		$label = wd_entities($which);

		return self::select_code($tag, $which, $label, $resume);
	}

	static public function bool_callback($entry, $tag, $resume)
	{
		$which = $entry->$tag;
		$label = $which ? 'Yes' : '';

		return self::select_code($tag, $which, t($label), $resume);
	}

	static public function user_callback($entry, $tag, $resume)
	{
		$uid = $entry->uid;

		if (isset($entry->username))
		{
			$username = wd_entities($entry->username);
		}
		else if ($uid)
		{
			static $users;

			if (!$users)
			{
				global $core;

				$users = $core->getModule('user.users')->model()->select
				(
					array('uid', 'username')
				)
				->fetchPairs();
			}

			if (empty($users[$uid]))
			{
				return t('<em>unknown-user-:uid</em>', array(':uid' => $uid));
			}

			$username = wd_entities($users[$uid]);
		}
		else
		{
			return t('<em>none</em>');
		}

		return self::select_code($tag, $entry->$tag, $username, $resume);
	}

	static public function email_callback($entry, $tag)
	{
		$email = $entry->$tag;

		$rc = '<a href="mailto:' . $email . '"';
		$rc .= ' title="' . t('Send an E-mail') . '">' . $email . '</a>';

		return $rc;
	}

	static public function size_callback($entry, $tag)
	{
		return wd_format_size($entry->$tag);
	}
}