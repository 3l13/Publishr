<?php

class user_members_WdActiveRecord extends user_users_WdActiveRecord
{
	protected function model($name='user.members')
	{
		return parent::model($name);
	}
}