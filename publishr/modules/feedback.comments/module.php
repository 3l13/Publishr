<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class feedback_comments_WdModule extends WdPModule
{
	/*
	static $notifies_response = array
	(
		'subject' => 'Notification de réponse au billet : #{@node.title}',
		'template' => 'Bonjour,

Vous recevez cet email parce que vous surveillez le billet "#{@node.title}" sur <nom_du_site>.
Ce billet a reçu une réponse depuis votre dernière visite. Vous pouvez utiliser le lien suivant
pour voir les réponses qui ont été faites :

#{@absolute_url}

Aucune autre notification ne vous sera envoyée.

À bientôt sur <url_du_site>',
		'from' => 'VotreSite <no-reply@votre_site.com>'
	);
	*/

	protected function block_edit(array $properties, $permission)
	{
		return array
		(
			WdElement::T_CHILDREN => array
			(
				Comment::AUTHOR => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => 'Author',
						WdElement::T_REQUIRED => true
					)
				),

				Comment::AUTHOR_EMAIL => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => 'E-mail',
						WdElement::T_REQUIRED => true
					)
				),

				Comment::AUTHOR_URL => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => 'URL'
					)
				),

				new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => 'Adresse IP',

						'value' => $properties[Comment::AUTHOR_IP]
					)
				),

				Comment::CONTENTS => new WdElement
				(
					'textarea', array
					(
						WdForm::T_LABEL => 'Message',
						WdElement::T_REQUIRED => true,

						'rows' => 10
					)
				),

				Comment::NOTIFY => new WdElement
				(
					WdElement::E_RADIO_GROUP, array
					(
						WdForm::T_LABEL => 'Notification',
						WdElement::T_DEFAULT => 'no',
						WdElement::T_REQUIRED => true,
						WdElement::T_OPTIONS => array
						(
							'yes' => 'Bien sûr !',
							'author' => "Seulement si c'est l'auteur du billet qui répond",
							'no' => 'Pas la peine, je viens tous les jours',
							'done' => 'Notification envoyée'
						),

						WdElement::T_DESCRIPTION => (($properties[Comment::NOTIFY] == 'done') ? "Un
						message de notification a été envoyé." : null),

						'class' => 'list'
					)
				),

				Comment::STATUS => new WdElement
				(
					'select', array
					(
						WdForm::T_LABEL => 'Status',
						WdElement::T_REQUIRED => true,
						WdElement::T_OPTIONS => array
						(
							null => '',
							'pending' => 'Pending',
							'approved' => 'Aprouvé',
							'spam' => 'Spam'
						)
					)
				)
			)
		);
	}

	protected function block_manage()
	{
		return new feedback_comments_WdManager
		(
			$this, array
			(
				WdManager::T_COLUMNS_ORDER => array
				(
					'created', 'author', 'score', 'nid'
				),

				WdManager::T_ORDER_BY => array('created', 'desc'),

				feedback_comments_WdManager::T_LIST_SPAM => false
			)
		);
	}

	protected function block_manage_spam()
	{
		return new feedback_comments_WdManager
		(
			$this, array
			(
				WdManager::T_COLUMNS_ORDER => array
				(
					'created', 'author', 'score', 'nid'
				),

				WdManager::T_ORDER_BY => array('created', 'desc'),

				feedback_comments_WdManager::T_LIST_SPAM => true
			)
		);
	}

	protected function block_config()
	{
		global $core;

		// TODO-20101101: move this to operation `config`

		$keywords = $core->registry[$this->flat_id . '.spam.keywords'];
		$keywords = preg_split('#[\s,]+#', $keywords, 0, PREG_SPLIT_NO_EMPTY);

		sort($keywords);

		$keywords = implode(', ', $keywords);

		return array
		(
			WdForm::T_VALUES => array
			(
				"global[$this->flat_id.spam.keywords]" => $keywords
			),

			WdElement::T_GROUPS => array
			(
				'primary' => array
				(
					'title' => 'Général',
					'class' => 'form-section flat'
				),

				'response' => array
				(
					'title' => "Message de notification à l'auteur lors d'une réponse",
					'class' => 'form-section flat'
				),

				'spam' => array
				(
					'title' => 'Paramètres du filtre anti-spam',
					'class' => 'form-section flat',
					'description' => "Les paramètres du filtre anti-spam s'appliquent à tous les
					sites."
				)
			),

			WdElement::T_CHILDREN => array
			(
				"local[$this->flat_id.form_id]" => new WdFormSelectorElement
				(
					'select', array
					(
						WdForm::T_LABEL => 'Formulaire',
						WdElement::T_GROUP => 'primary',
						WdElement::T_REQUIRED => true,
						WdElement::T_DESCRIPTION => "Il s'agit du formulaire à utiliser pour la
						saisie des commentaires."
					)
				),

				"local[$this->flat_id.delay]" => new WdElement
				(
					WdElement::E_TEXT, array
					(
						WdForm::T_LABEL => 'Intervale entre deux commentaires',
						WdElement::T_LABEL => 'minutes',
						WdElement::T_DEFAULT => 3,

						'size' => 3,
						'style' => 'text-align: right'
					)
				),

				"local[$this->flat_id.default_status]" => new WdElement
				(
					'select', array
					(
						WdForm::T_LABEL => 'Status par défaut',
						WdElement::T_OPTIONS => array
						(
							'pending' => 'Pending',
							'approved' => 'Approuvé'
						),
						WdElement::T_DESCRIPTION => "Il s'agit du status par défaut pour les nouveaux
						commentaires."
					)
				),

				"global[$this->flat_id.spam.urls]" => new WdElement
				(
					'textarea', array
					(
						WdForm::T_LABEL => 'URLs',
						WdElement::T_GROUP => 'spam',
						'rows' => 5
					)
				),

				"global[$this->flat_id.spam.keywords]" => new WdElement
				(
					'textarea', array
					(
						WdForm::T_LABEL => 'Mots clés',
						WdElement::T_GROUP => 'spam',
						'rows' => 5
					)
				)
			)
		);
	}

	protected static $spam_score_keywords;
	protected static $forbidden_urls;

	static public function score_spam($contents, $url, $author)
	{
		global $core;

		if (self::$spam_score_keywords === null)
		{
			$keywords = $core->registry['feedback_comments.spam.keywords'];

			if ($keywords)
			{
				$keywords = preg_split('#[\s,]+#', $keywords, 0, PREG_SPLIT_NO_EMPTY);
			}
			else
			{
				$keywords = array();
			}

			self::$spam_score_keywords = $keywords;
		}

		$score = wd_spamScore($contents, $url, $author, self::$spam_score_keywords);

		#
		# additionnal contents restrictions
		#

		$score -= substr_count($contents, '[url=');

		#
		# additionnal author restrictions
		#

		if ($author{0} == '#')
		{
			$score -= 5;
		}

		if (in_array($author, self::$spam_score_keywords))
		{
			$score -= 1;
		}

		#
		# additionnal url restrictions
		#

		if (self::$forbidden_urls === null)
		{
			$forbidden_urls = $core->registry['feedback_comments.spam.urls'];

			if ($forbidden_urls)
			{
				$forbidden_urls = preg_split('#[\s,]+#', $forbidden_urls, 0, PREG_SPLIT_NO_EMPTY);
			}

			self::$forbidden_urls = $forbidden_urls;
		}

		if (self::$forbidden_urls)
		{
			foreach (self::$forbidden_urls as $forbidden)
			{
				if (strpos($contents . $url, $forbidden) !== false)
				{
					$score -= 5;
				}
			}
		}

		return $score;
	}

	protected function provide_view_list(WdActiveRecordQuery $query, WdPatron $patron)
	{
		global $page;

		$target = $page ? $page->node : $page;

		$comments = $this->model->where('nid = ? AND status = "approved"', $target->nid)->order('created')->all;

		$patron->context['self']['count'] = t(':count comments', array(':count' => count($comments)));

		return $comments;
	}
}