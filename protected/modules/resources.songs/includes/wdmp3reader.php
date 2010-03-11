<?php

class WdMP3Reader
{

	/**
	* mp3/mpeg file name
	* @var boolean
	*/
//	var $file = false;
	/**
	* ID3 v1 tag found? (also true if v1.1 found)
	* @var boolean
	*/
//	var $id3v1 = false;
	/**
	* ID3 v1.1 tag found?
	* @var boolean
	*/
//	var $id3v11 = false;
	/**
	* ID3 v2 tag found? (not used yet)
	* @var boolean
	*/
//	var $id3v2 = false;

	// ID3v1.1 Fields:
	/**
	* trackname
	* @var string
	*/
//	var $name = '';
	/**
	* artists
	* @var string
	*/
//	var $artists = '';
	/**
	* album
	* @var string
	*/
//	var $album = '';
	/**
	* year
	* @var string
	*/
//	var $year = '';
	/**
	* comment
	* @var string
	*/
//	var $comment = '';
	/**
	* track number
	* @var integer
	*/
//	var $track = 0;
	/**
	* genre name
	* @var string
	*/
//	var $genre = '';
	/**
	* genre number
	* @var integer
	*/
//	var $genreno = 255;

	// MP3 Frame Stuff
	/**
	* Was the file studied to learn more info?
	* @var boolean
	*/
//	var $studied = false;

	/**
	* version of mpeg
	* @var integer
	*/
	var $mpeg_ver = 0;
	/**
	* version of layer
	* @var integer
	*/
	var $layer = 0;
	/**
	* version of bitrate
	* @var integer
	*/
	var $bitrate = 0;
	/**
	* Frames are crc protected?
	* @var boolean
	*/
	var $crc = false;
	/**
	* frequency
	* @var integer
	*/
	var $frequency = 0;
	/**
	* encoding type (CBR or VBR)
	* @var string
	*/
	var $encoding_type = 0;
	/**
	* number of samples per MPEG audio frame
	* @var integer
	*/
	var $samples_per_frame = 0;
	/**
	* samples in file
	* @var integer
	*/
	var $samples = 0;
	/**
	* Bytes in file without tag overhead
	* @var integer
	*/
	var $musicsize = -1;
	/**
	* number of MPEG audio frames
	* @var integer
	*/
	var $frames = 0;
	/**
	* quality indicator (0% - 100%)
	* @var integer
	*/
	var $quality = 0;
	/**
	* Frames padded
	* @var boolean
	*/
	var $padding = false;
	/**
	* private bit set
	* @var boolean
	*/
	var $private = false;
	/**
	* Mode (Stero etc)
	* @var string
	*/
	var $mode = '';
	/**
	* Copyrighted
	* @var string
	*/
	var $copyright = false;
	/**
	* On Original Media? (never used)
	* @var boolean
	*/
	var $original = false;
	/**
	* Emphasis (also never used)
	* @var boolean
	*/
	var $emphasis = '';
	/**
	* Bytes in file
	* @var integer
	*/
	var $filesize = -1;
	/**
	* Byte at which the first mpeg header was found
	* @var integer
	*/
	var $frameoffset = -1;
	/**
	* length of mp3 format hh:mm:ss
	* @var string
	*/
//	var $lengthh = false;
	/**
	* length of mp3 format mm:ss
	* @var string
	*/
//	var $length = false;
	/**
	* length of mp3 in seconds
	* @var string
	*/
	var $lengths = false;

	/**
	* if any errors they will be here
	* @var string
	*/
	var $error = false;

	/**
	* print debugging info?
	* @var boolean
	*/
	var $debug = false;

	/*
	* creates a new id3 object
	* and loads a tag from a file.
	*
	* @param string    $study  study the mpeg frame to get extra info like bitrate and frequency
	*                          You should advoid studing alot of files as it will siginficantly
	*                          slow this down.
	* @access public
	*/

	function __construct($study = false)
	{
//		$this->debug = true;
		$this->study=($study || defined('ID3_AUTO_STUDY'));
	}

	/**
	* reads the given file and parse it
	*
	* @param    string  $file the name of the file to parse
	* @return   mixed   PEAR_Error on error
	* @access   public
	*/
	public function read($file)
	{
		if ($this->debug) print($this->debugbeg . "id3('$file')<HR>\n");

		if(!empty($file))$this->file = $file;
		if ($this->debug) print($this->debugend);

		return $this->_read_v1();
	}

