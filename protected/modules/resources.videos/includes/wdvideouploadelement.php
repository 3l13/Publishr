<?php

class WdVideoUploadElement extends WdFileUploadElement
{
	protected function details($path)
	{
		$rc = parent::details($path);

		require_once 'flvinfo.php';

		$flv = new Flvinfo();

		$info = $flv->getInfo($_SERVER['DOCUMENT_ROOT'] . $path);

		if ($info && $info->hasVideo)
		{
			$rc[] = $info->video->width . ' &times; ' . $info->video->height . ' @ ' . $info->video->fps . 'fps, ' . round($info->duration) . ' secs';
			$rc[] = $info->video->codecStr . ($info->hasAudio ? '/' . $info->audio->codecStr : '');
		}

		return $rc;
	}

	protected function preview($path)
	{
		global $document;

		$document->addJavascript('../public/flowplayer.js');
		$document->addStyleSheet('../public/wdvideouploadelement.css');

		$swf = WdDocument::getURLFromPath('../public/flowplayer.swf');

		$rc = new WdElement
		(
			'a', array
			(
				'href' => $path,
				'style' => 'display:block; width:100%; height:100%;',
				'id' => 'player',
				WdElement::T_INNER_HTML => ''
			)
		);

		$rc .= new WdElement
		(
			'script', array
			(
				'type' => 'text/javascript',
				WdElement::T_INNER_HTML => <<<EOT
window.addEvent
(
	'load', function()
	{
		flowplayer
		(
			"player", "$swf",
			{
		    	clip:
		    	{
			        // these two configuration variables does the trick
			        autoPlay: false,
			        autoBuffering: false
			    },

			    // use a minimalistic controlbar
		    	plugins:
		    	{
		        	controls:
		        	{
			            backgroundGradient: 'none',
			            backgroundColor: 'transparent',
			            all:false,
			            scrubber:true
		        	}
		    	}
			}
		);
	}
);
EOT
			)
		);

		return $rc;
	}
}