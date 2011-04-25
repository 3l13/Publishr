<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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

	protected $browse;

	/**
	 * @var WdTranslatorProxi Proxis translator with the following scope: "manager.<module_flat_id>".
	 */
	protected $t;

	/**
	 * @var array The options include:
	 *
	 * int start: The index of the first record to display. 1 for the first record.
	 * int limit: The number of records to display.
	 * array|null order: Columns used to sort the records.
	 * string|null search: Key words to search for, altering the query conditions.
	 * array filters: The filters currently used to filter the records, ready for the
	 * WdActiveRecordQuery::where() method.
	 */
	protected $options = array();

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
							'label' => $identifier
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

		$this->t = new WdTranslatorProxi(array('scope' => array($module->flat_id, 'manager')));
	}

	/**
	 * Renders the object into a HTML string.
	 *
	 * @see WdElement::__toString()
	 */
	public function __toString()
	{
		global $core, $document;

		$document->js->add('resume.js', -170);
		$document->css->add('public/css/manage.css', -170);

		$module_id = $this->module->id;
		$session = $core->session;

		$options = $this->retrieve_options($module_id);

		$modifiers = array_diff_assoc($_GET, $options);
		// FIXME: if modifiers ?

		$this->options = $this->update_options($options, $modifiers);

		$this->store_options($this->options, $module_id);

		#
		# load entries
		#

		list($conditions, $conditions_args) = $this->get_query_conditions($this->options);

		$query = $this->model->where(implode(' AND ', $conditions), $conditions_args);
		$query = $this->alter_query($query, $this->options['filters']);

		$this->count = $query->count;

		$query = $this->alter_range_query($query, $this->options);

		$records = $this->load_range($query);
		$this->entries = $this->alter_records($records);

		#
		# extend columns with additional information.
		#

		$this->columns = $this->extend_columns($this->columns);

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

		if ($this->entries || $this->options['filters'])
		{
			if ($this->entries)
			{
				$body  = '<tbody>';
				$body .= $this->render_body();
				$body .= '</tbody>';
			}
			else
			{
				$body  = '<tbody><tr><td colspan="' . count($this->columns) . '">' . $this->render_empty_body() . '</td></tr></tbody>';
			}

			$head = $this->render_head();
			$foot = $this->render_foot();

			$rc .= '<table class="group manage" cellpadding="4" cellspacing="0">';

			$rc .= $head . PHP_EOL . $foot . PHP_EOL . $body . PHP_EOL;

			$rc .= '</table>' . PHP_EOL;
		}
		else
		{
			$rc .= $this->render_empty_body();
		}

		$rc .= '</form>' . PHP_EOL;

		$this->inject_search();

		return $rc;
	}

	/**
	 * Retrieves previously used options.
	 *
	 * @param string $name Storage name for the options, usualy the module's id.
	 *
	 * @return array Previously used options, or brand new ones is none were defined.
	 */
	protected function retrieve_options($name)
	{
		global $core;

		$options = array
		(
			'start' => 1,
			'limit' => 10,
			'order' => array(),
			'search' => null,
			'filters' => array()
		);

		$session = $core->session;

		if (isset($session->manager[$name]))
		{
			$options = $session->manager[$name] + $options;
		}

		if (!$options['order'])
		{
			$order = $this->get(self::T_ORDER_BY);

			if ($order)
			{
				list($id, $direction) = ((array) $order) + array(1 => 'asc');

				if (is_string($direction))
				{
					$direction = $direction == 'desc' ? -1 : 1;
				}

				$options['order'] = array($id => $direction);
			}
			else
			{
				foreach ($this->columns as $id => $column)
				{
					if (empty($column[self::COLUMN_SORT]))
					{
						continue;
					}

					$options['order'] = array($id => isset($column['default_order_direction']) ? $column['default_order_direction'] : 1);

					break;
				}
			}
		}

		return $options;
	}

	/**
	 * Store options for later use.
	 *
	 * @param array $options The options to store.
	 * @param string $name Storage name for the options, usualy the module's id.
	 */
	protected function store_options(array $options, $name)
	{
		global $core;

		$core->session->manager[$name] = $options;
	}

	/**
	 * Updates options with the provided modifiers.
	 *
	 * The method updates the `order`, `start`, `limit`, `search` and `filters` options.
	 *
	 * The `start` options is reset to 1 when the `order`, `search` or `filters` options change.
	 *
	 * @param array $options Previous options.
	 * @param array $modifiers Options modifiers.
	 *
	 * @return array Updated options.
	 */
	protected function update_options(array $options, array $modifiers)
	{
		if (isset($modifiers['start']))
		{
			$options['start'] = max(filter_var($modifiers['start'], FILTER_VALIDATE_INT), 1);
		}

		if (isset($modifiers['limit']))
		{
			$options['limit'] = max(filter_var($modifiers['limit'], FILTER_VALIDATE_INT), 10);
		}

		if (isset($modifiers['search']))
		{
			$options['search'] = $modifiers['search'];
			$options['start'] = 1;
		}

		if (isset($modifiers['order']))
		{
			$order = $this->update_order($options['order'], $modifiers['order']);

			if ($order != $options['order'])
			{
				$options['start'] = 1;
			}

			$options['order'] = $order;
		}

		$filters = $this->update_filters($options['filters'], $modifiers);

		if ($filters != $options['filters'])
		{
			$options['filters'] = $filters;
			$options['start'] = 1;
		}

		return $options;
	}

	protected function update_order(array $order, $modifiers)
	{
		list($id, $direction) = explode(':', $modifiers) + array(1 => null);

		if (empty($this->columns[$id]))
		{
			return $order;
		}

		return array($id => $direction == 'desc' ? -1 : 1);
	}

	/**
	 * Update filters with the specified modifiers.
	 *
	 * The extended schema of the model is used to automatically handle booleans, integers,
	 * dates (date, datetime and timestamp) and strings (char, varchar).
	 *
	 * @param array $filters
	 * @param array $modifiers
	 *
	 * @return array Updated filters.
	 */
	protected function update_filters(array $filters, array $modifiers)
	{
		static $as_strings = array('char', 'varchar', 'date', 'datetime', 'timestamp');

		$fields = $this->model->extended_schema['fields'];

		foreach ($modifiers as $identifier => $value)
		{
			if (empty($fields[$identifier]))
			{
				continue;
			}

			$type = $fields[$identifier]['type'];

			if ($type == 'boolean')
			{
				$value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
			}
			else if ($type == 'integer')
			{
				$value = filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
			}
			else if (in_array($type, $as_strings))
			{
				if ($value === '')
				{
					$value = null;
				}
			}
			else continue;

			if ($value === null)
			{
				unset($filters[$identifier]);

				continue;
			}

			$filters[$identifier] = $value;
		}

		return $filters;
	}

	protected function parseColumns($columns)
	{
		foreach ($columns as $tag => &$column)
		{
			if (!is_array($column))
			{
				$column = array();
			}

			if (isset($column[self::COLUMN_HOOK]))
			{
				continue;
			}

			$callback = 'render_cell_' . $tag;

			if (method_exists($this, $callback))
			{
				$column[self::COLUMN_HOOK] = array($this, $callback);
			}
			else if (method_exists($this, 'get_cell_' . $tag))
			{
				$column[self::COLUMN_HOOK] = array($this, 'get_cell_' . $tag);
			}
			else
			{
				$column[self::COLUMN_HOOK] = array($this, 'render_raw_cell');
			}
		}

		#
		# key
		#

		if ($this->idtag)
		{
			$columns = array_merge
			(
				array
				(
					$this->idtag => array
					(
						'label' => null,
						'class' => 'key',
						self::COLUMN_HOOK => array($this, 'render_key_cell')
					)
				),

				$columns
			);

//			var_dump($columns);
		}

		return $columns;
	}

	protected function get_query_conditions(array $options)
	{
		global $core;

		$where = array();
		$params = array();

		$display_search = $options['search'];

		$fields = $this->model->extended_schema['fields'];

		if ($display_search)
		{
			$words = explode(' ', $display_search);
			$words = array_map('trim', $words);

			$queries = array();

			foreach ($words as $word)
			{
				$concats = '';

				// FIXME-20081223: special cases form dates 2008, 2008-12, 2008-12-23

				foreach ($fields as $identifier => $definition)
				{
					$type = $definition['type'];

					if ($type != 'varchar' && $type != 'text')
					{
						continue;
					}

					$concats .= ', `' . $identifier . '`';
				}

				if (!$concats)
				{
					continue;
				}

				$where[] = 'CONCAT_WS(" ", ' . substr($concats, 2) . ') LIKE ?';
				$params[] = '%' . $word . '%';
			}
		}

		foreach ($this->options['filters'] as $identifier => $value)
		{
			$type = $fields[$identifier]['type'];

			if ($type == 'timestamp' || $type == 'date' || $type == 'datetime')
			{
				list($year, $month, $day) = explode('-', $value) + array(0, 0, 0);

				if ($year)
				{
					$where[] = "YEAR(`$identifier`) = ?";
					$params[] = (int) $year;
				}

				if ($month)
				{
					$where[] = "MONTH(`$identifier`) = ?";
					$params[] = (int) $month;
				}

				if ($day)
				{
					$where[] = "DAY(`$identifier`) = ?";
					$params[] = (int) $day;
				}
			}
			else
			{
				$where[] = "$identifier = ?";
				$params[] = $value;
			}
		}

		#
		# site
		#

		if ($this->module instanceof system_nodes_WdModule || $this->module instanceof taxonomy_vocabulary_WdModule)
		{
			$where['siteid'] = '(siteid = 0 OR siteid = ' . $core->site_id . ')';
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

	protected function alter_range_query(WdActiveRecordQuery $query, array $options)
	{
		$order = $options['order'];

		if ($order)
		{
			$o = '';

			foreach ($order as $id => $direction)
			{
				$o .= ', ' . $id . ' ' . ($direction < 0 ? 'DESC' : '');
			}

			$query->order(substr($o, 2));
		}

		return $query->limit($options['start'] - 1, $options['limit']);
	}

	protected function load_range(WdActiveRecordQuery $query)
	{
		return $query->all;
	}

	protected function alter_records(array $records)
	{
		return $records;
	}


	protected function extend_columns(array $columns)
	{
		foreach ($columns as $id => &$column)
		{
			$fallback = 'extend_column';
			$callback = $fallback . '_' . $id;

			if (!$this->has_method($callback))
			{
				$callback = $fallback;
			}

			$column = $this->$callback($column, $id);
		}

		return $columns;
	}

	/**
	 * Extends a column regarding filtering, ordering and more.
	 *
	 * @param array $options Initial options from columns definitions.
	 * @param string $id The identifier of the header cell.
	 *
	 * @return array header cell options:
	 *
	 *    array|null filters The filter options available.
	 *    bool filtering true if the filter is currently used to filter the records, false otherwise.
	 *    string resets Query string to reset the filter.
	 *    mixed order null if the column is not used for ordering, -1 for descending ordering, 1
	 *    for ascending ordering.
	 *    bool sorted true if the column is sorted, false otherwise.
	 */
	protected function extend_column(array $column, $id)
	{
		$options = $this->options;
		$ordering = isset($options['order'][$id]);

		return $column + array
		(
			'class' => null,

			'filters' => null,
			'filtering' => isset($options['filters'][$id]),
			'reset' => "?$id=",

			'orderable' => isset($column['label']),
			'order' => $ordering ? $options['order'][$id] : null,
			'default_order' => 1
		);
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

	/**
	 * Renders the THEAD element.
	 *
	 * @return string The rendered THEAD element.
	 */
	protected function render_head()
	{
		$cells = '';

		foreach ($this->columns as $id => $column)
		{
			$cells .= $this->render_column($column, $id);
		}

		return <<<EOT
<thead>
	<tr>$cells</tr>
</thead>
EOT;
	}

	/**
	 * Renders a column header.
	 *
	 * @param array $cell
	 * @param string $id
	 *
	 * @return string The rendered THEAD cell.
	 */
	protected function render_column(array $column, $id)
	{
		$class = $column['class'];

		$orderable = $column['orderable'];

		if ($orderable)
		{
			$class .= ' orderable';
		}

		$filtering = $column['filtering'];

		if ($filtering)
		{
			$class .= ' filtering';
		}

		$filters = $column['filters'];

		if ($filters)
		{
			$class .= ' filters';
		}

		$rc = '';
		$rc .= $class ? '<th class="' . trim($class) . '">' : '<th>';
		$rc .= '<div>';

		$t = $this->t;

		$label = isset($column['label']) ? $column['label'] : null;

		if ($label)
		{
			$label = $t($id, array(), array('scope' => '.title', 'default' => $t($id, array(), array('scope' => '.label', 'default' => $label))));
		}

		if ($filtering)
		{
			$rc .= '<a href="' . $column['reset'] . '" title="' . $t('View all') . '">' . ($label ? $label : '&nbsp;') . '</a>';
		}
		else if ($label && $orderable)
		{
			$order = $column['order'];
			$reverse = ($order === null) ? $column['default_order'] : -$order;

			$rc .= new WdElement
			(
				'a', array
				(
					WdElement::T_INNER_HTML => $label,

					'title' => $t('Sort by: :identifier', array(':identifier' => $label)),
					'href' => "?order=$id:" . ($reverse < 0 ? 'desc' : 'asc'),
					'class' => $order ? ($order < 0 ? 'desc' : 'asc') : null
				)
			);
		}
		else if ($label)
		{
			$rc .= $label;
		}
		else
		{
			$rc .= '&nbsp;';
		}

		if ($filters)
		{
			$rc .= $this->render_column_options($filters, $id, $column);
		}

		$rc .= '</div>';
		$rc .= '</th>';

		return $rc;
	}

	/**
	 * Renders a column filter.
	 *
	 * @param array|string $filter
	 * @param string $id
	 * @param array $header
	 */
	protected function render_column_options($filter, $id, $header)
	{
		$rc = '';

		/*
		if ($header['filtering'])
		{
			$rc .= '<li class="reset"><a href="' . $header['reset'] . '">View all</a></li>';
		}
		*/

		foreach ($filter['options'] as $qs => $label)
		{
			if ($qs[0] == '=')
			{
				$qs = $id . $qs;
			}

			$label = t($label);

			$rc .= '<li><a href="?' . $qs . '">' . wd_entities($label) . '</a></li>';
		}

		return '<ul>' . $rc . '</ul>';
	}

	protected function render_body()
	{
		global $core;

		$user = $core->user;
		$module = $this->module;
		$idtag = $this->idtag;

		$rc = '';

		foreach ($this->entries as $record)
		{
			$class = '';

			$ownership = $idtag ? $user->has_ownership($module, $record) : null;

			if ($ownership === false)
			{
				$class .= ' no-ownership';
			}

			$rc .= '<tr ' . ($class ? 'class="' . $class . '"' : '') . '>';

			foreach ($this->columns as $id => $column)
			{
				$rc .= $this->render_cell($record, $id, $column) . PHP_EOL;
			}

			$rc .= '</tr>';
		}

		return $rc;
	}

	protected function render_empty_body()
	{
		global $core;

		$search = $this->options['search'];
		$filters = implode(', ', $this->options['filters']);

		$rc  = '<div class="empty">';

		if ($search)
		{
			$rc .= t('Your search <q><strong>!search</strong></q> did not match any record.', array('!search' => $search));
		}
		else if ($filters)
		{
			$rc .= t('Your selection <q><strong>!selection</strong></q> dit not match any record.', array('!selection' => $filters));
		}
		else
		{
			$rc .= t('@manager.emptyCreateNew', array('!url' => $core->site->path . '/admin/' . $this->module . '/create'));
		}

		$rc .= '</div>';

		return $rc;
	}

	protected function render_cell($record, $property, $opt)
	{
		$class = isset($opt['class']) ? ' class="' . $opt['class'] . '"' : null;
		$content = call_user_func($opt[self::COLUMN_HOOK], $record, $property, $this);

		return "<td$class>$content</td>";
	}

	protected function render_key_cell(WdActiveRecord $record, $property)
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

	/**
	 * Renders the "search" element to be injected in the document.
	 *
	 * @return string The rendered "search" element.
	 */
	protected function render_search()
	{
		$search = $this->options['search'];

		return (string) new WdForm
		(
			array
			(
				WdElement::T_CHILDREN => array
				(
					'search' => new WdElement
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

	protected function inject_search()
	{
		global $core;

		$document = $core->document;

		if ($document instanceof WdPDocument)
		{
			$options = '<div class="manage">' . $this->render_search() . $this->browse . '</div>';
			$document->addToBlock($options, 'menu-options');
		}
	}

	protected function render_limiter()
	{
		$count = $this->count;
		$start = $this->options['start'];
		$limit = $this->options['limit'];

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

	protected function render_foot()
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
		$rc .= $this->count ? $this->render_limiter() : '';

		$rc .= '</td>';

		$rc .= '</tr>';
		$rc .= '</tfoot>';
		$rc .= PHP_EOL;

		return $rc;
	}

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
				'href' => $core->site->path . '/admin/' . $path . '/' . $entry->$key . '/edit'
			)
		);
	}

	static public function modify_code($label, $key, $resume)
	{
		global $core;

		return new WdElement
		(
			'a', array
			(
				WdElement::T_INNER_HTML => $label,

				'class' => 'edit',
				'title' => t('Edit this item'),
				'href' => $core->site->path . '/admin/' . $resume->module . '/' . $key . '/edit'
			)
		);
	}

	protected function render_cell_boolean($record, $property)
	{
		return $this->render_filter_cell($record, $property, $record->$property ? $this->t->__invoke('Yes') : '');
	}

	protected function render_cell_email($record, $property)
	{
		$email = $record->$property;

		return '<a href="mailto:' . $email . '" title="' . t('Send an E-mail') . '">' . $email . '</a>';
	}
}