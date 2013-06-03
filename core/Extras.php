<?php

	/// Function for converting unicode characters to UTF8 html entities.
	/// Created as a fix for issue #14. 
	/// Author: Justin Eittreim (DivinityArcane) > eittreim.justin@live.com
	function hexentity($char) {
		/*
		 * 7	U+007F	0xxxxxxx
		 * 11	U+07FF	110xxxxx	10xxxxxx
		 * 16	U+FFFF	1110xxxx	10xxxxxx	10xxxxxx
		 * 21	U+1FFFFF	11110xxx	10xxxxxx	10xxxxxx	10xxxxxx */
		$ords = array(ord($char));
		$bin = decbin(ord($char));
		
		// Ensure the char is holding more than one byte.
		if (!isset($char{1})) {
			if ($ords[0] >= 0 AND 127 >= $ords[0]) {
				return $char;
			} else {
				throw new Exception('Invalid unicode characted.');
			}
		} else {
			$ords[1] = ord($char{1});
		}
		
		if (!isset($char{2})) {
			if ($ords[0] >= 192 AND 223 >= $ords[0]) {
				return '&#'. (64*($ords[0]-192)+($ords[1]-128)) .';';
			} else {
				throw new Exception('Invalid unicode characted.');
			}
		} else {
			$ords[2] = ord($char{2});
		}
		
		if (!isset($char{3})) {
			if ($ords[0] >= 224 AND 239 >= $ords[0]) {
				return '&#'. ((4096*($ords[0]-224))+(64*($ords[1]-128))+($ords[2]-128)) .';';
			} else {
				throw new Exception('Invalid unicode characted.');
			}
		} else {
			$ords[3] = ord($char{3});
			if ($ords[0] >= 240 AND 247 >= $ords[0]) {
				return '&#'. ((262144*($ords[0]-240))+(4096*($ords[1]-128))+(64*($ords[2]-128))+($ords[3]-128)) .';';
			} else {
				throw new Exception('Invalid unicode characted.');
			}
		}
		
		// No 5, 6, 7, or 8 byte chars should be supported.
		// As per RFC3629, UTF-8 is no longer allowed to pass 0x1FFFFF
		throw new Exception('Invalid character passed for conversion.');
	}

	define('BYTE_SIZE_GB', pow(2, 30));
	define('BYTE_SIZE_MB', pow(2, 20));
	define('BYTE_SIZE_KB', pow(2, 10));

	/// Function for formatting bytes into human readable strings.
	/// Author: Justin Eittreim (DivinityArcane) > eittreim.justin@live.com
	function FormatBytes($bytes, $verbose = false) {
		if ($bytes <= 0) {
			return $verbose ? '0 Bytes' : '0B';
		} elseif ($bytes == 1) {
			return $verbose ? '1 Byte' : '1B';
		}

		$gb = 0; $mb = 0; $kb = 0;

		while ($bytes >= BYTE_SIZE_GB) {
			$gb++; $bytes -= BYTE_SIZE_GB;
		}
		while ($bytes >= BYTE_SIZE_MB) {
			$mb++; $bytes -= BYTE_SIZE_MB;
		}
		while ($bytes >= BYTE_SIZE_KB) {
			$kb++; $bytes -= BYTE_SIZE_KB;
		}

		$fmt = array();

		if ($gb > 0) {
			array_push($fmt, $gb . ($verbose ? ' GigaByte' . ($gb == 1 ? '' : 's') : 'GB'));
		}
		if ($mb > 0) {
			array_push($fmt, $mb . ($verbose ? ' MegaByte' . ($mb == 1 ? '' : 's') : 'MB'));
		}
		if ($kb > 0) {
			array_push($fmt, $kb . ($verbose ? ' KiloByte' . ($kb == 1 ? '' : 's') : 'kB'));
		}
		if ($bytes > 0) {
			array_push($fmt, $bytes . ($verbose ? ' Byte' . ($bytes == 1 ? '' : 's') : 'B'));
		}

		return implode(', ', $fmt);
	}
    
    function FormatTime($seconds) {
        $years      = 0;
        $weeks      = 0;
        $days       = 0;
        $hours      = 0;
        $minutes    = 0;
        $seconds    = round($seconds);
        $output     = '';
        
        if ($seconds <= 0) return '0 seconds';
        
        while ($seconds >= 31556926) {
            $years++;
            $seconds -= 31556926;
        }
        
        while ($seconds >= 604800) {
            $weeks++;
            $seconds -= 604800;
        }
        
        while ($seconds >= 86400) {
            $days++;
            $seconds -= 86400;
        }
        
        while ($seconds >= 3600) {
            $hours++;
            $seconds -= 3600;
        }
        
        while ($seconds >= 60) {
            $minutes++;
            $seconds -= 60;
        }
        
        if ($years   > 0) $output .= $years.   ' year'.   ($years   == 1 ? '' : 's') . ', ';
        if ($weeks   > 0) $output .= $weeks.   ' week'.   ($weeks   == 1 ? '' : 's') . ', ';
        if ($days    > 0) $output .= $days.    ' day'.    ($days    == 1 ? '' : 's') . ', ';
        if ($hours   > 0) $output .= $hours.   ' hour'.   ($hours   == 1 ? '' : 's') . ', ';
        if ($minutes > 0) $output .= $minutes. ' minute'. ($minutes == 1 ? '' : 's') . ', ';
        if ($seconds > 0) $output .= $seconds. ' second'. ($seconds == 1 ? '' : 's') . ', ';
        
        return substr($output, 0, -2);
    }

    /* Deviant info function
     * 2008-2013 Justin Eittreim
     * http://divinityarcane.deviantart.com/ */
    function deviant_info($user) {
        $url  = 'http://'.$user.'.deviantart.com/';
        $page = file_get_contents($url) or null;

        if ($page == null) return null;
        
        $patt = '%(<title>(?P<username>[^\s]+) on deviantART</title>)|(<strong>(?P<number>[^<]+)</strong>(?P<type>[^\t]+))|(<div id="super-secret-\w+"[^>]*>(?P<tagline>[^<]+))|(<d. class="f h">(?P<item>[^<]+)</d.>)|(<div>Deviant for (?P<years>[^<]+)</div><div>(?P<member>[^<]+)</div>)%';
        $data = array('info'=>array());
        $type = 0;
        $tnls = array(1=>'Type', 2=>'Name', 3=>'ASL');
        
        foreach (explode("\n", $page) as $line) {
            preg_match_all($patt, $line, $matches);
            
            if (count($matches['item']) > 0 && strlen($matches['item'][0]) > 0) {
                if (count($matches['item']) == 1)
                    $data[$tnls[++$type]] = $matches['item'][0];
                else if (strlen($matches['item'][0]) <= 32)
                    $data[$matches['item'][0]] = $matches['item'][1];

            } else if (count($matches['tagline']) > 0 && strlen($matches['tagline'][0]) > 0) {
                $data['Tagline'] = $matches['tagline'][0];
            
            } else if (count($matches['username']) > 0 && strlen($matches['username'][0]) > 0) {
                $data['Username'] = str_replace('#', '', $matches['username'][0]);
            
            } else if (count($matches['years']) > 0 && strlen($matches['years'][0]) > 0) {
                $data['Joined'] = 'Deviant for '.preg_replace('%\s+%', ' ', $matches['years'][0]);
                $data['Member'] = $matches['member'][0];
            
            } else if (count($matches['number']) > 0 && strlen($matches['number'][0]) > 0) {
                $t = strip_tags(trim($matches['type'][0]));
                if (strlen($t) < 1) continue;
                if (strstr($t, '   ')) $t = substr($t, 0, strpos($t, '   '));
		if (strlen($t) > 5 && strlen($t) <= 32)
			$data['info'][$t] = $matches['number'][0];
            }
        }

        return $data;
    }

?>
