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
	public $gender;

	public $number_work;
	public $number_home;
	public $number_fax;
	public $number_pager;
	public $number_mobile;

	public $address;
	public $address_complement;
	public $city;
	public $state;
	public $postalcode;
	public $country;
	public $webpage;

	public $birthday;

	public $position;
	public $service;
	public $company;
	public $company_address;
	public $company_address_complement;
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
			)
		);
	}
}