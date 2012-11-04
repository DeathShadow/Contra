<?php

	/*
	*	Contra's Extra Functions.
	*	Created by photofroggy
	*
	*	Released under a Creative Commons Attribution-Noncommercial-Share Alike 3.0 License, which allows you to copy, distribute, transmit, alter, transform,
	*	and build upon this work but not use it for commercial purposes, providing that you attribute me, photofroggy (froggywillneverdie@msn.com) for its creation.
	*
	*	This file stores most of the functions which
	*	are not contained within a class, and are quite
	*	generic.
	*/

	function array_del_key($array, $key) {
		if(array_key_exists($key, $array)) array_splice($array, $key, 1);
		return $array;
	}

	function cmd_in($pre = '', $accept_empty = false) {
	/*
	*		This is a very simple function which is just used
	*		to get input from the command line. The input
	*		has to be at least 1 character long, otherwise
	*		you will be stuck in this loop forever!
	*/

		while(1) {
			echo $pre;
			$str = trim(fgets(STDIN));
			if(strlen($str) > 0) return $str;
			if($accept_empty === true && strlen($str) === 0) return false;
		}
	}

	function cmd_get_args($disp, $args, $hide = false) {
	/*
	*		Here we have the stuff which gets specific arguments
	*		returned according to the input given via the command
	*		line. It's quite simple really.
	*/

		while(1) {
			$str = cmd_in('> '.($disp !== 0 ? $disp.': ' : ''));
			if($args === Null && strlen($str) > 0) return $str;
			$str = strtolower($str);
			if(is_array($args) && array_key_exists($str, $args))
				return $args[$str];
		}
	}

	function main_menu($opt = false) {
		// This is the main menu! The menu is actually skipped if $opt is not false...
		if($opt === false) {
			echo '=====> Main Menu <====='.chr(10);
			echo '>>> Select an option:'.chr(10);
			echo '>> Bot - run the bot.'.chr(10);
			echo '>> Debug - run the bot in debug mode.'.chr(10);
			echo '>> Config - edit the bot config.'.chr(10);
			echo '>> Exit - Exit the program.'.chr(10);
			$select = cmd_get_args(0,array('bot'=>'bot','config'=>'config','exit'=>'exit','debug'=>'debug'));
		} else { $select = str_replace('--','',$opt); }
		switch(strtolower(trim($select))) {
			case 'bot':
			case 'debug':
				define('DEBUG', (strtolower(trim($select)) == 'debug' ? true : false));
				if(!defined('RESTARTABLE')) define('RESTARTABLE', true);
				new Bot;
				break;
			case 'config':
				if(!defined('RESTARTABLE')) define('RESTARTABLE', true);
				config();
				break;
			case 'menu':
				if(!defined('RESTARTABLE')) define('RESTARTABLE', false);
				main_menu(false);
				break;
			case 'exit': default:
				break;
		}
	}

	/*
	*	Function humanList takes
	*	a list (stored as an array)
	*	and produces a grammatically
	*	accurate string for the list!
	*/

	function humanList($data) {
		if(!is_array($data)) return $data;
		if(empty($data)) return false;
		$len = count($data);
		if($len == 1) return $data[0];
		if($len == 2) return implode(' and ', $data);
		$last = array_pop($data);
		return implode(', ', $data).' and '.$last;
	}

        // This is a simple function to return a time length as a properly formatted string! Grammar Nazi ftw!
        function time_length($time) {
	        // We need to reverse the number if it is a negative value.
	        $time = ($time < 0 ? $time * -1 : $time);
	        // The array below stores the formats that will be used. Apparently these are the right values...
	        $formats=array(
		        'year' => 31556926,
		        'week' => 604800,
		        'day' => 86400,
		        'hour' => 3600,
		        'minute' => 60,
		        'second' => 1
	        );
	        $ntimes	= array(); // This array will hold the formatted time!
	        // The loop below sorts everything out.
	        foreach($formats as $name => $format){
		        if($time>=$format){
			        $num = floor($time / $format); // Ok, this actually formats the seconds.
			        $time = $time % $format; // This takes away the seconds we have used/don't need
			        $ntimes[$name] = $num >= 1 ? intVal($num) : false; // Save the value! Yeah!
		        }
	        }
	        // The below array stores the times with the measurements. (1 year etc.)
	        $times = array();
	        // Ok, so we need to loop through the new times and determine which ones to store, and whether it's plural or not.
        	foreach($ntimes as $name => $len)
		        if($len > 0) $times[] = $len.' '.$name.($len == 1 ? '' : 's');
	        // Finally is the simple task of returning a correctly formatted string!
	        return humanList($times);
        }

	function config() {
		echo '> Please enter the following information.'.chr(10);
		$config = array(
			'about' => '<b>%N% %V%%S%</b> (%R% release)<br/><sub><b>Author:</b> :dev%A%:<b>; Owner:</b> :dev%O%:<b>;</b><br/><b>%D%</b></sub>',
			'autojoin' => array()
		);
		foreach(array('username' => Null, 'trigger' => Null, 'owner' => Null) as $part => $s)
			$config['info'][$part] = cmd_get_args('Bot '.$part, $s, false);
		if(strstr($config['info']['owner'], ',') || strstr($config['info']['owner'], ' ') || strstr($config['info']['owner'], ';')) die('Contra does not support multi-owners.'.chr(10));
		echo '> Which channels would you like your bot to join? Separate with commas.'.chr(10);
		$rooms = explode(',',cmd_in('> ', true));
		foreach($rooms as $id => $room)
			if(strlen(trim($room)) > 0) $config['autojoin'][] = trim($room);
		if(empty($config['autojoin']))
			$config['autojoin'] = array('#Botdom');
		if(!ini_get('date.timezone') && PHP_OS == 'Darwin') {
			echo '> Enter your timezone. See http://php.net/manual/en/timezones.php for list of supported timezones.'.chr(10);
			$config['timezone'] = cmd_in('> ', true);
		}elseif(ini_get('date.timezone'))
			$config['timezone'] = ini_get('date.timezone');
		$config = array(
			'info' => $config['info'],
			'about' => $config['about'],
			'autojoin' => $config['autojoin'],
			'damntoken' => '',
			'updatenotes' => true,
			'timezone' => $config['timezone'],
		);
		save_config('./storage/config.cf', $config);
		echo '> Configuration saved!'.chr(10);
	}

	function save_config($file, $data) { file_put_contents($file, "<?php\n\nreturn ".var_export($data, true).";\n\n?>"); }
	function args($string, $index, $cont = false, $sep = ' ') {
		if(empty($string)) return '';
		if($index != 0 ) {
			for($i=1;$i<=$index;$i++) {
				if(strpos($string,$sep)!==false) $string = substr($string,strpos($string,$sep)+1);
				else return '';
			}
		}
		if($cont==true) return $string;
		if(strpos($string, $sep)) return substr($string, 0, strpos($string, $sep));
		return $string;
	}

	// Make sure we have the needed directories!
	if(!is_dir('./plugins'       	 ))   mkdir('./plugins'           , 0755);		// Need plugins directory.
	if(!is_dir('./plugins/extensions'))   mkdir('./plugins/extensions', 0755);		// 	Extensions directory.
	if(!is_dir('./plugins/startup'	 ))   mkdir('./plugins/startup'   , 0755);		// 	Startup directory.
	if(!is_dir('./storage'        	 ))   mkdir('./storage'           , 0755);		// Storage directory.
	if(!is_dir('./storage/logs'   	 ))   mkdir('./storage/logs'      , 0755);		// 	Logs directory.
	if(!is_dir('./storage/bat'    	 ))   mkdir('./storage/bat'       , 0755);		// 	Storage for bat files.
	// Own dis shit!
	chmod('.'					, 0755);
	chmod('./core'				, 0755);
	chmod('./plugins'			, 0755);
	chmod('./plugins/extensions', 0755);
	chmod('./plugins/startup'	, 0755);
	chmod('./storage'			, 0755);
	chmod('./storage/logs'		, 0755);
	chmod('./storage/bat'		, 0755);

	/*
	*	BOTDOM SPROCKET TITLE CODE
	*
	*	This is here as a tribute to the sprocket :(
	*
	*	:shakefist: <b>#BOTDOM</b>&nbsp;<sup><sup>HOME</sup> <b>OF</b> <sub>THE</sub><sup>SPRO</sup><b>C</b><sup><b>K</b><sup><b>E</b><sup><b>T</b></sup></sup></sup></sup> :shakefist:
	*/

?>