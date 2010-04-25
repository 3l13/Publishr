<?php

class user_roles_WdModel extends WdModel
{
	public function delete($rid)
	{
		if ($rid == 1)
		{
			throw new WdException('The role %role (%rid) cannot be delete', array('%role' => t('Visitor'), '%rid' => $rid));
		}

		return parent::delete($rid);
	}
}
