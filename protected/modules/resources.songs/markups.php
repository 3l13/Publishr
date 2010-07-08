<?php

class resources_songs_WdMarkups extends patron_markups_WdHooks
{
	static protected function model($name='resources.songs')
	{
		return parent::model($name);
	}

	static public function player(array $args, WdPatron $patron, $template)
	{
		$select = $args['select'];
		$align = $args['align'];

		#
		#
		#

		$entry = null;

		if ($select)
		{
			if (is_numeric($select))
			{
				$entry = self::model()->load($select);
			}
			else
			{
				$entry = self::model()->loadRange(0, 1, ' where title = ?', array($select));
			}

			$entry = $entry->fetchAndClose();
		}

		if (empty($entry))
		{
			$patron->error('Unable to select: %select', array('%select' => $select));

			return;
		}

		if ($template)
		{
			return $patron->publish($template, $entry);
		}

		#
		# build HTML
		#

		// FIXME-20091225: use WdDocument::getURLFromPath()

		$player_url = WdDocument::getURLFromPath('public/dewplayer-mini.swf');

		$data = $player_url . '?mp3=' . $entry->path . '&amp;showtime=1';

		$rc  = '<object type="application/x-shockwave-flash"';

		if ($align)
		{
			$rc .= ' align="' . $align . '"';
		}

		$rc .= ' data="' . $data . '"';
		$rc .= ' width="' . $args['width'] . '" height="' . $args['height'] . '"';
		$rc .= '>';
		$rc .= '<param name="wmode" value="transparent" />';
		$rc .= '<param name="movie" value="' . $data . '" />';
		$rc .= '</object>';

		return $rc;
	}
}