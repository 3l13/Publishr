<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class system_nodes_WdModule extends WdPModule
{
	const OPERATION_ONLINE = 'online';
	const OPERATION_OFFLINE = 'offline';
	const OPERATION_ADJUST_ADD = 'adjustAdd';
	const OPERATION_LOCK = 'lock';
	const OPERATION_UNLOCK = 'unlock';

	public function __construct($tags)
	{
		#
		# In order to identify which module created a node, we need to extend the primary model
		# by defining the T_CONSTRUCTOR tag. The tag is defined by the system.nodes primary model.
		#

		$tags[self::T_MODELS]['primary'] += array
		(
			system_nodes_WdModel::T_CONSTRUCTOR => $tags[self::T_ID]
		);

		//wd_log('module: \1, tags \2', array($tags[self::T_ID], $tags));

		parent::__construct($tags);
	}

	protected function getOperationsAccessControls()
	{
		return array
		(
			self::OPERATION_ONLINE => array
			(
				self::CONTROL_PERMISSION => PERMISSION_MAINTAIN,
				self::CONTROL_OWNERSHIP => true,
				self::CONTROL_VALIDATOR => false
			),

			self::OPERATION_OFFLINE => array
			(
				self::CONTROL_PERMISSION => PERMISSION_MAINTAIN,
				self::CONTROL_OWNERSHIP => true,
				self::CONTROL_VALIDATOR => false
			),

			self::OPERATION_LOCK => array
			(
				self::CONTROL_PERMISSION => PERMISSION_MAINTAIN,
				self::CONTROL_OWNERSHIP => true,
				self::CONTROL_VALIDATOR => false
			),

			self::OPERATION_UNLOCK => array
			(
				self::CONTROL_PERMISSION => PERMISSION_MAINTAIN,
				self::CONTROL_OWNERSHIP => true,
				self::CONTROL_VALIDATOR => false
			)
		)

		+ parent::getOperationsAccessControls();
	}

	// FIXME-20100216: since entries are reloaded using their constructor, this might lead to some
	// problems if the a wrong nid is used with a module which is not the constructor.

	protected function operation_save(WdOperation $operation)
	{
		global $app;

		$params = &$operation->params;

		$params[Node::CONSTRUCTOR] = $this->id;

		if (!$app->user->has_permission(PERMISSION_ADMINISTER, $this))
		{
			unset($params[Node::UID]);

			$params[Node::SITEID] = $app->site->siteid;
		}

		#
		# online
		#

		$operation->handle_booleans(array('is_online'));

		$rc = parent::operation_save($operation);

		if ($rc)
		{
			$key = $rc['key'];
			$entry = $this->model()->load($key);

			if ($rc['mode'] == 'update')
			{
				wd_log_done('The entry %title has been saved in %module.', array('%title' => wd_shorten($entry->title), '%module' => $this->id), 'save');
			}
			else
			{
				wd_log_done('The entry %title has been created in %module.', array('%title' => wd_shorten($entry->title), '%module' => $this->id), 'save');
			}

			#
			# metas
			#

			if (isset($params['metas']))
			{
				$metas = $entry->metas;

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
		}

		return $rc;
	}

	protected function operation_query_online(WdOperation $operation)
	{
		$entries = $operation->params['entries'];
		$count = count($entries);

		return array
		(
			'title' => t('@operation.online.title'),
			'message' => t($count == 1 ? '@operation.online.confirm' : '@operation.online.confirmN', array(':count' => count($entries))),
			'confirm' => array(t('@operation.online.dont'), t('@operation.online.do')),
			'params' => array
			(
				'entries' => $entries
			)
		);
	}

	protected function operation_online(WdOperation $operation)
	{
		$entry = $operation->entry;
		$entry->is_online = true;
		$entry->save();

		wd_log_done('!title is now online', array('!title' => $entry->title));

		return true;
	}

	protected function operation_query_offline(WdOperation $operation)
	{
		$entries = $operation->params['entries'];
		$count = count($entries);

		return array
		(
			'title' => t('@operation.offline.title'),
			'message' => t($count == 1 ? '@operation.offline.confirm' : '@operation.offline.confirmN', array(':count' => count($entries))),
			'confirm' => array(t('@operation.offline.dont'), t('@operation.offline.do')),
			'params' => array
			(
				'entries' => $entries
			)
		);
	}

	protected function operation_offline(WdOperation $operation)
	{
		$entry = $operation->entry;
		$entry->is_online = false;
		$entry->save();

		wd_log_done('!title is now offline', array('!title' => $entry->title));

		return true;
	}

	protected function operation_lock(WdOperation $operation)
	{
		$entry = $operation->entry;

		//wd_log('\1: locked', array($entry->title));

		return $entry->lock();
	}

	protected function operation_unlock(WdOperation $operation)
	{
		$entry = $operation->entry;

		//wd_log('\1: unlocked', array($entry->title));

		return $entry->unlock();
	}

	public function getBlock($name)
	{
		global $core, $app;

		$args = func_get_args();

		if ($name == 'edit' && !$app->user->is_guest())
		{
			if (isset($args[1]))
			{
				$nid = $args[1];
				$entry = $this->model()->load($nid);

				if ($entry)
				{
					$locked = $entry->lock();

					if (!$locked)
					{
						global $core;

						$luser = $core->getModule('user.users')->model()->load($entry->metas['lock.uid']);
						$url = $_SERVER['REQUEST_URI'];

						$time = round((strtotime($entry->metas['lock.until']) - time()) / 60);
						$message = $time ? "Le verrou devrait disparaitre dans $time minutes." : "Le verrou devrait disparaitre dans moins d'une minutes.";

						return <<<EOT
<div class="group">
<h3>Édition impossible</h3>
<p>Impossible d'éditer l'entrée <em>$entry->title</em> parce qu'elle est en cours d'édition par <em>$luser->name</em> <span class="small">($luser->username)</span>.</p>
<form method="get">
<input type="hidden" name="retry" value="1" />
<button class="continue">Réessayer</button> <span class="small light">$message</span>
</form>
</div>
EOT;
					}
				}
			}
		}

		return call_user_func_array((PHP_MAJOR_VERSION > 5 || (PHP_MAJOR_VERSION == 5 && PHP_MINOR_VERSION > 2)) ? 'parent::' . __FUNCTION__ : array($this, 'parent::' . __FUNCTION__), $args);
	}

	protected function block_edit(array $properties, $permission)
	{
		global $core, $app, $document;

		$document->js->add('public/edit.js');

		$values = array();

		#
		# metas
		#

		if ($properties[Node::NID])
		{
			$node = $this->model()->load($properties[Node::NID]);

			$values = array
			(
				'metas' => $node->metas['all']
			);
		}

		#
		#
		#

		$uid_el = null;
		$siteid_el = null;

		if ($app->user->has_permission(PERMISSION_ADMINISTER, $this))
		{
			$uid_el = new WdElement
			(
				'select', array
				(
					WdElement::T_LABEL => 'User',
					WdElement::T_LABEL_POSITION => 'before',

					WdElement::T_OPTIONS => array(null => '') + $core->models['user.users']->select
					(
						array('uid', 'username'), 'ORDER BY username'
					)
					->fetchPairs(),

					WdElement::T_MANDATORY => true,
					WdElement::T_DEFAULT => $app->user->uid,
					WdElement::T_GROUP => 'admin',
					WdElement::T_DESCRIPTION => "Parce que vous avez des droits d'administration
					sur le module, vous pouvez choisir l'utilisateur propriétaire de cette entrée."
				)
			);

			#
			#
			#

			// TODO-20100906: this should be added by the site.sites modules using the alter event.

			$siteid_el = new WdElement
			(
				'select', array
				(
					WdElement::T_LABEL => 'Site',
					WdElement::T_LABEL_POSITION => 'before',
					WdElement::T_OPTIONS => array
					(
						null => ''
					)

					+ $core->models['site.sites']->select
					(
						array('siteid', 'title'), 'ORDER BY title'
					)
					->fetchPairs(),

					WdElement::T_DEFAULT => $app->working_site_id,
					WdElement::T_GROUP => 'admin'
				)
			);
		}

		return array
		(
			WdForm::T_VALUES => $values,

			WdElement::T_GROUPS => array
			(
				'primary' => array
				(
					'title' => 'Général',
					'class' => 'form-section flat'
				),

				'node' => array
				(
					'weight' => -10,
					'title' => 'do not use node section anymore!',
					'class' => 'form-section flat'
				),

				'publish' => array
				(
					'title' => 'Publication',
					'weight' => 300
				),

				'online' => array
				(
					'title' => "Visibilité",
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
						WdForm::T_LABEL => 'Titre',
						WdElement::T_MANDATORY => true,
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
						WdElement::T_LABEL => 'En ligne',
						WdElement::T_GROUP => 'online',
						WdElement::T_DESCRIPTION => "Seules les entrées <em>en ligne</em> sont
						accessibles aux visiteurs."
					)
				)
			),
		);
	}

	protected function block_welcome()
	{
		global $app, $core;

		require_once WDCORE_ROOT . 'wddate.php';

		$user = $app->user;

		$where = $user->is_admin() ? '' : 'WHERE `uid` = ' . (int) $user->uid;

		$modified = $this->model()->loadRange
		(
			0, 10, $where . ' ORDER BY `' . Node::MODIFIED . '` DESC, `' . Node::TITLE . '` ASC'
		)
		->fetchAll();

		$modified = WdArray::group_by($modified, Node::CONSTRUCTOR);

		$rc = '';

		foreach ($modified as $module_id => $group)
		{
			$title = isset($core->descriptors[$module_id][WdModule::T_TITLE]) ? $core->descriptors[$module_id][WdModule::T_TITLE] : $module_id;

			$rc .= '<h3>' . t($title) . '</h3>';

			$rc .= '<ul>';

			foreach ($group as $node)
			{
				$rc .= '<li>';
				$rc .= '<span class="date">' . wd_ftime($node->modified, '%Y-%m-%d %H:%M') . '</span> ';
				$rc .= '<a href="' . WdRoute::encode('/' . $node->constructor . '/' . $node->nid . '/edit') . '">';
				$rc .= $node->title ? $node->title : '#' . $node->nid;
				$rc .= '</a></li>';
			}

			$rc .= '</ul>';
		}

		return '<h2>Derniers éléments</h2>' . $rc;
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

	protected function block_adjust($params)
	{
		return new WdAdjustNodeElement
		(
			array
			(
				WdAdjustNodeElement::T_SCOPE => $this->id,

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

			foreach ($entries as $entry)
			{
				$rc .= ($entry->nid == $selected) ? '<li class="selected">' : '<li>';
				$rc .= $this->adjust_createEntry($entry);
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

	protected function adjust_loadRange(array $where, array $values, $limit, $page)
	{
		$model = $this->model();
		$where = $where ? ' WHERE ' . implode(' AND ', $where) : '';
		$count = $model->count(null, null, $where, $values);
		$entries = array();

		if ($count)
		{
			$entries = $model->loadRange
			(
				$page * $limit, $limit, $where . ' ORDER BY title', $values
			);
		}

		return array($entries, $count);
	}

	public function adjust_createEntry($entry)
	{
		$rc  = '<input class="nid" type="hidden" value="' . $entry->nid . '" />';

		if ($entry->title)
		{
			$title = $entry->title ? wd_shorten($entry->title, 32, .75, $shortened) : '<';

			$rc .= '<span class="title"' . ($shortened ? ' title="' . wd_entities($entry->title) . '"' : '') . '>' . $title . '</span>';
		}
		else
		{
			$rc .= '<em class="light">Untitled node <span class="small">(' . $this->id . '.' . $entry->nid . ')</span></em>';
		}

		return $rc;
	}

	/*
	 * DIRTY-20100707: now that we have restful operations, this is handled by the
	 * WdAdjustNodesList element.
	 *
	protected function validate_operation_adjustAdd(WdOperation $operation)
	{
		$params = &$operation->params;

		if (empty($params['nid']))
		{
			return false;
		}

		global $core;

		$nid = $params['nid'];
		$node = $this->model()->load($nid);

		if (!$node)
		{
			wd_log_error('Unknown entry: %nid', array('%nid' => $nid));

			return false;
		}

		$operation->entry = $node;

		return true;
	}

	protected function operation_adjustAdd(WdOperation $operation)
	{
		return '<span class="handle">↕</span> ' . $this->adjust_createEntry($operation->entry);
	}
	*/




	static public function dashboard_now()
	{
		global $core, $app, $document;

		$document->css->add('public/dashboard.css');

		$model = $core->models['system.nodes'];
		$counts = $model->count('constructor', 'asc', 'WHERE (siteid = 0 || siteid = ?)', array($app->working_site_id));

		if (!$counts)
		{
			return '<p class="nothing">Il n\'y a pas encore d\'entrées</p>';
		}

		$by_title = array();

		foreach ($counts as $constructor => $count)
		{
			if (!$core->hasModule($constructor))
			{
				continue;
			}

			$title = $core->descriptors[$constructor][WdModule::T_TITLE];

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

			$url = '/admin/index.php/' . $constructor;

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

		$rc = <<<EOT
<table>
	<thead>
		<tr>
			<th>&nbsp;</th><th>Contenus</th>
			<th>&nbsp;</th><th>Resources</th>
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
		global $core, $app, $document;

		require_once WDCORE_ROOT . 'wddate.php';

		$document->css->add('public/dashboard.css');

		$model = $core->models['system.nodes'];

		$entries = $model->loadRange
		(
			0, 10, 'WHERE uid = ? AND (siteid = 0 OR siteid = ?) ORDER BY modified DESC', array
			(
				$app->user_id, $app->working_site_id
			)
		)
		->fetchAll();

		if (!$entries)
		{
			return '<p class="nothing">Vous n\'avez pas encore créé d\'entrées</p>';
		}

		$rc = '<table>';

		foreach ($entries as $entry)
		{
			$date = wd_date_period($entry->modified);
			$title = wd_entities($entry->title);

			$rc .= <<<EOT
	<tr>
	<td class="date light">$date</td>
	<td class="title"><a href="/admin/index.php/{$entry->constructor}/{$entry->nid}/edit">{$title}</a></td>
	</tr>
EOT;
	}

	$rc .= '</table>';

	return $rc;
}
}

class system_nodes_adjust_WdPager extends WdPager
{
	protected function getURL($n)
	{
		return '#' . $n;
	}
}