	/**
	* getGenre - return the name of a genre number
	*
	* if no genre number is specified the genre number from
	* $this->genreno will be used.
	*
	* the genre is returned or false if an error or not found
	* no error message is ever returned
	*
	* @param   integer $genreno Number of the genre
	* @return  mixed   false, if no genre found, else string
	*
	* @access public
	*/
	function getGenre($genreno) {
	if ($this->debug) print($this->debugbeg . "getgenre($genreno)<HR>\n");

	$genres = $this->genres();
	if (isset($genres[$genreno])) {
	$genre = $genres[$genreno];
	if ($this->debug) print($genre . "\n");
	} else {
	$genre = '';
	}

	if ($this->debug) print($this->debugend);
	return $genre;
	} // getGenre($genreno)

	/*
	* getGenreNo - return the number of the genre name
	*
	* the genre number is returned or 0xff (255) if a match is not found
	* you can specify the default genreno to use if one is not found
	* no error message is ever returned
	*
	* @param   string  $genre      Name of the genre
	* @param   integer $default    Genre number in case of genre not found
	*
	* @access public
	*/
	function getGenreNo($genre, $default = 0xff) {
	if ($this->debug) print($this->debugbeg . "getgenreno('$genre',$default)<HR>\n");

	$genres = $this->genres();
	$genreno = false;
	if ($genre) {
	foreach ($genres as $no => $name) {
	if (strtolower($genre) == strtolower($name)) {
	if ($this->debug) print("$no:'$name' == '$genre'");
	$genreno = $no;
	}
	}
	}
	if ($genreno === false) $genreno = $default;
	if ($this->debug) print($this->debugend);
	return $genreno;
	} // getGenreNo($genre, $default = 0xff)

	/*
	* genres - returns an array of the ID3v1 genres
	*
	* @return array
	*
	* @access public
	*/
	static $genres_tables = array
	(
		'Blues',
		'Classic Rock',
		'Country',
		'Dance',
		'Disco',
		'Funk',
		'Grunge',
		'Hip-Hop',
		'Jazz',
		'Metal',
		'New Age',
		'Oldies',
		'Other',
		'Pop',
		'R&B',
		'Rap',
		'Reggae',
		'Rock',
		'Techno',
		'Industrial',
		'Alternative',
		'Ska',
		'Death Metal',
		'Pranks',
		'Soundtrack',
		'Euro-Techno',
		'Ambient',
		'Trip-Hop',
		'Vocal',
		'Jazz+Funk',
		'Fusion',
		'Trance',
		'Classical',
		'Instrumental',
		'Acid',
		'House',
		'Game',
		'Sound Clip',
		'Gospel',
		'Noise',
		'Alternative Rock',
		'Bass',
		'Soul',
		'Punk',
		'Space',
		'Meditative',
		'Instrumental Pop',
		'Instrumental Rock',
		'Ethnic',
		'Gothic',
		'Darkwave',
		'Techno-Industrial',
		'Electronic',
		'Pop-Folk',
		'Eurodance',
		'Dream',
		'Southern Rock',
		'Comedy',
		'Cult',
		'Gangsta',
		'Top 40',
		'Christian Rap',
		'Pop/Funk',
		'Jungle',
		'Native US',
		'Cabaret',
		'New Wave',
		'Psychadelic',
		'Rave',
		'Showtunes',
		'Trailer',
		'Lo-Fi',
		'Tribal',
		'Acid Punk',
		'Acid Jazz',
		'Polka',
		'Retro',
		'Musical',
		'Rock & Roll',
		'Hard Rock',
		'Folk',
		'Folk-Rock',
		'National Folk',
		'Swing',
		'Fast Fusion',
		'Bebob',
		'Latin',
		'Revival',
		'Celtic',
		'Bluegrass',
		'Avantgarde',
		'Gothic Rock',
		'Progressive Rock',
		'Psychedelic Rock',
		'Symphonic Rock',
		'Slow Rock',
		'Big Band',
		'Chorus',
		'Easy Listening',
		'Acoustic',
		'Humour',
		'Speech',
		'Chanson',
		'Opera',
		'Chamber Music',
		'Sonata',
		'Symphony',
		'Booty Bass',
		'Primus',
		'Porn Groove',
		'Satire',
		'Slow Jam',
		'Club',
		'Tango',
		'Samba',
		'Folklore',
		'Ballad',
		'Power Ballad',
		'Rhytmic Soul',
		'Freestyle',
		'Duet',
		'Punk Rock',
		'Drum Solo',
		'Acapella',
		'Euro-House',
		'Dance Hall',
		'Goa',
		'Drum & Bass',
		'Club-House',
		'Hardcore',
		'Terror',
		'Indie',
		'BritPop',
		'Negerpunk',
		'Polsk Punk',
		'Beat',
		'Christian Gangsta Rap',
		'Heavy Metal',
		'Black Metal',
		'Crossover',
		'Contemporary Christian',
		'Christian Rock',
		'Merengue',
		'Salsa',
		'Trash Metal',
		'Anime',
		'Jpop',
		'Synthpop'
	);

