<?php

class resources_songs_WdModel extends resources_files_WdModel
{
	protected $accept = array
	(
		'audio/mpeg'
	);

	public function save(array $values, $key=null, array $options=array())
	{
		$options += array
		(
			self::ACCEPT => $this->accept,
			self::UPLOADED => &$uploaded
		);

		$rc = parent::save($values, $key, $options);

		if (!$rc)
		{
			return $rc;
		}

		#
		# we update the "width" and "height" properties if the file is changed
		#

		if ($uploaded)
		{
			$path = $this->parent->_select(File::PATH)->where(array('{primary}' => $rc))->column();

			//wd_log('path: \1', $path);

			if ($path)
			{
				$infos = resources_songs_WdModule::parseMP3($_SERVER['DOCUMENT_ROOT'] . $path);

				//wd_log('parse: \1', $infos);

				$_POST = array_merge($_POST, $infos);

				$this->update($infos, $rc);
			}
		}

		return $rc;
	}
}