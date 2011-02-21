<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
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

	public function __construct(WdModule $module, WdModel $model, array $tags)
	{
		global $core;

		parent::__construct(null, $tags);

		$this->module = $module;
		$this->model = $model;

		if (empty($tags[self::T_COLUMNS]))
		{
			throw new WdException('The %tag tag is required', array('%tag' => 'T_COLUMNS'));
		}

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
							self::COLUMN_LABEL => $identifier
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

					$this->addJob(WdModule::OPERATION_DELETE, t('delete.operation.short_title'));
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

		list($conditions, $conditions_args) = $this->get_query_conditions($request);

		$query = $this->model->where(implode(' AND ', $conditions), $conditions_args);
		$query = $this->alter_query($query);

		$this->count = $query->count;

		$query = $this->alter_range_query($query);

		$records = $this->load_range($query);
		$this->entries = $this->alter_records($records);
	}

	protected function get_query_conditions(array $request)
	{
		global $core;

		$where = array();
		$params = array();

		$display_search = $request[self::SEARCH];
		$display_where = $request[self::WHERE];
		$display_is = $request[self::IS];

		$schema = $this->model->get_extended_schema();

		if ($display_search)
		{
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

		if ($this->module instanceof system_nodes_WdModule || $this->module instanceof taxonomy_vocabulary_WdModule)
		{
			$where['siteid'] = '(siteid = 0 OR siteid = ' . (int) $core->working_site_id . ')';
		}

		// TODO: move this to their respective manager

		$module = $this->module;

		if ($module instanceof user_users_WdModule)
		{
			#
			# we load only the entries that where created by the module
			#

			$where[] = 'constructor = ?';
			$params[] = (string) $module;
		}

		return array($where, $params);
	}

	/**
	 *
	 * Enter description here ...
	 * @param unknown_type $query
	 * @return WdActiveRecordQuery Altered query.
	 */

	protected function alter_range_query(WdActiveRecordQuery $query)
	{
		$request = $this->tags;

		$order = null;

		$display_by = $request[self::BY];

		if ($display_by)
		{
			$order = "`$display_by` " . ($request[self::ORDER] == 'desc' ? 'DESC' : 'ASC');
		}

		$display_start = $request[self::START] - 1; // FIXME-20101127: what is this horror !?
		$display_limit = $request[self::LIMIT];

		return $query->order($order)->limit($display_start, $display_limit);
	}

	protected function load_range(WdActiveRecordQuery $query)
	{
		return $query->all;
	}

	protected function alter_records(array $records)
	{
		return $records;
	}

	protected function parseOptions($name)
	{
		global $core;

		$request = $_GET;

		// FIXME: use search-clear because 'search' is sent too when 'start' is changed, reseting 'start' to 1 if 'search' is empty

		if (isset($request[self::WHERE]) || isset($request[self::BY]) ||
			isset($request[self::ORDER]) || isset($request[self::LIMIT]) || isset($request[self::SEARCH]))
		{
			$request[self::START] = 1;
		}

		if (isset($core->session->wdmanager['options'][$name]))
		{
			$request += $core->session->wdmanager['options'][$name];
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



		$schema = $this->model->get_extended_schema();

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

		$core->session->wdmanager['options'][$name] = $request;

//		$core->user->metas["manager.$name.options"] = json_encode($request);

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

		$constructor_flat_id = $this->module->flat_id;

		foreach ($this->columns as $by => $col)
		{
			$class = isset($col[self::COLUMN_CLASS]) ? $col[self::COLUMN_CLASS] : null;
			$rc .= $class ? '<th class="' . $class . '">' : '<th>';

			$label = isset($col[self::COLUMN_LABEL]) ? $col[self::COLUMN_LABEL] : null;

			if ($label)
			{
				$label = t
				(
					$by, array(), array
					(
						'scope' => array($constructor_flat_id, 'manager', 'title'),
						'default' => t
						(
							$by, array(), array('scope' => array($constructor_flat_id, 'manager', 'label'), 'default' => $label)
						)
					)
				);

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
							$rc .= $display_order == self::ORDER_ASC ? 'class="asc" ' : 'class="desc" ';
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

	protected function get_cell(WdActiveRecord $record, $property, $opt)
	{
		$class = isset($opt[self::COLUMN_CLASS]) ? ' class="' . $opt[self::COLUMN_CLASS] . '"' : null;
		$content = call_user_func($opt[self::COLUMN_HOOK], $record, $property, $this);

		return "<td$class>$content</td>";
	}

	protected function get_cell_key(WdActiveRecord $record, $property)
	{
		global $core;

		$disabled = true;

		if ($core->user->has_ownership($this->module, $record))
		{
			$disabled = false;

			$this->checkboxes++;
		}

		$value = $record->$property;

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
		global $core;

		$user = $core->user;
		$module = $this->module;
		$idtag = $this->idtag;
		$count = count($this->entries);

		$rc = '';

		foreach ($this->entries as $i => $entry)
		{
			$ownership = $idtag ? $user->has_ownership($module, $entry) : null;
			$class = '';

			if ($ownership === false)
			{
				$class .= ' no-ownership';
			}

			$rc .= '<tr ' . ($class ? 'class="' . $class . '"' : '') . '>';

			#
			# create user defined columns
			#

			foreach ($this->columns as $tag => $opt)
			{
				$rc .= $this->get_cell($entry, $tag, $opt) . PHP_EOL;
			}

			$rc .= '</tr>';
		}

		return $rc;
	}

	protected function getEmptyContents()
	{
		$search = $this->get(self::SEARCH);
		$select = $this->get(self::IS);

		$rc  = '<tr><td colspan="' . (count($this->columns) + 1) . '" class="create-new">';

		if ($search)
		{
			$rc .= t('Your search <q><strong>!search</strong></q> did not match any record.', array('!search' => $search));
		}
		else if ($select)
		{
			$rc .= t('Your selection <q><strong>!selection</strong></q> dit not match any record.', array('!selection' => $select));
		}
		else
		{
			$rc .= t('@manager.emptyCreateNew', array('!url' => '/admin/' . $this->module . '/create'));
		}

		$rc .= '</td></tr>';

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
							WdElement::T_DATASET => array
							(
								'placeholder' => t('Search')
							),

							'title' => t('Search in the records'),
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
		global $core;

		$document = $core->document;

		if ($document instanceof WdPDocument)
		{
			$options = '<div class="manage">' . $this->getSearch() . $this->browse . '</div>';
			$document->addToBlock($options, 'menu-options');
		}
	}

	protected function getLimiter()
	{
		$count = $this->count;
		$start = $this->tags[self::START];
		$limit = $this->tags[self::LIMIT];

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

		$page_limit_selector = null;

		if (($limit >= 20) || ($count >= $limit))
		{
			$page_limit_selector = new WdElement
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

			$page_limit_selector = ' &nbsp; ' . t(':page_limit_selector by page', array(':page_limit_selector' => (string) $page_limit_selector));
		}

		$browse = null;

		if ($count > $limit)
		{
			$url = '?start=';

			// ◀▶ ➜ ▷ ❮❯❰❱

			$browse  = '<span class="browse">';
			$browse .= '<a href="' . $url . ($start - $limit < 1 ? $count - $limit + 1 + ($count % $limit ? $limit - ($count % $limit) : 0) : $start - $limit) . '" class="browse previous">◀</a>';
			$browse .= '<a href="' . $url . ($start + $limit >= $count ? 1 : $start + $limit) . '" class="browse next">▶</a>';
			$browse .= '</span>';
		}

		$this->browse = $browse;

		# the hidden select is a trick for vertical alignement with the operation select

		return <<<EOT
<div class="limiter">
	<select style="visibility: hidden;"><option>&nbsp;</option></select>
	{$ranger}{$page_limit_selector}{$browse}
</div>
EOT;
	}

	protected function getJobs()
	{
		if (!$this->jobs)
		{
			return;
		}

		$options = array(null => t('For the selection…', array(), array('scope' => 'manager')));

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
		$rc  = '<tfoot>';
		$rc .= '<tr>';

		if ($this->idtag)
		{
			$rc .= '<td class="key">';

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
								WdElement::E_CHECKBOX
							)
						),

						'class' => 'checkbox-wrapper rectangle',
						'title' => t('Toggle selection for the entries ([alt] to toggle selection)')
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

		// +1 for the 'operation' column apparently

		$rc .= '<td colspan="' . $ncolumns . '">';

		$rc .= $this->entries ? $this->getJobs() : '';
		$rc .= $this->count ? $this->getLimiter() : '';

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

		$rc .= $head . PHP_EOL . $foot . PHP_EOL . $body . PHP_EOL;

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
		global $core;

		$label = $entry->$tag;

		if (mb_strlen($label) > self::MODIFY_MAX_LENGTH)
		{
			$label = wd_entities(trim(mb_substr($label, 0, self::MODIFY_MAX_LENGTH))) . '…';
		}
		else
		{
			$label = wd_entities($entry->$tag);
		}

		$title = $core->user->has_ownership($resume->module, $entry) ? 'Edit this item' : 'View this item';
		$key = $resume->idtag;
		$path = $resume->module;

		return new WdElement
		(
			'a', array
			(
				WdElement::T_INNER_HTML => $label,

				'class' => 'edit',
				'title' => t($title),
				'href' => '/admin/' . $path . '/' . $entry->$key . '/edit'
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
				'href' => '/admin/' . $resume->module . '/' . $key . '/edit'
			)
		);
	}

	static public function select_code($tag, $which, $label, $resume)
	{
		if ($label)
		{
			if ($tag == $resume->get(self::WHERE))
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

	static public function email_callback($record, $property)
	{
		$email = $record->$property;

		return '<a href="mailto:' . $email . '" title="' . t('Send an E-mail') . '">' . $email . '</a>';
	}

	static public function size_callback($record, $property)
	{
		return wd_format_size($record->$property);
	}
}