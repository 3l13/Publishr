<?php

class user_members_WdManager extends user_users_WdManager
{
	protected function get_cell_uid($entry, $tag)
	{
		$rc = '';

		if ($this->photo)
		{
			$rc .= '<img src="' . $this->thumbnail('$icon') . '" class="icon" alt="' . $this->username . '" />';
		}

		return $rc .= parent::get_cell_uid($entry, $tag);
	}
}