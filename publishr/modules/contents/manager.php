<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class contents_WdManager extends system_nodes_WdManager
{
	public function __construct($module, array $tags=array())
	{
		parent::__construct
		(
			$module, $tags + array
			(
				self::T_ORDER_BY => array('date', 'desc')
			)
		);

		global $document;

		$document->css->add('public/manage.css');
		$document->js->add('public/manage.js');
	}

	protected function columns()
	{
		return parent::columns() + array
		(
			'date' => array
			(
				'class' => 'date',
				self::COLUMN_SORT => self::ORDER_DESC,
				'default_order' => -1
			),

			'is_home_excluded' => array
			(
				'label' => null,
				'orderable' => false
			)
		);
	}

	/**
	 * Updates filters with the `is_home_excluded` filter.
	 *
	 * @see system_nodes_WdManager::update_filters()
	 */
	protected function update_filters(array $filters, array $modifiers)
	{
		$filters = parent::update_filters($filters, $modifiers);

		if (isset($modifiers['is_home_excluded']))
		{
			$value = $modifiers['is_home_excluded'];

			if ($value === '' || $value === null)
			{
				unset($filters['is_home_excluded']);
			}
			else
			{
				$filters['is_home_excluded'] = !empty($value);
			}
		}

		return $filters;
	}

	/**
	 * Alters query with the `is_home_excluded` filter.
	 *
	 * @see system_nodes_WdManager::alter_query()
	 */
	protected function alter_query(WdActiveRecordQuery $query, array $filters)
	{
		if (isset($filters['is_home_excluded']))
		{
			$query->where('is_home_excluded = ?', $filters['is_home_excluded']);
		}

		return parent::alter_query($query, $filters);
	}

	/**
	 * Returns options for the `is_home_excluded` header cell.
	 *
	 * @param array $options
	 * @param string $id
	 *
	 * @return array
	 */
	protected function extend_column_is_home_excluded(array $options, $id)
	{
		return array
		(
			'filters' => array
			(
				'options' => array
				(
					'=1' => "Exclus de l'accueil",
					'=0' => "Inclus à l'accueil"
				)
			),

			'sortable' => false
		)

		+ parent::extend_column($options, $id);
	}

	/**
	 * Renders a cell of the `is_home_excluded` column.
	 *
	 * @param WdActiveRecord $record
	 * @param string $property
	 */
	protected function render_cell_is_home_excluded(WdActiveRecord $record, $property)
	{
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
							'value' => $record->nid,
							'checked' => ($record->$property != 0),
							'class' => 'is_home_excluded'
						)
					)
				),

				'title' => "Inclure ou exclure l'entrée de la page d'accueil",
				'class' => 'checkbox-wrapper home'
			)
		);
	}
}