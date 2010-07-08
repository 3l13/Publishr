<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

class user_members_WdActiveRecord extends user_users_WdActiveRecord
{
	protected function model($name='user.members')
	{
		return parent::model($name);
	}

	protected function __get_thumbnail()
	{
		return $this->thumbnail('primary');
	}

	public function thumbnail($version)
	{
		if (!$this->photo)
		{
			return;
		}

		return WdOperation::encode
		(
			'thumbnailer', 'get', array
			(
				'src' => $this->photo,
				'version' => $version
			),

			'r'
		);
	}
}