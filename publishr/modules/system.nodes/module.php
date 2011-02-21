<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class system_nodes_WdModule extends WdPModule
{
	const PERMISSION_MODIFY_ASSOCIATED_SITE = 'modify associated site';

	protected function resolve_primary_model_tags($tags)
	{
		return parent::resolve_model_tags($tags, 'primary') + array
		(
			system_nodes_WdModel::T_CONSTRUCTOR => $this->id
		);
	}

	const OPERATION_ADJUST_ADD = 'adjustAdd';

	/**
	 * Overrides the method to control the following properties:
	 *
	 * `constructor`: In order to avoid misuse and errors, the constructor of the record is set by
	 * the method.
	 *
	 * `uid`: Only users with the PERMISSION_ADMINISTER permission can choose the user of records. If
	 * the user saving a record has no such permission, the Node::UID property is removed from the
	 * properties created by the WdModule::control_properties_for_operation() method.
	 *
	 * `siteid`: If the user is creating a new record or the user has no permission to choose the
	 * record's site, the property is set to the value of the working site's id.
	 *
	 * @param WdOperation $operation An operation object.
	 */

	protected function control_properties_for_operation_save(WdOperation $operation)
	{
		global $core;

		$properties = parent::control_properties_for_operation_save($operation);

		$properties[Node::CONSTRUCTOR] = $this->id;

		$user = $core->user;

		if (!$user->has_permission(self::PERMISSION_ADMINISTER, $this))
		{
			unset($properties[Node::UID]);
		}

		if (!$operation->key || !$user->has_permission(self::PERMISSION_MODIFY_ASSOCIATED_SITE))
		{
			$properties[Node::SITEID] = $core->working_site_id;
		}

		return $properties;
	}

	protected function operation_save(WdOperation $operation)
	{
		global $core;

		$rc = parent::operation_save($operation);

		$key = $rc['key'];
		$record = $this->model[$key];

		wd_log_done
		(
			$rc['mode'] == 'update'
			? '%title has been saved in %module.'
			: 'The entry %title has been created in %module.', array
			(
				'%title' => wd_shorten($record->title), '%module' => $this->id
			),

			'save'
		);

		#
		# metas
		#

		$params = &$operation->params;

		if (isset($params['metas']))
		{
			$metas = $record->metas;

			foreach ($params['metas'] as $key => $value)
			{
				if (is_array($value))
				{
					$value = serialize($value);
				}
				else if (!strlen($value))
				{
					$value = null;
				}

				$metas[$key] = $value;
			}
		}

		return $rc;
	}

	const OPERATION_ONLINE = 'online';

	protected function operation_query_online(WdOperation $operation)
	{
		$entries = $operation->params['entries'];
		$count = count($entries);

		return array
		(
			'params' => array
			(
				'entries' => $entries
			)
		);
	}

	protected function controls_for_operation_online(WdOperation $operation)
	{
		return array
		(
			self::CONTROL_PERMISSION => self::PERMISSION_MAINTAIN,
			self::CONTROL_OWNERSHIP => true,
			self::CONTROL_VALIDATOR => false
		);
	}

	protected function operation_online(WdOperation $operation)
	{
		$record = $operation->record;
		$record->is_online = true;
		$record->save();

		wd_log_done('!title is now online', array('!title' => $record->title));

		return true;
	}

	protected function operation_query_offline(WdOperation $operation)
	{
		$entries = $operation->params['entries'];
		$count = count($entries);

		return array
		(
			'params' => array
			(
				'entries' => $entries
			)
		);
	}

	/*
	 * The "offline" operation is used to put an entry offline.
	 */

	const OPERATION_OFFLINE = 'offline';

	protected function controls_for_operation_offline(WdOperation $operation)
	{
		return array
		(
			self::CONTROL_PERMISSION => self::PERMISSION_MAINTAIN,
			self::CONTROL_OWNERSHIP => true,
			self::CONTROL_VALIDATOR => false
		);
	}

	protected function operation_offline(WdOperation $operation)
	{
		$record = $operation->record;
		$record->is_online = false;
		$record->save();

		wd_log_done('!title is now offline', array('!title' => $record->title));

		return true;
	}

	protected function block_edit(array $properties, $permission)
	{
		global $core;

		$uid_el = null;
		$siteid_el = null;

		if ($core->user->has_permission(self::PERMISSION_ADMINISTER, $this))
		{
			$uid_el = new WdElement
			(
				'select', array
				(
					WdElement::T_LABEL => '.user',
					WdElement::T_LABEL_POSITION => 'before',

					WdElement::T_OPTIONS => array
					(
						null => ''
					)
					+ $core->models['user.users']->select('uid, username')->order('username')->pairs,

					WdElement::T_REQUIRED => true,
					WdElement::T_DEFAULT => $core->user->uid,
					WdElement::T_GROUP => 'admin',
					WdElement::T_DESCRIPTION => '.user'
				)
			);
		}

		if ($core->user->has_permission(self::PERMISSION_MODIFY_ASSOCIATED_SITE, $this))
		{
			// TODO-20100906: this should be added by the "site.sites" modules using the alter event.

			$siteid_el = new WdElement
			(
				'select', array
				(
					WdElement::T_LABEL => '.siteid',
					WdElement::T_LABEL_POSITION => 'before',
					WdElement::T_OPTIONS => array
					(
						null => ''
					)
					+ $core->models['site.sites']->select('siteid, IF(admin_title != "", admin_title, concat(title, ":", language))')->order('admin_title, title')->pairs,

//					WdElement::T_DEFAULT => $core->working_site_id,
					WdElement::T_GROUP => 'admin',
					WdElement::T_DESCRIPTION => '.siteid'
				)
			);
		}

		return array
		(
			WdElement::T_GROUPS => array
			(
				'node' => array
				(
					'weight' => -10,
					'title' => 'do not use node section anymore!',
					'class' => 'form-section flat'
				),

				'online' => array // FIXME-20110402: should be 'visibility'
				(
					'title' => '.visibility',
					'class' => 'form-section flat',
					'weight' => 400
				)
			),

			WdElement::T_CHILDREN => array
			(
				Node::TITLE => new WdTitleSlugComboElement
				(
					array
					(
						WdForm::T_LABEL => '.title',
						WdElement::T_REQUIRED => true,
						WdTitleSlugComboElement::T_NODEID => $properties[Node::NID],
						WdTitleSlugComboElement::T_SLUG_NAME => 'slug'
					)
				),

				Node::UID => $uid_el,

				Node::SITEID => $siteid_el,

				Node::IS_ONLINE => new WdElement
				(
					WdElement::E_CHECKBOX, array
					(
						WdElement::T_LABEL => '.is_online',
						WdElement::T_GROUP => 'online',
						WdElement::T_DESCRIPTION => '.is_online'
					)
				)
			),
		);
	}

	protected function block_manage()
	{
		return new system_nodes_WdManager
		(
			$this, array
			(
				WdManager::T_COLUMNS_ORDER => array
				(
					'title', 'uid', 'constructor', 'is_online', 'created', 'modified'
				)
			)
		);
	}

	protected function block_adjust(array $params)
	{
		return new WdAdjustNodeElement
		(
			array
			(
				WdAdjustNodeElement::T_CONSTRUCTOR => $this->id,
				WdElement::T_DESCRIPTION => null,

				'value' => isset($params['value']) ? $params['value'] : null
			)
		);
	}

	protected function block_adjustResults(array $options=array())
	{
		$options += array
		(
			'page' => 0,
			'limit' => 10,
			'search' => null,
			'selected' => null
		);

		#
		# search
		#

		$where = array();
		$values = array();

		if ($this->id != 'system.nodes')
		{
			$where[] = 'constructor = ?';
			$values[] = $this->id;
		}

		$search = $options['search'];

		if ($search)
		{
			$concats = array();

			$words = explode(' ', $options['search']);
			$words = array_map('trim', $words);

			foreach ($words as $word)
			{
				$where[] = 'title LIKE ?';
				$values[] = '%' . $word . '%';
			}
		}

		$page = $options['page'];
		$limit = $options['limit'];
		$selected = $options['selected'];

		list($entries, $count) = $this->adjust_loadRange($where, $values, $limit, $page);

		$rc = '<div class="results">';

		if ($count)
		{
			$rc .= '<ul>';

			foreach ($entries as $record)
			{
				$rc .= ($record->nid == $selected) ? '<li class="selected">' : '<li>';
				$rc .= $this->adjust_createEntry($record);
				$rc .= '</li>' . PHP_EOL;
			}

			$rc .= '</ul>';

			$rc .= new system_nodes_adjust_WdPager
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
			$rc .= '<p class="no-response">';

			$rc .= $search
				? t('Aucun objet ne correspond aux termes de recherche spécifiés (%search)', array('%search' => $search))
				: t('Aucune entrée dans le module %module', array('%module' => $this->id));

			$rc .= '</p>';
		}

		$rc .= '</div>';

		return $rc;
	}

	protected function adjust_loadRange(array $conditions, array $conditions_args, $limit, $page)
	{
		$arq = $this->model->where(implode(' AND ', $conditions), $conditions_args);

		$count = $arq->count;
		$entries = array();

		if ($count)
		{
			$entries = $arq->limit($page * $limit, $limit)->order('title')->all;
		}

		return array($entries, $count);
	}

	public function adjust_createEntry($record)
	{
		$rc  = '<input class="nid" type="hidden" value="' . $record->nid . '" />';

		if ($record->title)
		{
			$title = $record->title ? wd_shorten($record->title, 32, .75, $shortened) : '<';

			$rc .= '<span class="title"' . ($shortened ? ' title="' . wd_entities($record->title) . '"' : '') . '>' . $title . '</span>';
		}
		else
		{
			$rc .= '<em class="light">Untitled node <span class="small">(' . $this->id . '.' . $record->nid . ')</span></em>';
		}

		return $rc;
	}

	static public function dashboard_now()
	{
		global $core, $document;

		$document->css->add('public/dashboard.css');

		$counts = $core->models['system.nodes']->where('siteid = 0 OR siteid = ?', array($core->working_site_id))->count('constructor');

		if (!$counts)
		{
			return '<p class="nothing">' . t('There is no record yet') . '</p>';
		}

		$by_title = array();

		foreach ($counts as $constructor => $count)
		{
			if (!$constructor)
			{
				wd_log("$count nodes have no constructors !");

				continue;
			}

			if (empty($core->modules[$constructor]))
			{
				continue;
			}

			$title = $core->modules->descriptors[$constructor][WdModule::T_TITLE];

			$by_title[$title] = array
			(
				$constructor,
				$count
			);
		}

		ksort($by_title);

		$types = array
		(
			'contents' => array(),
			'resources' => array()/*,
			'organize' => array(),
			'feedback' => array()*/
		);

		foreach ($by_title as $title => $node)
		{
			list($constructor, $count) = $node;

			$url = '/admin/' . $constructor;

			$cell = '<td class="count">' . $count . '</td>' .
				'<td class="constructor"><a href="' . $url . '">' . $title . '</a></td>';

			if (preg_match('#resources.*#', $constructor))
			{
				$types['resources'][] = $cell;
			}
			else if (preg_match('#organize.*#', $constructor))
			{
				continue;

				$types['organize'][] = $cell;
			}
			else if (preg_match('#feedback.*#', $constructor))
			{
				continue;

				$types['feedback'][] = $cell;
			}
			else
			{
				$types['contents'][] = $cell;
			}
		}

		$rows = '';
		$rows_max = 0;

		foreach ($types as $rows)
		{
			$rows_max = max($rows_max, count($rows));
		}

		$label_contents = t('module_category.title.contents');
		$label_resources = t('module_category.title.resources');

		$rc = <<<EOT
<table>
	<thead>
		<tr>
			<th>&nbsp;</th><th>$label_contents</th>
			<th>&nbsp;</th><th>$label_resources</th>
		</tr>
	</thead>
	<tbody>
EOT;

		for ($i = 0 ; $i < $rows_max ; $i++)
		{
			$rc .= '<tr>';

			foreach ($types as $rows)
			{
				$rc .= isset($rows[$i]) ? $rows[$i] : '<td colspan="2">&nbsp;</td>';
			}

			$rc .= '</tr>';
		}

		$rc .= <<<EOT
</tbody>
</table>
EOT;

		return $rc;
	}

	static public function dashboard_user_modified()
	{
		global $core, $document;

		$document->css->add('public/dashboard.css');

		$model = $core->models['system.nodes'];

		$entries = $model
			->where('uid = ? AND (siteid = 0 OR siteid = ?)', array($core->user_id, $core->working_site_id))
			->order('modified desc')
			->limit(10)
			->all();

		if (!$entries)
		{
			return '<p class="nothing">' . t("You don't have created records yet") . '</p>';
		}

		$rc = '<table>';

		foreach ($entries as $record)
		{
			$date = wd_date_period($record->modified);
			$title = wd_entities($record->title);
			$title = wd_shorten($title, 48);

			$rc .= <<<EOT
	<tr>
	<td class="date light">$date</td>
	<td class="title"><a href="/admin/{$record->constructor}/{$record->nid}/edit">{$title}</a></td>
	</tr>
EOT;
		}

		$rc .= '</table>';

		return $rc;
	}

	protected function provide_view_view(WdActiveRecordQuery $query, WdPatron $patron)
	{
		global $core, $page;

		$record = $query->one;

		if (!$record)
		{
			throw new WdHTTPException('The requested record was not found.', array(), 404);
		}
		else if (!$record->is_online)
		{
			if (!$core->user->has_permission(WdModule::PERMISSION_ACCESS, $record->constructor))
			{
				throw new WdHTTPException('The requested record requires authentication.', array(), 401);
			}

			$record->title .= ' ✎';
		}

		$page->title = $record->title;

		return $record;
	}

	protected function provide_view_alter_query($name, $query)
	{
		global $page;

		$site = $page->site;

		$query->where
		(
			'constructor = ? AND (siteid = 0 OR siteid = ?) AND (language = "" OR language = ?)',

			$this->id, $site->siteid, $site->language
		);

		if ($name != 'view')
		{
			$query->where('is_online = 1');
		}

		$url_variables = $page->url_variables;

		if (isset($url_variables['nid']))
		{
			$query->where('nid = ?', $url_variables['nid']);
		}
		else if (isset($url_variables['slug']))
		{
			$query->where('slug = ?', $url_variables['slug']);
		}
		else
		{
			$query->where('is_online = 1');
		}

		return parent::provide_view_alter_query($name, $query);
	}

	protected function provide_view_alter_query_view($query)
	{
		return $query->limit(1);
	}

	protected function provide_view_list(WdActiveRecordQuery $query, WdPatron $patron)
	{
		global $core;

		$count = $query->count;

		$limit = $core->site->metas->get("$this->flat_id.limits.list", 10);
		$position = isset($_GET['page']) ? $_GET['page'] : 0;

		if ($limit)
		{
			$query->limit($position * $limit, $limit);
		}

		$patron->context['self']['range'] = array
		(
			'count' => $count,
			'page' => $position,
			'limit' => $limit
		);

		return $query->all;
	}
}

class system_nodes_adjust_WdPager extends WdPager
{
	protected function getURL($n)
	{
		return '#' . $n;
	}
}