	static $id3tag_to_var = array
	(
		'TPE1' => 'artist',
		'TALB' => 'album',
		'TIT2' => 'title',
		'TRCK' => 'track',
		'TYER' => 'year'
	);

	public function _read_v2($file)
	{
		$fh = @fopen($file, 'rb');

		if (!$fh)
		{
			throw new WdException('Unable to open file <em>\1</em>', $file);
		}

		$this->file = $file;
		$this->id3 = self::readId3($fh);

		if ($this->id3)
		{
			#
			# text
			#

			foreach (self::$id3tag_to_var as $id => $value)
			{
				$this->$value = empty($this->id3[$id]) ? NULL : $this->id3[$id]['contents'];
			}

			#
			# rat
			#

			$this->rating = empty($this->id3['POPM']) ? 0 : $this->id3['POPM']['rating'];
		}

//		wd_log('frames: \1', $frames);

		$this->readFrame($fh);

		$left = fread($fh, 1024);

//		wd_log('<code>\1</code> == \2', wordwrap(bin2hex($left), 32, "\n", true), mysql_real_escape_string($left));
	}

	const HEADER_BIT_UNSYNCRONIZATION = 7;
	const HEADER_BIT_EXTENDED = 6;
	const HEADER_BIT_EXPERIMENTAL = 5;

	static public function readId3($handle)
	{
		fseek($handle, 0);

		$header = fread($handle, 10);
		$header = unpack('A3id/C1version/C1revision/C1flags/N1size', $header);

//		wd_log('header unpacked: \1', $header);

		if ($header['id'] != 'ID3')
		{
			#
			# this is not an ID3 header, we need to reset the file
			# read position
			#

			fseek($handle, 0);

			return;
		}

		return WdMP3Reader::readId3Tags($handle);
	}

