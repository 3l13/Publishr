<?php

/**
 * This file is part of the WdPublisher software
 *
 * @author Olivier Laviale <olivier.laviale@gmail.com>
 * @link http://www.wdpublisher.com/
 * @copyright Copyright (c) 2007-2010 Olivier Laviale
 * @license http://www.wdpublisher.com/license.html
 */

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
