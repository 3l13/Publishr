<?php

class contents_agenda_WdActiveRecord extends contents_WdActiveRecord
{
	protected function __get_formatedDate()
	{
		$stime = strtotime($this->date);

		if (empty($this->finish) || !((int) $this->finish))
		{
			return strftime('%d %B %Y', $stime);
		}

		list($sy, $sm, $sd) = explode('-', $this->date);
		list($fy, $fm, $fd) = explode('-', $this->finish);

		$ftime = strtotime($this->finish);

		$rc = 'Du ';

		if ($sy == $fy && $sm == $fm)
		{
			$rc .= strftime('%d', $stime);
		}
		else if ($sy == $fy)
		{
			$rc .= strftime('%d %B', $stime);
		}
		else
		{
			$rc .= strftime('%d %B %Y', $stime);
		}

		$rc .= ' au ' . strftime('%d %B %Y', $ftime);

		return $rc;
	}
}