	static public function readId3Tags($fh)
	{
		$n = 0;

		$frames = array();

		for (;;)
		{
			$header = fread($fh, 10);
			$header = unpack('A4id/N1size/n1flags', $header);

			if ($header['size'] < 1)
			{
				break;
			}

			$raw = fread($fh, $header['size']);

//			wd_log('header unpacked: \1: \2', $header, mysql_real_escape_string($contents));

			$frame = $header;
			$frame['raw'] = bin2hex($raw);

			$id = $header['id'];

			switch ($id)
			{
				case 'TPE1':
				case 'TABL':
				case 'TPE2':
				case 'TIT2':
				case 'TPUB':
				case 'TRCK':
				case 'TSSE':
				case 'TYER':
				case 'TDRC':
				case 'TALB':
				case 'TXXX':

				#
				# Text information frames - details
				#

				case 'TALB':
				case 'TBPM':
				case 'TCOM':
				case 'TCON':
				case 'TCOP':
				case 'TDAT':
				case 'TDLY':
				case 'TENC':
				case 'TEXT':
				case 'TFLT':
				case 'TIME':
				case 'TIT1':
				case 'TIT2':
				case 'TIT3':
				case 'TKEY':
				case 'TLAN':
				case 'TLEN':
				case 'TMED':
				case 'TOAL':
				case 'TOFN':
				case 'TOLY':
				case 'TOPE':
				case 'TORY':
				case 'TOWN':
				case 'TPE1':
				case 'TPE2':
				case 'TPE3':
				case 'TPE4':
				case 'TPOS':
				case 'TPUB':
				case 'TRCK':
				case 'TRDA':
				case 'TRSN':
				case 'TRSO':
				case 'TSIZ':
				case 'TSRC':
				case 'TSSE':
				case 'TYER':
				{
					$frame += unpack('c1encoding/H*contents', $raw);
				}
				break;

				case 'USLT':
				{
					#
					# Unsychronised lyrics/text transcription
					#

					$frame += unpack('c1encoding/A3language/n1descriptor/H*contents', $raw);
				}
				break;

				case 'POPM':
				{
					#
					# Popularimeter
					#

					if (preg_match('([^\0]+)', $raw, $match))
					{
						$frame['email'] = $match[0];

						$raw = substr($raw, strlen($frame['email']) + 1);

						$frame += unpack('C1rating/c*counter', $raw);
					}
//					$frame += unpack('a*email/c1rating/c*counter', $raw);
				}
				break;

				case 'APIC':
				{
					#
					# Attached picture
					#

					/*
					$frame['encoding'] = $raw{0};

					preg_match('([^\0]+)', substr($raw, 1), $match);

					$frame['mime'] = $match[0];

					$raw = substr($raw, 1 + strlen($frame['mime'] + 1));

					$frame['type'] = $raw{0};

					preg_match('([^\0]+)', substr($raw, 1), $match);

					$frame['contents'] = $match[0];
					*/
				}
				break;

				default:
				{
//					wd_log('unsupported tag <em>\1</em> [\2][\3]', $id,	mysql_real_escape_string($raw), bin2hex($raw));

					$frame = array();
				}
				break;
			}

			if (empty($frame))
			{
				continue;
			}

			$frames[$id] = $frame;
		}

		#
		# convert contents
		#

		foreach ($frames as &$frame)
		{
			WdMP3Reader::unpackContents($frame);
		}

		return $frames;
	}

	static public function unpackContents(&$frame)
	{
		if (isset($frame['encoding']))
		{
			$contents = pack('H*', $frame['contents']);

			$frame['contents'] = $frame['encoding'] == 1 ? mb_convert_encoding($contents, 'utf-8', 'utf-16') : utf8_encode($contents);
		}
	}
	/*
	static public function unpackString($raw)
	{
		switch ($raw{0})
		{
			case 0:
			{
				#
				# iso-8859-1
				#

				return utf8_encode(substr($raw, 1));
			}
			break;

			case 1:
			{
				#
				# utf-16
				#

				return mb_convert_encoding(substr($raw, 1), 'utf-8', 'utf-16');
			}
			break;

			default:
			{
				throw new WdException('Unknown charset marker: <em>\1</em>', $raw{0});
			}
			break;
		}
	}
	*/

	static $layer_table = array
	(
		array(0,3),
		array(2,1)
	);

	static $samples_per_frame_table = array
	(
		'1' => array
		(
			'1' => 384,
			'2' => 1152,
			'3' => 1152
		),

		'2' => array
		(
			'1' => 384,
			'2' => 1152,
			'3' => 576
		),

		'2.5' => array
		(
			'1' => 384,
			'2' => 1152,
			'3' => 576
		),
	);

	static $frequency_table = array
	(
		'1' => array
		(
			'0' => array(44100, 48000),
			'1' => array(32000, 0),
		),

		'2' => array
		(
			'0' => array(22050, 24000),
			'1' => array(16000, 0),
		),

		'2.5' => array
		(
			'0' => array(11025, 12000),
			'1' => array(8000, 0),
		)
	);

