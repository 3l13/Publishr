<?php

/**
 * This file is part of the Publishr software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2011 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class resources_files_attached_WdHooks
{
	/**
	 * Returns the attachments of the given node.
	 *
	 * @param system_nodes_WdActiveRecord $ar
	 * @return array|null An array of attachments or null if there is none.
	 */
	static public function get_attachments(system_nodes_WdActiveRecord $ar)
	{
		global $core;

		$nodes = $core->models['resources.files.attached']
		->find_by_nodeid($ar->nid)
		->joins('INNER JOIN {prefix}system_nodes ON(nid = fileid)')
		->select('fileid, attached.title, constructor')
		->where('is_online = 1')
		->order('weight')->all(PDO::FETCH_OBJ);

		if (!$nodes)
		{
			return;
		}

		$nodes_by_id = array();
		$ids_by_constructor = array();

		foreach ($nodes as $node)
		{
			$nid = $node->fileid;
			$nodes_by_id[$nid] = $node;
			$ids_by_constructor[$node->constructor][] = $nid;
		}

		foreach ($ids_by_constructor as $constructor => $ids)
		{
			$records = $core->models[$constructor]->find($ids);

			foreach ($records as $record)
			{
				$nid = $record->nid;
				$node = $nodes_by_id[$nid];
				$nodes_by_id[$nid] = $record;

				$record->label = $node->title ? $node->title : $record->title;
			}
		}

		return array_values($nodes_by_id);
	}

	/**
	 * Alters the "edit" block to adds the "attachments" group with a WdAttachmentsElement used to
	 * manage node attachments.
	 *
	 * @param WdEvent $event
	 */
	static public function event_alter_block_edit(WdEvent $event)
	{
		global $core;

		$target = $event->target;

		if ($target instanceof resources_files_WdModule)
		{
			return;
		}

		$scope = $core->registry['resources_files_attached.scope'];

		if (!$scope)
		{
			return;
		}

		$scope = explode(',', $scope);

		if (!in_array($target->flat_id, $scope))
		{
			return;
		}

		$event->tags = wd_array_merge_recursive
		(
			$event->tags, array
			(
				WdElement::T_GROUPS => array
				(
					'attachments' => array
					(
						'title' => '.attachments',
						'class' => 'form-section flat'
					)
				),

				WdElement::T_CHILDREN => array
				(
					new WdAttachmentsElement
					(
						array
						(
							WdElement::T_GROUP => 'attachments',

							WdAttachmentsElement::T_NODEID => $event->key,
							WdAttachmentsElement::T_HARD_BOND => true
						)
					)
				)
			)
		);
	}

	/**
	 * The `wdp:node:attachments` markup can be used to render a node attachments.
	 *
	 * There is actually two modes for rendering the attachments, depending on their number:
	 *
	 * - No attachment: nothing is rendered.
	 *
	 * - One attachment:
	 *
	 *     <div class="node-attachments">
	 *     <p><a href="#{@url('download')}">#{t:Download attachment}</a>
	 *     <span class="metas">(#{@extension} – #{@size.format_size()}</span></p>
	 *     </div>
	 *
	 * - More than on attachment:
	 *
	 *     <div class="node-attachments">
	 *     <h5>#{t:Attached files}</h5>
	 *     <ul>
	 *     <wdp:foreach>
	 *     <p><a href="#{@url('download')}">#{@label}</a>
	 *     <span class="metas">(#{@extension} – #{@size.format_size()}</span></p>
	 *     </wdp:foreach>
	 *     </ul>
	 *     </div>
	 *
	 * One can use I18n scope to translate "Download attachment" or "Attachments" to module
	 * specific translations such as "Download press release" or "Press release attachments".
	 *
 	 * Attachments are created using the "resources.files.attached" module.
	 *
	 * @param array $args
	 * @param WdPatron $patron
	 * @param string|null $template
	 * @return string|null The rendered attached file(s), or null if no files were attached.
	 */
	static public function markup_node_attachments(array $args=array(), WdPatron $patron, $template)
	{
		$target = $patron->context['this'];
		$files = $target->attachments;

		if (!$files)
		{
			return;
		}

		$rc = '<div class="node-attachments">';

		if (count($files) == 1)
		{
			$file = $files[0];

			$rc .= '<p>' . self::make_link($file, t('Download attachment')) . '</p>';
		}
		else
		{
			$rc .= '<h5>' . t('Attachments') . '</h5>';
			$rc .= '<ul>';

			foreach ($files as $file)
			{
				$rc .= '<li>' . self::make_link($file) . '</li>';
			}

			$rc .= '</ul>';
		}

		return $rc . '</div>';
	}

	static private function make_link(resources_files_WdActiveRecord $file, $label=null)
	{
		if (!$label)
		{
			$label = $file->label;
		}

		return '<a href="' . wd_entities($file->url('download')) . '">' . wd_entities($label) . '</a> <span class="metas">(' . $file->extension . ' &ndash; ' . wd_format_size($file->size) . ')</span>';
	}
}