<?php

/*
 * This file is part of the Publishr package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class user_members_WdActiveRecord extends user_users_WdActiveRecord
{
	public $gender;

	public $number_work;
	public $number_home;
	public $number_fax;
	public $number_pager;
	public $number_mobile;

	public $street;
	public $street_complement;
	public $city;
	public $state;
	public $postalcode;
	public $country;
	public $webpage;

	public $birthday;

	public $position;
	public $service;
	public $company;
	public $company_street;
	public $company_street_complement;
	public $company_city;
	public $company_state;
	public $company_postalcode;
	public $company_country;
	public $company_webpage;

	public $misc1;
	public $misc2;
	public $misc3;
	public $misc4;
	public $notes;

	public $photo;

	protected function model($name='user.members')
	{
		return parent::model($name);
	}

	// TODO-20110108: thumbnailer should support the 'thumbnail' method for members as well,
	// or maybe the "resources.images" module should have a special support for users.

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
			'thumbnailer/get', array
			(
				'src' => $this->photo,
				'version' => $version
			)
		);
	}
}