    function readFrame($f)
	{
		if ($this->debug) print($this->debugbeg . "_readframe()<HR>\n");

		/*
		$file = $this->file;

		$mqr = get_magic_quotes_runtime();
		set_magic_quotes_runtime(0);

		if (! ($f = fopen($file, 'rb')) ) {
			if ($this->debug) print($this->debugend);
			return PEAR::raiseError( "Unable to open " . $file, PEAR_MP3_ID_FNO) ;
		}

		$this->filesize = filesize($file);
		*/
		do {
			while (fread($f,1) != Chr(255)) { // Find the first frame
			if ($this->debug) echo "Find...\n";
			if (feof($f)) {
				if ($this->debug) print($this->debugend);
				return PEAR::raiseError( "No mpeg frame found", PEAR_MP3_ID_NOMP3) ;
			}
			}
			fseek($f, ftell($f) - 1); // back up one byte

			$frameoffset = ftell($f);

			$r = fread($f, 4);
			// Binary to Hex to a binary sting. ugly but best I can think of.
			// $bits = unpack('H*bits', $r);
			// $bits =  base_convert($bits['bits'],16,2);
			$bits = sprintf("%'08b%'08b%'08b%'08b", ord($r{0}), ord($r{1}), ord($r{2}), ord($r{3}));
		} while (!$bits[8] and !$bits[9] and !$bits[10]); // 1st 8 bits true from the while
		if ($this->debug) print('Bits: ' . $bits . "\n");

		$this->frameoffset = $frameoffset;

		// Detect VBR header
		if ($bits[11] == 0) {
			if (($bits[24] == 1) && ($bits[25] == 1)) {
				$vbroffset = 9; // MPEG 2.5 Mono
			} else {
				$vbroffset = 17; // MPEG 2.5 Stereo
			}
		} else if ($bits[12] == 0) {
			if (($bits[24] == 1) && ($bits[25] == 1)) {
				$vbroffset = 9; // MPEG 2 Mono
			} else {
				$vbroffset = 17; // MPEG 2 Stereo
			}
		} else {
			if (($bits[24] == 1) && ($bits[25] == 1)) {
				$vbroffset = 17; // MPEG 1 Mono
			} else {
				$vbroffset = 32; // MPEG 1 Stereo
			}
		}

		fseek($f, ftell($f) + $vbroffset);
		$r = fread($f, 4);

		switch ($r) {
			case 'Xing':
				$this->encoding_type = 'VBR';
			case 'Info':
				// Extract info from Xing header

				if ($this->debug) print('Encoding Header: ' . $r . "\n");

				$r = fread($f, 4);
				$vbrbits = sprintf("%'08b", ord($r{3}));

				if ($this->debug) print('XING Header Bits: ' . $vbrbits . "\n");

				if ($vbrbits[7] == 1) {
					// Next 4 bytes contain number of frames
					$r = fread($f, 4);
					$this->frames = unpack('N', $r);
					$this->frames = $this->frames[1];
				}

				if ($vbrbits[6] == 1) {
					// Next 4 bytes contain number of bytes
					$r = fread($f, 4);
					$this->musicsize = unpack('N', $r);
					$this->musicsize = $this->musicsize[1];
				}

				if ($vbrbits[5] == 1) {
					// Next 100 bytes contain TOC entries, skip
					fseek($f, ftell($f) + 100);
				}

				if ($vbrbits[4] == 1) {
					// Next 4 bytes contain Quality Indicator
					$r = fread($f, 4);
					$this->quality = unpack('N', $r);
					$this->quality = $this->quality[1];
				}

				break;

			case 'VBRI':
			default:
				if ($vbroffset != 32) {
					// VBRI Header is fixed after 32 bytes, so maybe we are looking at the wrong place.
					fseek($f, ftell($f) + 32 - $vbroffset);
					$r = fread($f, 4);

					if ($r != 'VBRI') {
						$this->encoding_type = 'CBR';
						break;
					}
				} else {
					$this->encoding_type = 'CBR';
					break;
				}

				if ($this->debug) print('Encoding Header: ' . $r . "\n");

				$this->encoding_type = 'VBR';

				// Next 2 bytes contain Version ID, skip
				fseek($f, ftell($f) + 2);

				// Next 2 bytes contain Delay, skip
				fseek($f, ftell($f) + 2);

				// Next 2 bytes contain Quality Indicator
				$r = fread($f, 2);
				$this->quality = unpack('n', $r);
				$this->quality = $this->quality[1];

				// Next 4 bytes contain number of bytes
				$r = fread($f, 4);
				$this->musicsize = unpack('N', $r);
				$this->musicsize = $this->musicsize[1];

				// Next 4 bytes contain number of frames
				$r = fread($f, 4);
				$this->frames = unpack('N', $r);
				$this->frames = $this->frames[1];
		}

		/*
		fclose($f);
		set_magic_quotes_runtime($mqr);
		*/

		if ($bits[11] == 0)
		{
			$this->mpeg_ver = "2.5";

			$bitrates = array
			(
				'1' => array(0, 32, 48, 56, 64, 80, 96, 112, 128, 144, 160, 176, 192, 224, 256, 0),
				'2' => array(0,  8, 16, 24, 32, 40, 48,  56,  64,  80,  96, 112, 128, 144, 160, 0),
				'3' => array(0,  8, 16, 24, 32, 40, 48,  56,  64,  80,  96, 112, 128, 144, 160, 0),
			);
		}
		else if ($bits[12] == 0)
		{
			$this->mpeg_ver = "2";

			$bitrates = array
			(
				'1' => array(0, 32, 48, 56, 64, 80, 96, 112, 128, 144, 160, 176, 192, 224, 256, 0),
				'2' => array(0,  8, 16, 24, 32, 40, 48,  56,  64,  80,  96, 112, 128, 144, 160, 0),
				'3' => array(0,  8, 16, 24, 32, 40, 48,  56,  64,  80,  96, 112, 128, 144, 160, 0),
			);
		}
		else
		{
			$this->mpeg_ver = "1";

			$bitrates = array
			(
				'1' => array(0, 32, 64, 96, 128, 160, 192, 224, 256, 288, 320, 352, 384, 416, 448, 0),
				'2' => array(0, 32, 48, 56,  64,  80,  96, 112, 128, 160, 192, 224, 256, 320, 384, 0),
				'3' => array(0, 32, 40, 48,  56,  64,  80,  96, 112, 128, 160, 192, 224, 256, 320, 0),
			);
		}

		if ($this->debug) print('MPEG' . $this->mpeg_ver . "\n");

		$this->layer = self::$layer_table[$bits[13]][$bits[14]];

		if ($this->debug) print('layer: ' . $this->layer . "\n");

		if ($bits[15] == 0)
		{
			// It's backwards, if the bit is not set then it is protected.
			if ($this->debug) print("protected (crc)\n");
			$this->crc = true;
		}

		$bitrate = 0;
		if ($bits[16] == 1) $bitrate += 8;
		if ($bits[17] == 1) $bitrate += 4;
		if ($bits[18] == 1) $bitrate += 2;
		if ($bits[19] == 1) $bitrate += 1;
		$this->bitrate = $bitrates[$this->layer][$bitrate];

		$this->frequency = self::$frequency_table[$this->mpeg_ver][$bits[20]][$bits[21]];

		$this->padding = $bits[22];
		$this->private = $bits[23];

		$mode = array
		(
			array('Stereo', 'Joint Stereo'),
			array('Dual Channel', 'Mono'),
		);

		$this->mode = $mode[$bits[24]][$bits[25]];

		// XXX: I dunno what the mode extension is for bits 26,27

		$this->copyright = $bits[28];
		$this->original = $bits[29];

		$emphasis = array(
			array('none', '50/15ms'),
			array('', 'CCITT j.17'),
				 );
		$this->emphasis = $emphasis[$bits[30]][$bits[31]];

		$this->samples_per_frame = self::$samples_per_frame_table[$this->mpeg_ver][$this->layer];

		if ($this->encoding_type != 'VBR')
		{
			if ($this->bitrate == 0)
			{
				$s = -1;
			}
			else
			{
				$s = ((8*filesize($this->file))/1000) / $this->bitrate;
			}
//			$this->length = sprintf('%02d:%02d',floor($s/60),floor($s-(floor($s/60)*60)));
//			$this->lengthh = sprintf('%02d:%02d:%02d',floor($s/3600),floor($s/60),floor($s-(floor($s/60)*60)));
			$this->duration = (int) $s;

			$this->samples = ceil($this->duration * $this->frequency);
			if(0 != $this->samples_per_frame) {
				$this->frames = ceil($this->samples / $this->samples_per_frame);
			} else {
				$this->frames = 0;
			}
			$this->musicsize = ceil($this->duration * $this->bitrate * 1000 / 8);
		}
		else
		{
			$this->samples = $this->samples_per_frame * $this->frames;
			$s = $this->samples / $this->frequency;

//			$this->length = sprintf('%02d:%02d',floor($s/60),floor($s-(floor($s/60)*60)));
//			$this->lengthh = sprintf('%02d:%02d:%02d',floor($s/3600),floor($s/60),floor($s-(floor($s/60)*60)));
			$this->duration = (int) $s;

			$this->bitrate = (int)(($this->musicsize / $s) * 8 / 1000);
		}

		if ($this->debug) print($this->debugend);
		} // _readframe()

}