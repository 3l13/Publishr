<?php

class system_nodes_WdModule extends WdPModule
{
	const OPERATION_ONLINE = 'online';
	const OPERATION_OFFLINE = 'offline';
	const OPERATION_ADJUST_ADD = 'adjustAdd';

	public function __construct($tags)
	{
		#
		# In order to identify which module created a node, we need to extend the primary model
		# by defining the T_CONSTRUCTOR tag. The tag is defined by the system.nodes primary model.
		#

		if (isset($tags[self::T_MODELS]['primary']))
		{
			$tags[self::T_MODELS]['primary'] += array
			(
				system_nodes_WdModel::T_CONSTRUCTOR => $tags[self::T_ID]
			);
		}

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
			)
		)

		+ parent::getOperationsAccessControls();
	}

	// FIXME-20100216: since entries are reloaded using their constructor, this might lead to some
	// problems if the a wrong nid is used with a module which is not the constructor.

	protected function operation_save(WdOperation $operation)
	{
		$params = &$operation->params;

		global $user;

		if (!$user->hasPermission(PERMISSION_ADMINISTER, $this))
		{
			unset($params[Node::UID]);
		}

		#
		# online
		#

		$operation->handleBooleans(array('is_online'));

		$rc = parent::operation_save($operation);

		if ($rc)
		{
			$key = $rc['key'];
			$entry = $this->model()->load($key);

			if ($rc['mode'] == 'update')
			{
				wd_log_done('The entry %title has been saved in %module.', array('%title' => $entry->title, '%module' => $this->id), 'save');
			}
			else
			{
				wd_log_done('The entry %title has been created in %module.', array('%title' => $entry->title, '%module' => $this->id), 'save');
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

	protected function block_edit(array $properties, $permission)
	{
		global $user;

		$uid_el = null;

		if ($user->hasPermission(PERMISSION_ADMINISTER, $this))
		{
			global $core;

			$uid_el = new WdElement
			(
				'select', array
				(
					WdForm::T_LABEL => 'User',

					WdElement::T_OPTIONS => array(null => '') + $core->getModule('user.users')->model()->select
					(
						array('uid', 'username'), 'ORDER BY username'
					)
					->fetchPairs(),

					WdElement::T_MANDATORY => true,
					WdElement::T_DEFAULT => $user->uid,
					WdElement::T_DESCRIPTION => "Parce que vous avez des droits d'administration
					sur le module, vous pouvez choisir l'utilisateur propriétaire de cette entrée.",
					WdElement::T_WEIGHT => 100
				)
			);
		}

		return array
		(
			WdElement::T_GROUPS => array
			(
				'node' => array
				(
					'weight' => -100,
				),

				'publish' => array
				(
					'title' => 'Publication',
					'weight' => 300
				),

				'online' => array
				(
					'title' => 'Visibilité de l\'entrée',
					'weight' => 400
				)
			),

			WdElement::T_CHILDREN => array
			(
				/*
				Node::TITLE => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => 'Titre',
						WdElement::T_GROUP => 'node',
						WdElement::T_MANDATORY => true
					)
				),
				*/

				Node::TITLE => new WdTitleSlugComboElement
				(
					array
					(
						WdForm::T_LABEL => 'Titre',
						WdElement::T_MANDATORY => true,
						WdElement::T_GROUP => 'node',
						WdTitleSlugComboElement::T_SLUG_NAME => 'slug'
					)
				),

				Node::UID => $uid_el,

				Node::IS_ONLINE => new WdElement
				(
					WdElement::E_CHECKBOX, array
					(
						WdElement::T_LABEL => 'En ligne',
						WdElement::T_GROUP => 'online',
						WdElement::T_DESCRIPTION => "Mettre une entrée <em>en ligne</em> permet de
						la rendre accessible aux visiteurs."
					)
				)
			),
		);
	}

	protected function block_welcome()
	{
		global $user, $core;

		require_once WDCORE_ROOT . 'wddate.php';

		$where = $user->isAdmin() ? '' : 'WHERE `uid` = ' . (int) $user->uid;

		$modified = $this->model()->loadRange
		(
			0, 10, $where . ' ORDER BY `' . Node::MODIFIED . '` DESC, `' . Node::TITLE . '` ASC'
		)
		->fetchAll();

		$modified = WdArray::groupBy(Node::CONSTRUCTOR, $modified);

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
					'title', 'url', 'uid', 'constructor', 'created', 'modified', 'is_online'
				)
			)
		);
	}

	protected function block_adjustResults(array $options=array())
	{
		$options += array
		(
			'page' => 0,
			'limit' => 10,
			'search' => null
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

		list($entries, $count) = $this->adjust_loadRange($where, $values, $limit, $page);

		$rc = '<div class="results">';

		if ($count)
		{
			$rc .= '<ul>';

			foreach ($entries as $entry)
			{
				$rc .= '<li>';
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

		$title = wd_shorten($entry->title, 32, $shortened);

		$rc .= $shortened ? '<span title="' . wd_entities($entry->title) . '">' . $title . '</span>' : $title;

		return $rc;
	}

	/*
	public function adjust_createEntry($node)
	{
		$rc  = '<input type="hidden" name="nodes[]" value="' . $node->nid . '" />';
		$rc .= wd_entities($node->title);

		return $rc;
	}
	*/

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
		return $this->adjust_createEntry($operation->entry);
	}
}

class system_nodes_adjust_WdPager extends WdPager
{
	protected function getURL($n)
	{
		return '#' . $n;
	}
}