<?php

class taxonomy_playlists_WdModule extends system_nodes_WdModule
{
	const OPERATION_SEARCH = 'search';

	protected function getOperationsAccessControls()
	{
		return parent::getOperationsAccessControls() + array
		(
			self::OPERATION_SEARCH => array
			(
				self::CONTROL_AUTHENTICATED => true,
				self::CONTROL_VALIDATOR => false
			)
		);
	}

	protected function operation_save(WdOperation $operation)
	{
		$rc = parent::operation_save($operation);

		if ($rc)
		{
			$nid = $rc['key'];
			$params = &$operation->params;

			$songsModel = $this->model('songs');

			$songsModel->execute
			(
				'DELETE FROM {self} WHERE plid = ?', array
				(
					$nid
				)
			);

			if (isset($params['songs']))
			{
				$songs = $params['songs'];

				$weight = 0;

				foreach ($songs as $songid)
				{
					$songsModel->insert
					(
						array
						(
							'plid' => $nid,
							'songid' => $songid,
							'weight' => $weight++
						)
					);
				}
			}
		}

		return $rc;
	}

	protected function validate_operation_add(WdOperation $operation)
	{
		$params = &$operation->params;

		if (empty($params['song']))
		{
			return false;
		}

		global $core;

		$songid = $params['song'];
		$song = $core->getModule('resources.songs')->model()->load($songid);

		if (!$song)
		{
			wd_log_error('Unknown song: !songid', array('!songid' => $songid));

			return false;
		}

		$operation->song = $song;

		return true;
	}

	protected function operation_add(WdOperation $operation)
	{
		return $this->createSongEntry($operation->song);
	}

	protected function operation_search(WdOperation $operation)
	{
		return $this->createResults($operation->params);
	}

	protected function createResults(array $options=array())
	{
		global $core;

		$options += array
		(
			'page' => 0,
			'limit' => 10,
			'search' => null
		);

		$songsModel = $core->getModule('resources.songs')->model();

		#
		# search
		#

		$page = $options['page'];
		$limit = $options['limit'];

		$where = '';
		$params = array();

		if ($options['search'])
		{
			$concats = array();

			$words = explode(' ', $options['search']);
			$words = array_map('trim', $words);

			foreach ($words as $word)
			{
				$concats[] = 'CONCAT_WS(" ", title, artist, album) LIKE ?';
				$params[] = '%' . $word . '%';
			}

			$where = ' WHERE ' . implode(' AND ', $concats);
		}

		$count = $songsModel->count(null, null, $where, $params);

		$rc = '<div id="song-results">';

		if ($count)
		{
			$entries = $songsModel->loadRange
			(
				$page * $limit, $limit, $where . ' ORDER BY title', $params
			);

			$rc .= '<ul class="song results">';

			foreach ($entries as $entry)
			{
				$rc .= '<li class="song result" id="sr:' . $entry->nid . '">';
				//$result .= ' <button class="play" type="button">›</button>';
				$rc .= '<button class="add" type="button" onclick="pl.add(\'' . $entry->nid . '\')" title="Ajouter à la liste de lecture">+</button>';
				$rc .= ' ' . wd_entities($entry->title);

				if ($entry->artist)
				{
					$rc .= '<span class="small"> &ndash; ' . wd_entities($entry->artist);

					if ($entry->album)
					{
						$rc .= ' / ' . $entry->album;
					}

					$rc .= '</span>';
				}

				$rc .= '</li>' . PHP_EOL;
			}

			$rc .= '</ul>';

			$rc .= new taxonomy_playlist_WdPager
			(
				'div', array
				(
					WdPager::T_COUNT => $count,
					WdPager::T_LIMIT => $limit,
					WdPager::T_POSITION => $page,
					WdPager::T_URLBASE => 'javascript://',

					'class' => 'pager'
				)
			);
		}
		else
		{
			$rc .= '<p class="no-response">' . t('Aucune chanson ne correspond aux termes de recherche spécifiés (%search)', array('%search' => $options['search'])) . '</p>';
		}

		$rc .= '</div>';

		return $rc;
	}

	protected function createSongEntry($song)
	{
		$rc  = '<li class="song sortable">';
		$rc .= '<input type="hidden" name="songs[]" value="' . $song->nid . '" />';
		$rc .= '<button type="button" class="remove" onclick="pl.remove(this)" title="Retirer de la liste de lecture">-</button>';
		$rc .= ' ' . wd_entities($song->title);

		if ($song->artist)
		{
			$rc .= '<span class="small"> &ndash; ' . wd_entities($song->artist) . '</span>';
		}

		$rc .= '</li>';

		return $rc;
	}

	protected function block_manage()
	{
		return new taxonomy_playlists_WdManager
		(
			$this, array
			(
				WdManager::T_COLUMNS_ORDER => array
				(
					'title', 'date', 'uid', 'is_online'
				)
			)
		);
	}

	protected function block_edit(array $properties, $permission)
	{
		global $document;

		$document->addStyleSheet('public/edit.css');
		$document->addJavaScript('public/edit.js');

		global $core;

		$songsModel = $core->getModule('resources.songs')->model();

		#
		# results
		#

		$results = $this->createResults();

		#
		# songs
		#

		$songs = null;

		if ($properties[Node::NID])
		{
			$entries = $songsModel->loadAll
			(
				'INNER JOIN {prefix}taxonomy_playlists_songs ON nid = songid
				WHERE plid = ? ORDER BY weight, title', array
				(
					$properties[Node::NID]
				)
			);

			foreach ($entries as $entry)
			{
				$songs .= $this->createSongEntry($entry);
			}
		}

		return wd_array_merge_recursive
		(
			parent::block_edit($properties, $permission), array
			(
				WdElement::T_CHILDREN => array
				(
					new WdElement
					(
						'div', array
						(
							WdElement::T_CHILDREN => array
							(
								'<div id="song-search">' .
								'<h4>Ajouter des chansons</h4>' .
								'<div class="search">' .
								'<input type="text" class="search" />' .
								'</div>' .
								$results .
								'<div class="element-description">' .
								"Voici la liste de toutes les chansons qui peuvent être utilisées
								pour composer votre liste de lecture. Cliquez sur le bouton
								<em>Ajouter à la liste de lecture</em> pour ajouter des chansons à
								votre liste de lecture. Utilisez le champ de recherche pour filter
								les chansons selon leur titre, artiste et album." .
								' Rendez-vous dans le module <em>resources.songs</em> pour
								<a href="' . WdRoute::encode('/resources.songs') . '">gérer les chansons</a>.</div>',
								'</div>',

								'<div id="playlist">' .
								'<h4>Liste de lecture</h4>' .
								'<ul class="song">' . $songs . '</ul>' .
								'<div class="element-description">Les chansons ci-dessus forment
								votre liste de lecture. Vous pouvez ajouter d\'autres chansons
								depuis le panneau <em>Ajouter des chansons</em>, ou en retirer en
								cliquant sur le bouton <em>Retirer de la liste de lecture</em> situé en
								tête de chaque chanson.</div>' .
								'</div>'
							)
						)
					),

					'description' => new moo_WdEditorElement
					(
						array
						(
							WdForm::T_LABEL => 'Description'
						)
					),

					'date' => new WdDateElement
					(
						array
						(
							WdForm::T_LABEL => 'Date',
							WdElement::T_MANDATORY => true,
							WdElement::T_DEFAULT => date('Y-m-d H:i:s')
						)
					)
				)
			)
		);
	}
}

class taxonomy_playlist_WdPager extends WdPager
{
	protected function getURL($n)
	{
		return 'javascript:search.page(' . $n . ');';
	}
}