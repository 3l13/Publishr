<?php

class feedback_comments_WdMarkups extends patron_markups_WdHooks
{
	static protected function model($name="feedback.comments")
	{
		return parent::model($name);
	}

	static public function comments(WdHook $hook, WdPatron $patron, $template)
	{
		extract($hook->params, EXTR_PREFIX_ALL, 'p');

		#
		# build sql query
		#

		$where = array();
		$params = array();

		$node = $hook->params['node'];

		if ($node)
		{
			$where[] = '`nid` = ?';
			$params[] = $node;
		}

		if ($p_noauthor)
		{
			$where[] = '(SELECT uid FROM {prefix}system_nodes WHERE nid = t1.nid) != IFNULL(uid, 0)';
		}

		$query = $where ? ' WHERE ' . implode(' AND ', $where) : '';

		if ($p_by)
		{
			$query .= ' ORDER BY `' . $p_by . '` ' . ($p_order == 'desc' ? 'DESC' : 'ASC');
		}

		if ($p_limit)
		{
			$entries = self::model()->loadRange($p_limit * $p_page, $p_limit, $query, $params);
		}
		else
		{
			$entries = self::model()->loadAll($query, $params);
		}

		$entries = $entries->fetchAll();

		if (!$entries && !$p_parseempty)
		{
			return;
		}

		#
		#
		#

		return $patron->publish($template, $entries);
	}

	static public function form(WdHook $hook, WdPatron $patron, $template)
	{
		if (empty($hook->params['node']))
		{
			$patron->error('The %attribute attribute is mandatory', array('%attribute' => 'node'));

			return;
		}

		$patron->context['self']['ok'] = WdOperation::getResult(WdModule::OPERATION_SAVE);

		#
		# properties
		#

		$values = $_POST;

		#
		# if the user is authenticated, we fill and lock its name and email
		#

		global $user;

		$is_member = !$user->isGuest();

		if ($is_member)
		{
			$values[Comment::AUTHOR] = $user->name;
			$values[Comment::AUTHOR_EMAIL] = $user->email;
		}

		#
		# create form
		#

		$form = new Wd2CForm
		(
			array
			(
				WdForm::T_VALUES => $values,

				WdElement::T_CHILDREN => array
				(
					Comment::AUTHOR => new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'Name',
							WdElement::T_MANDATORY => true,
							'readonly' => $is_member
						)
					),

					Comment::AUTHOR_EMAIL => new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'E-mail',
							WdElement::T_MANDATORY => true,
							WdElement::T_VALIDATOR => array(array('WdForm', 'validate_email')),
							'readonly' => $is_member
						)
					),

					Comment::AUTHOR_URL => new WdElement
					(
						WdElement::E_TEXT, array
						(
							WdForm::T_LABEL => 'URL'
						)
					),

					Comment::CONTENTS => new WdElement
					(
						'textarea', array
						(
							WdElement::T_MANDATORY => true,
							WdElement::T_LABEL_MISSING => 'Contents',
							'rows' => 8
						)
					),

					'Souhaitez-vous être informé par E-Mail de la réponse à votre message&nbsp;?',

					Comment::NOTIFY => new WdElement
					(
						WdElement::E_RADIO_GROUP, array
						(
							WdElement::T_OPTIONS => array
							(
								'yes' => 'Bien sûr !',
								'author' => 'Seulement si c\'est l\'auteur du billet qui répond',
								'no' => 'Pas la peine, je viens tous les jours'
							),

							WdElement::T_DEFAULT => 'no',

							'class' => 'list'
						)
					),

					new WdElement
					(
						WdElement::E_SUBMIT, array
						(
							WdElement::T_INNER_HTML => t('Send'),
							'class' => 'save'
						)
					)
				),

				WdForm::T_HIDDENS => array
				(
					WdOperation::DESTINATION => 'feedback.comments',
					WdOperation::NAME => WdModule::OPERATION_SAVE,

					Comment::NID => $hook->params['node']
				),

				'action' => '#respond',
				'id' => 'respond-form'
			)
		);

		$form->save();

		return $patron->publish($template, $form);
	}
}