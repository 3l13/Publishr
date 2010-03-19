<?php

class user_users_WdMarkups
{
	static protected function model($name='user.users')
	{
		return parent::model($name);
	}

	public static function connect(WdHook $hook, WdPatron $patron, $template)
	{
		global $user;

		if (!$user->isGuest())
		{
			$form = new Wd2CForm
			(
				array
				(
					WdForm::T_HIDDENS => array
					(
						WdOperation::DESTINATION => 'user.users',
						WdOperation::NAME => user_users_WdModule::OPERATION_DISCONNECT
					),

					WdElement::T_CHILDREN => array
					(
						new WdElement
						(
							WdElement::E_SUBMIT, array
							(
								WdElement::T_INNER_HTML => t('Deconnection'),
								'class' => 'disconnect'
							)
						)
					),

					'name' => 'disconnect'
				),

				'div'
			);

			$rc = '<p>';
			$rc .= t
			(
				'Welcome back :username&nbsp;!
				You can use the :publisher to manage your articles and images.', array
				(
					':username' => $user->username,
					':publisher' => '<a href="/admin">WdPublisher</a>'
				)
			);
			$rc .= '</p>';
			$rc .= $form;

			return $rc;
		}
		else
		{
			$form = new Wd2CForm
			(
				array
				(
					WdForm::T_HIDDENS => array
					(
						WdOperation::DESTINATION => 'user.users',
						WdOperation::NAME => user_users_WdModule::OPERATION_CONNECT
					),

					WdElement::T_CHILDREN => array
					(
						User::USERNAME => new WdElement
						(
							WdElement::E_TEXT, array
							(
								WdForm::T_LABEL => 'Username',
								WdElement::T_MANDATORY => true
							)
						),

						User::PASSWORD => new WdElement
						(
							WdElement::E_PASSWORD, array
							(
								WdForm::T_LABEL => 'Password',
								WdElement::T_MANDATORY => true
							)
						),

						new WdElement
						(
							WdElement::E_SUBMIT, array
							(
								WdElement::T_INNER_HTML => t('Connect'),
								'class' => 'connect'
							)
						)
					),

					'class' => 'login',
					'name' => 'connect'
				),

				'div'
			);

			return $form;
		}
	}

	static public function user(WdHook $hook, WdPatrong $patron, $template)
	{
		$entry = self::model()->load($hook->params['select']);

		if (!$entry)
		{
			return;
		}

		return $patron->publish($template, $entry);
	}
}