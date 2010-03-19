<?php

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
							self::COLUMN_LABEL => '@manager.th.' . $identifier
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

		#
		# COLUMNS_ORDER
		#

		if (isset($tags[self::T_COLUMNS_ORDER]))
		{
			$this->columns = wd_array_sort_and_filter($tags[self::T_COLUMNS_ORDER], $this->columns);
		}









		$name = (string) $this->module;

		$request = $this->parseOptions($name);

		// FIXME-20100203: this is quite dangerous !!!

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

		if ($display_search)
		{
			global $core;

			$words = explode(' ', $display_search);
			$words = array_map('trim', $words);

			$queries = array();

			$schema = $this->model->getExtendedSchema();

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
		else if ($display_where && $display_is)
		{
			$where[] = '`' . $display_where . '` = ?';
			$params[] = $display_is;
		}

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
		$request = $_GET;

		// FIXME: use search-clear because 'search' is sent too when 'start' is changed, reseting 'start' to 1 if 'search' is empty

		if (isset($request[self::WHERE]) || isset($request[self::BY]) ||
			isset($request[self::ORDER]) || isset($request[self::LIMIT]) || isset($request[self::SEARCH]))
		{
			$request[self::START] = 1;
		}

		if (isset($_SESSION[self::OPTIONS][$name]))
		{
			$request += $_SESSION[self::OPTIONS][$name];
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

		if (isset($request[self::BY]) && empty($schema['fields'][$request[self::BY]]))
		{
			WdDebug::trigger('Unknown column %column used by display-by', array('%column' => $request[self::BY]));

			$request[self::BY] = null;
		}

		if (empty($request[self::BY]))
		{
			$order = $this->getTag(self::T_ORDER_BY);

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

		$_SESSION[self::OPTIONS][$name] = $request;

		return $request;
	}

	public function addJob($job, $label)
	{
		$this->jobs[$job] = $label;
	}

	private function getURL(array $modifier=array(), $fragment=null)
	{
		$url = '?' . http_build_query($modifier, null, '&');

		if ($fragment)
		{
			$url .= '#' . $fragment;
		}

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
			$rc .= '<th class="first key">';

			/*
			if (($this->idtag == $display_by)
			 && ($display_order == self::ORDER_ASC))
			{
				$order = self::ORDER_DESC;
			}
			else
			{
				$order = self::ORDER_ASC;
			}

			$rc .= '<a href="';

			$rc .= $this->getURL
			(
				array
				(
					self::BY => $this->idtag,
					self::ORDER => $order
				)
			);
			$rc .= '"' ;

			if ($display_by == $this->idtag)
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

			$rc .= '>#</a>';
			*/

			$rc .= '&nbsp;';

			$rc .= '</th>';

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
				$label = t($label);

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
							self::IS => '',
							self::SEARCH => ''
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

		#
		# obtain the contents of the cell using the appropriate method
		#

		$callback = 'get_cell_' . $tag;

		if (method_exists($this, $callback))
		{
			$rc .= $this->$callback($entry, $tag, $opt);
		}
		else if (empty($opt[self::COLUMN_HOOK]))
		{
			$rc .= wd_entities($entry->$tag);
		}
		else
		{
			$rc .= call_user_func($opt[self::COLUMN_HOOK], $entry, $tag, $this);
		}

		#
		# cell end
		#

		$rc .= '</td>';

		return $rc;
	}

	protected function get_cell_key($entry, $value)
	{
		global $user;

		$disabled = true;

		if ($user->hasOwnership($this->module, $entry))
		{
			$disabled = false;

			$this->checkboxes++;
		}

		$rc  = '<td class="key">';

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
							'value' => $value,
							'checked' => $disabled
						)
					)
				),

				'title' => t('Toggle selection for entry #\1', array($value)),
				'class' => 'checkbox-wrapper rectangle'
			)
		);

		$rc .= '</td>' . PHP_EOL;

		return $rc;
	}

	protected function getContents()
	{
		$rc = '';
		$idtag = $this->idtag;
		$count = count($this->entries);

		foreach ($this->entries as $i => $entry)
		{
			$ownership = null;

			if ($idtag)
			{
				global $user;

				$ownership = $user->hasOwnership($this->module, $entry);
			}

			$class = 'entry';

			if ($ownership === false)
			{
				$class .= ' no-ownership';
			}

			if ($i + 1 == $count)
			{
				$class .= ' last';
			}

			$rc .= '<tr class="' . $class . '">';

			#
			# if the id tag was provided, we had a column for the ids
			#

			if ($idtag)
			{
				$rc .= $this->get_cell_key($entry, $entry->$idtag);
			}

			#
			# create user defined columns
			#

			foreach ($this->columns as $tag => $opt)
			{
				$rc .= $this->get_cell($entry, $tag, $opt) . PHP_EOL;
			}

			#
			# end row
			#

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

		$rc .= '<td colspan="' . (count($this->columns) + 1) . '" style="text-align: center">';

		$search = isset($this->tags[self::SEARCH]) ? $this->tags[self::SEARCH] : NULL;
		$select = isset($this->tags[self::IS]) ? $this->tags[self::IS] : NULL;

		if ($search)
		{
			$rc .= t('There is no entry matching %search', array('%search' => $search));
		}
		else if ($select)
		{
			$rc .= t('There is no entry for the selection %selection', array('%selection' => $select));
		}
		else
		{
			$rc .= t('There is no entry: <strong><a href="!url">create a new entry&hellip;</a></strong>', array('!url' => WdRoute::encode('/' . $this->module . '/create')));
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
		$where = $this->tags[self::WHERE];
		$search = $this->tags[self::SEARCH];

		$rc = new WdForm
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
							'value' => $where ? null : $search,
							'size' => '16',
							'class' => 'search',
							'tabindex' => 0
						)
					)
				),

				'method' => 'get'
			)
		);

		if ($search && !$where)
		{
			$rc .= '<a class="reset"';
			$rc .= ' title="' . t('Cancel search') . '"';
			$rc .= ' href="?' . self::SEARCH . '=">&Chi;</a>';
		}

		return $rc;
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

			$browse  = '<span class="browse">';
			$browse .= '<a href="' . $url . ($start - $limit < 1 ? $count - $limit + 1 + ($count % $limit ? $limit - ($count % $limit) : 0) : $start - $limit) . '" class="browse previous">&lt;</a>';
			$browse .= '<a href="' . $url . ($start + $limit >= $count ? 1 : $start + $limit) . '" class="browse next">&gt;</a>';
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

		$rc = '<tr class="footer">';

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

		$rc .= '<td colspan="' . count($this->columns) . '">';

		if ($this->entries)
		{
			$rc .= $this->getJobs();
		}

		if ($this->count)
		{
			$rc .= $this->getLimiter();
		}

		$rc .= '</td>';
		$rc .= '</tr>';
		$rc .= PHP_EOL;

		return $rc;
	}

	public function __toString()
	{
		global $document;

		$document->addJavascript('resume.js', 170, dirname(__FILE__));
		$document->addStyleSheet('public/css/manage.css', 170);

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
				'value' => $this->getTag(self::T_BLOCK, 'manage')
			)
		);

		$rc .= '<table class="group manage" cellpadding="4" cellspacing="0">';

		$rc .= $this->getHeader();

		$rc .= '<tbody>';

		if (empty($this->entries))
		{
			$rc .= $this->getEmptyContents();
		}
		else
		{
			$rc .= $this->getContents();
		}
		$rc .= $this->getFooter();

		$rc .= '</tbody>';
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
		global $user;

		$label = $entry->$tag;

		if (mb_strlen($label, 'utf-8') > self::MODIFY_MAX_LENGTH)
		{
			$label = wd_entities(trim(mb_substr($label, 0, self::MODIFY_MAX_LENGTH, 'utf-8'))) . '&hellip;';
		}
		else
		{
			$label = wd_entities($entry->$tag);
		}

		$title = $user->hasOwnership($resume->module, $entry) ? 'Edit this item' : 'View this item';
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
						self::IS => $which,
						self::SEARCH => '',
						self::START => 1
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

	static public function date_callback($entry, $tag, $resume)
	{
		$value = $entry->$tag;

		if (!(int) $value)
		{
			return;
		}

		$date = array();

		if (!preg_match('#(\d{4})-(\d{2})-(\d{2})#', $value, $date))
		{
			return;
		}

		list(, $year, $month, $day) = $date;

		$display_where = NULL;
		$display_search = NULL;

		extract($resume->tags, EXTR_PREFIX_ALL, 'display');

		//
		// select by year
		//

		if (($display_where == $tag) && ($display_search == $year))
		{
			$rc = $year;
		}
		else
		{
			$ttl = t('Display only: :identifier', array(':identifier' => $year));

			$url = $resume->getURL
			(
				array
				(
					self::WHERE => $tag,
					self::SEARCH => $year,
					self::IS => '',
					self::START => 1
				)
			);

			$rc = '<a class="filter" href="' . $url . '"';
			$rc .= ' title="' . $ttl . '">' . $year . '</a>';
		}

		$rc .= '-';

		//
		// select by year+month
		//


		if (($display_where == $tag) && ($display_search == "$year$month"))
		{
			$rc .= $month;
		}
		else
		{
			$ttl = t('Display only: :identifier', array(':identifier' => "$year/$month"));

			$url = $resume->getURL
			(
				array
				(
					self::WHERE => $tag,
					self::SEARCH => "$year-$month",
					self::IS => '',
					self::START => 1
				)
			);

			$rc .= '<a class="filter" href="' . $url . '"';
			$rc .= ' title="' . $ttl . '">' . $month . '</a>';
		}

		$rc .= '-';

		//
		// select by year+month+day
		//

		if (($display_where == $tag)
		 && (substr($display_search, 0, 8) == "$year$month$day"))
		{
			$rc .= "$day";
		}
		else
		{
			$ttl = t('Display only: :identifier', array(':identifier' => "$year/$month/$day"));

			$url = $resume->getURL
			(
				array
				(
					self::WHERE => $tag,
					self::SEARCH => "$year-$month-$day",
					self::IS => '',
					self::START => 1
				)
			);

			$rc .= '<a class="filter" href="' . $url . '"';
			$rc .= ' title="' . $ttl . '">' . $day . '</a>';
		}

		// FIXME: should be in a datetime_callback
		//
		// time
		//

		$time = array();

		if (preg_match('#\s{0,1}(\d{2})\:(\d{2})\:(\d{2})#', $value, $time))
		{
			$rc .= '&nbsp;<small>' . $time[1] . ':' . $time[2] . '</small>';
		}

	//	wd_log('value: \1, date: \2, time: \3', $value, $date, $time);

		return $rc;
	}

	static public function size_callback($entry, $tag)
	{
		$size = $entry->$tag;

		if ($size < 1024)
		{
			return t('\1 bytes', array($size));
		}
		else if ($size < 1024 * 1024)
		{
			return t('\1 Kb', array(round($size / 1024, 2)));
		}
		else if ($size < 1024 * 1024 * 1024)
		{
			return t('\1 Mb', array(round($size / (1024 * 1024))));
		}
		else
		{
			return t('\1 Gb', array(round($size / (1024 * 1024 * 1024))));
		}
	}
}