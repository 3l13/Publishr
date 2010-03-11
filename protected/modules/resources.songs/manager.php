<?php

class resources_songs_WdManager extends resources_files_WdManager
{
	public function __construct($module, $tags)
	{
		parent::__construct($module, $tags);

		global $document;

		$document->addStyleSheet('public/manage.css');
		$document->addJavascript('public/sm2/soundmanager2.js');
		$document->addJavascript('public/player.js');
	}

	public function __toString()
	{
		$rc = parent::__toString();

		$url = dirname(WdDocument::getURLFromPath('public/sm2/soundmanager2.js')) . '/';

		$rc .= <<<EOT
<div class="slide-wrapper" style="height: 0; overflow: hidden">
<div id="player">
	<script type="text/javascript">
		soundManagerURL = '$url';
	</script>
	<div class="title">
	<strong>-</strong> <span class="separator">&ndash;</span>
	<span class="artist">-</span>
	</div>

	<div class="views">
		<span class="position">-:--</span><span class="separator">/</span><span class="duration">-:--</span>
	</div>

	<!--div class="controls">
	<button type="button" class="play">P</button>
	<button type="button" class="previous">&lt;</button>
	<button type="button" class="next">&gt;</button>
	</div-->

	<div class="clear"></div>

	<div class="progress">
		<div class="progress-gap">
			<div class="load"></div>
			<div class="play"></div>
		</div>
	</div>
</div>
</div>
EOT;

		return $rc;
	}

	protected function columns()
	{
		return parent::columns() + array
		(
			'artist' => array
			(
				WdResume::COLUMN_HOOK => array(__CLASS__, 'select_callback')
			),

			'album' => array
			(
				WdResume::COLUMN_HOOK => array(__CLASS__, 'select_callback')
			),

			'year' => array
			(
				WdResume::COLUMN_HOOK => array(__CLASS__, 'select_callback')
			),

			'duration' => array
			(
				WdResume::COLUMN_CLASS => 'size'
			)
		);
	}

	protected function get_cell_title($entry, $tag)
	{
		$rc = '<a href="#' . $entry->nid . '" class="song" title="Play song">Play song</a>';

		return $rc . ' ' . parent::get_cell_title($entry, $tag);
	}

	protected function get_cell_duration($entry, $tag)
	{
		$duration = $entry->$tag;

		if (!$duration)
		{
			return;
		}

		return self::formatDuration($duration);
	}

	static public function formatDuration($duration)
	{
		$hours = floor($duration / 3600);
		$minutes = floor($duration / 60);
		$seconds = floor($duration - ($minutes * 60));

		if ($hours)
		{
			return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
		}
		else if ($minutes)
		{
			return sprintf('%02d:%02d', $minutes, $seconds);
		}
		else
		{
			return sprintf('%02d secs', $seconds);
		}
	}
}